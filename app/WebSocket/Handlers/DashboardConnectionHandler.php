<?php

namespace App\WebSocket\Handlers;

use App\WebSocket\Handlers\Base\BaseConnectionHandler;
use Swoole\WebSocket\Server;
use Illuminate\Support\Facades\Log;

class DashboardConnectionHandler extends BaseConnectionHandler
{
    public function handleConnection(Server $server, int $fd, array $params = []): void
    {
        if (!$this->validateConnection($params)) {
            $server->close($fd);
            return;
        }

        $room = $params['room'];
        $apiToken = $params['api_token'];

        // Validate API token
        if (!$apiToken) {
            Log::error("[DASHBOARD] Missing API token", ['fd' => $fd]);
            $server->close($fd);
            return;
        }

        // Join room
        $this->joinRoom($fd, $room, ['name' => $room]);

        Log::info("[DASHBOARD] Dashboard client connected", [
            'fd' => $fd,
            'room' => $room
        ]);
    }

    public function handleMessage(Server $server, int $fd, array $message): void
    {
        $client = $this->tables['clients']->get($fd);
        if (!$client) {
            Log::error("[DASHBOARD] Unknown client", ['fd' => $fd]);
            return;
        }

        $room = $client['room'];
        
        // Handle different message types
        switch ($message['type']) {
            case 'get_room_status':
                $roomData = $this->tables['rooms']->get($room);
                $clients = $this->tables['clients']->get($fd);
                
                $this->server->push($fd, json_encode([
                    'type' => 'room_status',
                    'room' => $room,
                    'clients' => $clients,
                    'room_data' => $roomData
                ]));
                break;

            case 'get_all_rooms':
                $rooms = [];
                foreach ($this->tables['rooms'] as $roomId => $roomData) {
                    $rooms[$roomId] = $roomData;
                }
                
                $this->server->push($fd, json_encode([
                    'type' => 'all_rooms',
                    'rooms' => $rooms
                ]));
                break;

            case 'get_all_clients':
                $clients = [];
                foreach ($this->tables['clients'] as $clientId => $clientData) {
                    $clients[$clientId] = $clientData;
                }
                
                $this->server->push($fd, json_encode([
                    'type' => 'all_clients',
                    'clients' => $clients
                ]));
                break;

            case 'control':
                if (isset($message['action'])) {
                    $this->broadcastToRoom($room, [
                        'type' => 'control',
                        'action' => $message['action'],
                        'params' => $message['params'] ?? [],
                        'source' => 'dashboard'
                    ]);
                }
                break;

            case 'broadcast':
                if (isset($message['content'])) {
                    $this->broadcastToRoom($room, [
                        'type' => 'broadcast',
                        'content' => $message['content'],
                        'source' => 'dashboard'
                    ]);
                }
                break;

            default:
                Log::warning("[DASHBOARD] Unknown message type", [
                    'type' => $message['type'],
                    'fd' => $fd
                ]);
        }
    }

    public function handleClose(Server $server, int $fd): void
    {
        $client = $this->tables['clients']->get($fd);
        if ($client) {
            $this->leaveRoom($fd);
            Log::info("[DASHBOARD] Dashboard client disconnected", [
                'fd' => $fd,
                'room' => $client['room']
            ]);
        }
    }

    protected function getConnectionType(): string
    {
        return 'dashboard';
    }
} 