<?php

namespace App\WebSocket\Messages;

use App\WebSocket\Messages\Interfaces\MessageHandlerInterface;
use Swoole\WebSocket\Server;
use Illuminate\Support\Facades\Log;

class ControlMessageHandler implements MessageHandlerInterface
{
    private $tables;

    public function __construct(array $tables = [])
    {
        $this->tables = $tables;
    }

    public function handle(Server $server, int $fd, array $message): void
    {
        $client = null;
        if (!empty($this->tables) && isset($this->tables['clients'])) {
            $client = $this->tables['clients']->get($fd);
        }
        if (!$client) {
            Log::error("[CONTROL] Unknown client", ['fd' => $fd]);
            return;
        }

        // Validate control message
        if (!isset($message['action'])) {
            Log::error("[CONTROL] Missing action", ['fd' => $fd]);
            return;
        }

        $action = $message['action'];
        $params = $message['params'] ?? [];
        $source = $message['source'] ?? 'unknown';

        // Handle different control actions
        switch ($action) {
            case 'status_check':
                $this->handleStatusCheck($server, $fd, $params, $source);
                break;
            case 'health_check':
                $this->handleHealthCheck($server, $fd, $params, $source);
                break;
            case 'ping_all':
                $this->handlePingAll($server, $fd, $params, $source);
                break;
            default:
                Log::warning("[CONTROL] Unknown action", [
                    'action' => $action,
                    'fd' => $fd,
                    'source' => $source
                ]);
                // Send error response
                $server->push($fd, json_encode([
                    'type' => 'error',
                    'error' => 'Unknown control action',
                    'action' => $action,
                    'source' => 'server'
                ]));
                return;
        }

        Log::info("[CONTROL] Control action handled", [
            'fd' => $fd,
            'action' => $action,
            'source' => $source
        ]);
    }

    private function handleStatusCheck(Server $server, int $fd, array $params, string $source): void
    {
        // Get server stats
        $stats = [
            'server_time' => date('Y-m-d H:i:s'),
            'uptime' => time() - $_SERVER['REQUEST_TIME'],
            'total_connections' => count($this->tables['clients']),
            'active_rooms' => count($this->tables['rooms']),
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true)
        ];

        $server->push($fd, json_encode([
            'type' => 'control',
            'action' => 'status_check_response',
            'data' => $stats,
            'source' => 'server'
        ]));
    }

    private function handleHealthCheck(Server $server, int $fd, array $params, string $source): void
    {
        // Perform health checks
        $health = [
            'status' => 'healthy',
            'checks' => [
                'tables' => [
                    'clients' => $this->tables['clients'] ? 'ok' : 'error',
                    'rooms' => $this->tables['rooms'] ? 'ok' : 'error'
                ],
                'memory' => memory_get_usage(true) < (1024 * 1024 * 512) ? 'ok' : 'warning', // 512MB limit
                'connections' => count($this->tables['clients']) < 1000 ? 'ok' : 'warning'
            ],
            'timestamp' => time()
        ];

        $server->push($fd, json_encode([
            'type' => 'control',
            'action' => 'health_check_response',
            'data' => $health,
            'source' => 'server'
        ]));
    }

    private function handlePingAll(Server $server, int $fd, array $params, string $source): void
    {
        $pings = [];
        $timestamp = time();

        // Ping all connected clients
        foreach ($this->tables['clients'] as $clientFd => $clientData) {
            if ($clientFd != $fd) { // Don't ping the requester
                try {
                    $pingMessage = [
                        'type' => 'ping',
                        'timestamp' => $timestamp,
                        'from' => $source
                    ];
                    
                    $success = $server->push($clientFd, json_encode($pingMessage));
                    $pings[] = [
                        'fd' => $clientFd,
                        'type' => $clientData['type'] ?? 'unknown',
                        'status' => $success ? 'sent' : 'failed'
                    ];
                } catch (\Exception $e) {
                    $pings[] = [
                        'fd' => $clientFd,
                        'type' => $clientData['type'] ?? 'unknown',
                        'status' => 'error',
                        'error' => $e->getMessage()
                    ];
                }
            }
        }

        $server->push($fd, json_encode([
            'type' => 'control',
            'action' => 'ping_all_response',
            'data' => [
                'total_clients' => count($pings),
                'results' => $pings
            ],
            'source' => 'server'
        ]));
    }
} 