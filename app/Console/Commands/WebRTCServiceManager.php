<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\App;

class WebRTCServiceManager extends Command
{
    protected $signature = 'webrtc:services {action=status} {service?}';
    protected $description = 'Manage WebRTC services (websocket, coturn, media server)';

    private $services = [
        'websocket' => [
            'command' => 'php artisan webrtc:websocket --daemon',
            'name' => 'WebRTC WebSocket Server',
            'check_command' => 'ps aux | grep "[w]ebrtc:websocket"',
            'stop_command' => 'pkill -f "webrtc:websocket"'
        ],
        'coturn' => [
            'command' => 'turnserver -c /etc/turnserver.conf',
            'name' => 'Coturn STUN/TURN Server',
            'check_command' => 'ps aux | grep "[t]urnserver"',
            'stop_command' => 'pkill -f "turnserver"'
        ]
    ];

    private $processes = [];
    private $isConsoleCommand = false;
    private $isShuttingDown = false;

    public function __construct()
    {
        parent::__construct();
        $this->isConsoleCommand = App::runningInConsole() && php_sapi_name() === 'cli';
    }

    public function handle()
    {
        $action = $this->argument('action');
        $service = $this->argument('service');

        // Don't execute if we're shutting down
        if ($this->isShuttingDown) {
            return 0;
        }

        switch ($action) {
            case 'start':
                return $this->startServices($service);
            case 'stop':
                $this->isShuttingDown = true; // Only set shutdown flag when explicitly stopping
                return $this->stopServices($service);
            case 'restart':
                $this->isShuttingDown = true; // Set shutdown flag for restart
                $this->stopServices($service);
                sleep(2); // Wait for service to stop
                $this->isShuttingDown = false; // Clear flag before starting
                return $this->startServices($service);
            case 'status':
                return $this->showStatus($service);
            default:
                $this->error("Unknown action: {$action}");
                return 1;
        }
    }

    private function startServices($targetService = null)
    {
        $success = true;
        foreach ($this->services as $service => $config) {
            if ($targetService && $service !== $targetService) {
                continue;
            }

            if ($this->isConsoleCommand) {
                $this->info("Starting {$config['name']}...");
            }
            
            try {
                // Check if service is already running
                $checkProcess = Process::fromShellCommandline($config['check_command']);
                $checkProcess->run();
                
                if ($checkProcess->getExitCode() === 0) {
                    if ($this->isConsoleCommand) {
                        $this->info("{$config['name']} is already running");
                    }
                    continue;
                }

                // Start the service
                $process = Process::fromShellCommandline($config['command']);
                $process->setWorkingDirectory(base_path());
                $process->setTimeout(null);
                
                // For daemon processes, we don't need to capture output
                if (strpos($config['command'], '--daemon') !== false) {
                    $process->disableOutput();
                }
                
                $process->start();
                
                // Wait a moment to check if process started successfully
                sleep(2);
                
                // Check if process is running
                $checkProcess->run();
                if ($checkProcess->getExitCode() === 0) {
                    if ($this->isConsoleCommand) {
                        $this->info("{$config['name']} started successfully");
                    }
                } else {
                    $success = false;
                    if ($this->isConsoleCommand) {
                        $this->error("{$config['name']} failed to start");
                    }
                }
            } catch (\Exception $e) {
                $success = false;
                if ($this->isConsoleCommand) {
                    $this->error("Error starting {$config['name']}: " . $e->getMessage());
                }
                Log::error("Service start error", [
                    'service' => $service,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
        return $success ? 0 : 1;
    }

    private function stopServices($targetService = null)
    {
        $this->isShuttingDown = true;
        $success = true;

        foreach ($this->services as $service => $config) {
            if ($targetService && $service !== $targetService) {
                continue;
            }

            try {
                // Check if service is running
                $checkProcess = Process::fromShellCommandline($config['check_command']);
                $checkProcess->run();
                
                if ($checkProcess->getExitCode() === 0) {
                    // Extract PID from ps output
                    preg_match('/^\S+\s+(\d+)/', $checkProcess->getOutput(), $matches);
                    if (isset($matches[1])) {
                        $pid = $matches[1];
                        // Send SIGTERM to the process for graceful shutdown
                        Process::fromShellCommandline("kill -15 {$pid}")->run();
                        
                        // Wait for process to stop
                        $maxWait = 10;
                        while ($maxWait > 0) {
                            $checkProcess->run();
                            if ($checkProcess->getExitCode() !== 0) {
                                break;
                            }
                            sleep(1);
                            $maxWait--;
                        }

                        // Force kill if still running
                        if ($maxWait === 0) {
                            Process::fromShellCommandline("kill -9 {$pid}")->run();
                        }
                        
                        // Log the shutdown
                        Log::info("Service stopped", [
                            'service' => $service,
                            'pid' => $pid
                        ]);

                        if ($this->isConsoleCommand) {
                            //$this->info("{$config['name']} stopped");
                        }
                    }
                } else {
                    Log::info("Service was not running", ['service' => $service]);
                }
            } catch (\Exception $e) {
                $success = false;
                Log::error("Service stop error", [
                    'service' => $service,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
        return $success ? 0 : 1;
    }

    private function showStatus($targetService = null)
    {
        if (!$this->isConsoleCommand) {
            return;
        }

        $headers = ['Service', 'Status', 'PID'];
        $rows = [];

        foreach ($this->services as $service => $config) {
            if ($targetService && $service !== $targetService) {
                continue;
            }

            $checkProcess = Process::fromShellCommandline($config['check_command']);
            $checkProcess->run();
            
            $status = 'Stopped';
            $pid = '-';

            if ($checkProcess->getExitCode() === 0) {
                $status = 'Running';
                preg_match('/^\S+\s+(\d+)/', $checkProcess->getOutput(), $matches);
                $pid = $matches[1] ?? '-';
            }

            $rows[] = [$config['name'], $status, $pid];
        }

        $this->table($headers, $rows);
    }

    public function __destruct()
    {
        // Only stop services if we're explicitly shutting down
        if ($this->isConsoleCommand && $this->isShuttingDown) {
            try {
                Log::info('WebRTC Service Manager shutting down');
                $this->stopServices();
                Log::info('WebRTC Service Manager shutdown complete');
            } catch (\Exception $e) {
                Log::error('Error during service manager shutdown', [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
    }
} 