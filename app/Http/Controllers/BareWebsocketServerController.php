<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class BareWebsocketServerController extends Controller
{
    public function status(): JsonResponse
    {
        return response()->json($this->buildStatus());
    }

    public function start(): JsonResponse
    {
        $status = $this->buildStatus();

        if ($status['server']['running']) {
            return response()->json(array_merge($status, [
                'success' => true,
                'message' => 'bare:server is already running',
            ]));
        }

        if (!empty($status['serverv2']['running'])) {
            return response()->json(array_merge($status, [
                'success' => false,
                'message' => 'bare:serverv2 is occupying port 9502. Stop it before starting bare:server.',
            ]), 409);
        }

        $basePath = rtrim(config('app.base_path') ?: base_path(), '/');
        $logFile = storage_path('logs/bare_server.log');
        $command = sprintf(
            'nohup /usr/bin/php %s/artisan bare:server >> %s 2>&1 & echo $!',
            $basePath,
            escapeshellarg($logFile)
        );

        $output = [];
        $exitCode = 0;
        exec($command, $output, $exitCode);

        Log::info('[BARE SERVER] Start requested', [
            'command' => $command,
            'output' => $output,
            'exit_code' => $exitCode,
        ]);

        usleep(800000);
        $status = $this->buildStatus();

        if (!$status['server']['running']) {
            return response()->json(array_merge($status, [
                'success' => false,
                'message' => 'Failed to start bare:server. Check storage/logs/bare_server.log.',
            ]), 500);
        }

        return response()->json(array_merge($status, [
            'success' => true,
            'message' => 'bare:server started',
        ]));
    }

    public function stop(): JsonResponse
    {
        $status = $this->buildStatus();

        if (!$status['server']['running'] && empty($status['server']['processes'])) {
            return response()->json(array_merge($status, [
                'success' => true,
                'message' => 'bare:server is already stopped',
            ]));
        }

        $this->stopProcesses($status['server']['processes']);
        usleep(500000);
        $status = $this->buildStatus();

        if ($status['server']['running']) {
            $this->stopProcesses($status['server']['processes'], true);
            usleep(300000);
            $status = $this->buildStatus();
        }

        Log::info('[BARE SERVER] Stop requested', [
            'still_running' => $status['server']['running'],
        ]);

        return response()->json(array_merge($status, [
            'success' => !$status['server']['running'],
            'message' => $status['server']['running']
                ? 'bare:server did not stop'
                : 'bare:server stopped',
        ]), $status['server']['running'] ? 500 : 200);
    }

    public function restart(): JsonResponse
    {
        $this->stop();
        sleep(1);
        return $this->start();
    }

    private function buildStatus(): array
    {
        $serverProcesses = $this->findProcesses('bare:server', ['bare:serverv2']);
        $serverV2Processes = $this->findProcesses('bare:serverv2');
        $relayProcesses = $this->findProcesses('bare:assistant-v2');
        $legacyRelays = $this->findProcesses('bare:assistant', ['bare:assistant-v2']);

        return [
            'success' => true,
            'server' => [
                'name' => 'BareWebsocketServer',
                'command' => 'bare:server',
                'running' => !empty($serverProcesses),
                'pid' => $serverProcesses[0]['pid'] ?? null,
                'uptime' => $serverProcesses[0]['etime'] ?? null,
                'cmdline' => $serverProcesses[0]['cmd'] ?? null,
                'processes' => $serverProcesses,
                'log_file' => storage_path('logs/bare_server.log'),
            ],
            'serverv2' => [
                'name' => 'BareWebsocketServerV2',
                'command' => 'bare:serverv2',
                'running' => !empty($serverV2Processes),
                'processes' => $serverV2Processes,
            ],
            'legacy_server' => [
                'name' => 'BareWebsocketServer',
                'command' => 'bare:server',
                'running' => !empty($serverProcesses),
                'processes' => $serverProcesses,
            ],
            'relays' => array_map(function (array $process) {
                $process['parsed'] = $this->parseRelayCommand($process['cmd'] ?? '');
                return $process;
            }, $relayProcesses),
            'legacy_relays' => $legacyRelays,
            'config' => [
                'version' => 'bare:server + bare:assistant-v2',
                'relay_command' => 'bare:assistant-v2',
                'host' => '0.0.0.0',
                'port' => config('app.ws_port_alt'),
                'domain' => config('app.domain'),
                'url' => 'wss://' . config('app.domain') . ':' . config('app.ws_port_alt'),
                'protocol' => 'WSS',
                'ssl_cert' => config('app.ssl_cert_file'),
                'ssl_key' => config('app.ssl_key_file'),
                'connect_paths' => [
                    'dashboard' => '/dashboard/{room}?token={api_token}',
                    'webclient' => '/webclient/{assistant_id}?token={api_token}',
                    'openai' => '/openai/{room}/{assistant_id}',
                    'openai-realtime' => '/openai-realtime/{room}/{assistant_id}',
                    'twilio-inbound' => '/twilio-inbound/{room}/{call_sid}',
                    'twilio' => '/twilio/{room}/{call_sid}',
                    'monitor' => '/monitor/{room}',
                ],
                'message_types' => [
                    'request_server_data',
                    'get_all_clients',
                    'get_all_rooms',
                ],
            ],
        ];
    }

    private function findProcesses(string $needle, array $exclude = []): array
    {
        $process = Process::fromShellCommandline('ps -eo pid,etime,lstart,cmd --no-headers');
        $process->setTimeout(5);
        $process->run();

        if (!$process->isSuccessful()) {
            return [];
        }

        $matches = [];
        foreach (preg_split('/\r?\n/', trim($process->getOutput())) as $line) {
            if ($line === '' || !str_contains($line, $needle)) {
                continue;
            }

            $skip = false;
            foreach ($exclude as $excluded) {
                if (str_contains($line, $excluded)) {
                    $skip = true;
                    break;
                }
            }
            if ($skip) {
                continue;
            }

            if (!preg_match('/^\s*(\d+)\s+(\S+)\s+(.+?\d{4})\s+(.+)$/', $line, $parts)) {
                continue;
            }

            $matches[] = [
                'pid' => (int) $parts[1],
                'etime' => $parts[2],
                'started' => trim($parts[3]),
                'cmd' => trim($parts[4]),
            ];
        }

        return $matches;
    }

    private function parseRelayCommand(string $cmd): array
    {
        $room = null;
        $assistantId = null;
        $conversationId = null;

        if (preg_match('/bare:assistant-v2\s+(\S+)\s+(\S+)/', $cmd, $matches)) {
            $room = $matches[1];
            $assistantId = $matches[2];
        }
        if (preg_match('/--conversation_id=(\S+)/', $cmd, $matches)) {
            $conversationId = $matches[1];
        }

        return [
            'room' => $room,
            'assistant_id' => $assistantId,
            'conversation_id' => $conversationId,
        ];
    }

    private function stopProcesses(array $processes, bool $force = false): void
    {
        $signal = $force ? 9 : 15;
        foreach ($processes as $process) {
            $pid = (int) ($process['pid'] ?? 0);
            if ($pid > 1) {
                Process::fromShellCommandline("kill -{$signal} {$pid}")->run();
            }
        }
    }
}
