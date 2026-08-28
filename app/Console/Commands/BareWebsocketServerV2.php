<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Swoole\WebSocket\Server;
use Swoole\Table;
use Illuminate\Support\Facades\Log;
use App\WebSocket\Tables\TableManager;
use App\WebSocket\Handlers\WebClientConnectionHandler;
use App\WebSocket\Handlers\TwilioConnectionHandler;
use App\WebSocket\Handlers\OpenAIConnectionHandler;
use App\WebSocket\Handlers\MonitorConnectionHandler;
use App\WebSocket\Handlers\DashboardConnectionHandler;
use App\WebSocket\Handlers\RemoteRichbotConnectionHandler;
use App\WebSocket\Messages\MediaMessageHandler;
use App\WebSocket\Messages\TextMessageHandler;
use App\WebSocket\Messages\DtmfMessageHandler;
use App\WebSocket\Messages\ControlMessageHandler;
use App\WebSocket\Messages\StatusMessageHandler;
use App\WebSocket\Messages\ErrorMessageHandler;
use App\WebSocket\Messages\SystemMessageHandler;
use App\WebSocket\Messages\CommandMessageHandler;
use App\WebSocket\Messages\HeartbeatMessageHandler;
use App\WebSocket\Messages\ClientMessageHandler;
use App\WebSocket\Messages\BroadcastMessageHandler;
use App\WebSocket\Router\MessageRouter;
use App\WebSocket\Router\ConnectionRouter;

class BareWebsocketServerV2 extends Command
{
    protected $signature = 'bare:serverv2 {--host=0.0.0.0} {--port=9502} {--worker-num=4} {--task-worker-num=2} {--daemonize}';
    protected $description = 'Start the Bare WebSocket server V2';

    private $server;
    private $tableManager;
    private $connectionRouter;
    private $messageRouter;
    private $connectionHandlers = [];
    private $messageHandlers = [];

    public function handle()
    {
        $this->info('Starting Bare WebSocket Server V2...');

        // Initialize server configuration
        $config = [   
            'worker_num' => $this->option('worker-num',4),
            'task_worker_num' => $this->option('task-worker-num',2),
            'daemonize' => $this->option('daemonize',false),
            'log_level' => SWOOLE_LOG_INFO,
            'log_file' => storage_path('logs/swoole.log'),
            'pid_file' => storage_path('logs/swoole.pid'),
            'max_request' => 10000,
            'max_conn' => 10000,
            'buffer_output_size' => 2 * 1024 * 1024,
            'package_max_length' => 10 * 1024 * 1024,
            'heartbeat_check_interval' => 30,
            'heartbeat_idle_time' => 60,
            'enable_reuse_port' => true,
            'enable_coroutine' => true,
            'max_coroutine' => 3000,
            'hook_flags' => SWOOLE_HOOK_ALL,
            // SSL Configuration for WSS support  
            'ssl_cert_file' => config('app.ssl_cert_file'),
            'ssl_key_file' => config('app.ssl_key_file'),
            'ssl_verify_peer' => false,
            'ssl_allow_self_signed' => true,
            'ssl_protocols' => SWOOLE_SSL_TLSv1_2 | SWOOLE_SSL_TLSv1_3
        ];


        $this->info("Starting Bare WebSocket Server V2...");
        $this->info("Host: " . $this->option('host','0.0.0.0'));
        $this->info("Port: " . $this->option('port',9502));
        $this->info("Protocol: WSS (Secure WebSocket with SSL)");
        $this->info("SSL Cert: " . $config['ssl_cert_file']);
        $this->info("SSL Key: " . $config['ssl_key_file']);
        
        // Verify SSL certificate files exist
        if (!file_exists($config['ssl_cert_file'])) {
            $this->error("SSL certificate file not found: " . $config['ssl_cert_file']);
            return 1;
        }
        if (!file_exists($config['ssl_key_file'])) {
            $this->error("SSL key file not found: " . $config['ssl_key_file']);
            return 1;
        }
        $this->info("✅ SSL certificate files verified");
        
        $this->info("Worker Num: " . $config['worker_num']);
        $this->info("Task Worker Num: " . $config['task_worker_num']);
        $this->info("Daemonize: " . $config['daemonize']);
        $this->info("Log Level: " . $config['log_level']);
        $this->info("Log File: " . $config['log_file']);
        // Create server instance with SSL support
        $this->server = new Server($this->option('host','0.0.0.0'), $this->option('port',9502), SWOOLE_PROCESS, SWOOLE_SOCK_TCP | SWOOLE_SSL);

        // Set server configuration
        $this->server->set($config);

        // Initialize components
        $this->initializeComponents();

        // Register event handlers
        $this->registerEventHandlers();

        // Start server
        $this->server->start();
    }

    private function initializeComponents(): void
    {
        // Initialize table manager
        $this->tableManager = new TableManager();
        $this->tableManager->initialize();

        // Initialize routers
        $this->connectionRouter = new ConnectionRouter($this->tableManager->getTables());
        $this->messageRouter = new MessageRouter($this->tableManager->getTables());

        // Initialize connection handlers
        $this->connectionHandlers = [
            'webclient' => new WebClientConnectionHandler($this->tableManager->getTables(), $this->server),
            'twilio' => new TwilioConnectionHandler($this->tableManager->getTables(), $this->server),
            'openai' => new OpenAIConnectionHandler($this->tableManager->getTables(), $this->server),
            'monitor' => new MonitorConnectionHandler($this->tableManager->getTables(), $this->server),
            'dashboard' => new DashboardConnectionHandler($this->tableManager->getTables(), $this->server),
            'remote_richbot' => new RemoteRichbotConnectionHandler($this->tableManager->getTables(), $this->server)
        ];

        // Initialize message handlers
        $this->messageHandlers = [
            'client' => new ClientMessageHandler($this->tableManager->getTables(), $this->server),
            'media' => new MediaMessageHandler($this->tableManager->getTables()),
            'text' => new TextMessageHandler($this->tableManager->getTables()),
            'dtmf' => new DtmfMessageHandler($this->tableManager->getTables()),
            'control' => new ControlMessageHandler($this->tableManager->getTables()),
            'status' => new StatusMessageHandler($this->tableManager->getTables()),
            'error' => new ErrorMessageHandler($this->tableManager->getTables()),
            'system' => new SystemMessageHandler($this->tableManager->getTables()),
            'command' => new CommandMessageHandler($this->tableManager->getTables()),
            'heartbeat' => new HeartbeatMessageHandler($this->tableManager->getTables()),
            'broadcast' => new BroadcastMessageHandler($this->tableManager->getTables()),
            'get_all_clients' => new ClientMessageHandler($this->tableManager->getTables(), $this->server),
            'get_all_rooms' => new ClientMessageHandler($this->tableManager->getTables(), $this->server),
            'get_room_status' => new ClientMessageHandler($this->tableManager->getTables(), $this->server)
        ];

        // Register handlers with routers
        foreach ($this->connectionHandlers as $type => $handler) {
            $this->connectionRouter->registerHandler($type, $handler);
        }

        foreach ($this->messageHandlers as $type => $handler) {
            $this->messageRouter->registerHandler($type, $handler);
        }
    }

    private function registerEventHandlers(): void
    {
        // Manager process events
        $this->server->on('ManagerStart', function (Server $server) {
            Log::info("[MANAGER] Manager process started", [
                'pid' => $server->manager_pid
            ]);
        });

        $this->server->on('ManagerStop', function (Server $server) {
            Log::info("[MANAGER] Manager process stopped", [
                'pid' => $server->manager_pid
            ]);
        });

        // Worker process events
        $this->server->on('WorkerStart', function (Server $server, int $workerId) {
            Log::info("[WORKER] Worker process started", [
                'worker_id' => $workerId,
                'pid' => $server->worker_pid
            ]);
        });

        $this->server->on('WorkerStop', function (Server $server, int $workerId) {
            Log::info("[WORKER] Worker process stopped", [
                'worker_id' => $workerId,
                'pid' => $server->worker_pid
            ]);
        });

        $this->server->on('WorkerError', function (Server $server, int $workerId, int $workerPid, int $exitCode, int $signal) {
            Log::error("[WORKER] Worker process error", [
                'worker_id' => $workerId,
                'worker_pid' => $workerPid,
                'exit_code' => $exitCode,
                'signal' => $signal
            ]);
        });

        // Task worker events
        $this->server->on('Task', function (Server $server, int $taskId, int $workerId, $data) {
            Log::info("[TASK] Task received", [
                'task_id' => $taskId,
                'worker_id' => $workerId,
                'data' => $data
            ]);
            
            // Handle task
            $result = $this->handleTask($data);
            
            $server->finish($result);
        });

        $this->server->on('Finish', function (Server $server, int $taskId, $result) {
            Log::info("[TASK] Task finished", [
                'task_id' => $taskId,
                'result' => $result
            ]);
        });

        // WebSocket events
        $this->server->on('Open', function (Server $server, $request) {
            $this->handleOpen($server, $request);
        });

        $this->server->on('Message', function (Server $server, $frame) {
            $this->handleMessage($server, $frame);
        });

        $this->server->on('Close', function (Server $server, int $fd, int $reactorId) {
            $this->handleClose($server, $fd, $reactorId);
        });
    }

    private function handleOpen(Server $server, $request): void
    {
        $path = $request->server['request_uri'];
        $params = $this->parsePath($path);

        if (!$params) {
            Log::error("[SERVER] Invalid connection path", [
                'path' => $path,
                'fd' => $request->fd
            ]);
            $server->close($request->fd);
            return;
        }

        // Add API token from query parameters
        if (isset($request->get['token'])) {
            $params['api_token'] = $request->get['token'];
        }

        $handler = $this->connectionRouter->getHandler($params['type']);
        if ($handler) {
            $handler->handleConnection($server, $request->fd, $params);
        } else {
            Log::error("[SERVER] No handler found for connection type", [
                'type' => $params['type'],
                'fd' => $request->fd
            ]);
            $server->close($request->fd);
        }
    }

    private function handleMessage(Server $server, $frame): void
    {
        $message = json_decode($frame->data, true);
        if (!$message || !is_array($message)) {
            Log::error("[SERVER] Invalid message format", [
                'fd' => $frame->fd,
                'data' => $frame->data
            ]);
            return;
        }

        // Handle Twilio's event field format
        if (!isset($message['type']) && isset($message['event'])) {
            $message['type'] = $message['event'];
        }

        if (!isset($message['type'])) {
            Log::error("[SERVER] Message missing type field", [
                'fd' => $frame->fd,
                'message' => $message
            ]);
            return;
        }

        $handler = $this->messageRouter->getHandler($message['type']);
        if ($handler) {
            $handler->handle($server, $frame->fd, $message);
        } else {
            Log::warning("[SERVER] No handler found for message type", [
                'type' => $message['type'],
                'fd' => $frame->fd
            ]);
        }
    }

    private function handleClose(Server $server, int $fd, int $reactorId): void
    {
        $client = $this->tableManager->getTables()['clients']->get($fd);
        if ($client) {
            $handler = $this->connectionRouter->getHandler($client['type']);
            if ($handler) {
                $handler->handleClose($server, $fd);
            }
        }
    }

    private function handleTask($data)
    {
        // Implement task handling logic here
        return ['status' => 'success', 'data' => $data];
    }

    private function parsePath(string $path): ?array
    {
        $parts = explode('/', trim($path, '/'));
        if (count($parts) < 2) {
            return null;
        }

        $type = $parts[0];
        $room = $parts[1];
        $params = ['type' => $type, 'room' => $room];

        // Add additional parameters based on connection type
        switch ($type) {
            case 'webclient':
                if (count($parts) >= 3) {
                    $params['assistant_id'] = $parts[2];
                }
                break;
            case 'twilio':
                if (count($parts) >= 3) {
                    $params['call_sid'] = $parts[2];
                }
                break;
            case 'openai':
                if (count($parts) >= 3) {
                    $params['assistant_id'] = $parts[2];
                }
                break;
            case 'monitor':
            case 'dashboard':
                // No additional parameters needed
                break;
            case 'remote_richbot':
                if (count($parts) >= 3) {
                    $params['assistant_id'] = $parts[2];
                }
                break;
            default:
                return null;
        }

        return $params;
    }
} 