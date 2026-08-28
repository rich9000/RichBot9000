<?php

namespace App\WebSocket\Handlers;

use App\WebSocket\Handlers\Base\BaseConnectionHandler;
use Swoole\WebSocket\Server;
use Illuminate\Support\Facades\Log;

class MonitorConnectionHandler extends BaseConnectionHandler
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
            Log::error("[MONITOR] Missing API token", ['fd' => $fd]);
            $server->close($fd);
            return;
        }

        // Join room
        $this->joinRoom($fd, $room, ['name' => $room]);

        Log::info("[MONITOR] Monitor client connected", [
            'fd' => $fd,
            'room' => $room
        ]);
    }

    public function handleMessage(Server $server, int $fd, array $message): void
    {
        $client = $this->tables['clients']->get($fd);
        if (!$client) {
            Log::error("[MONITOR] Unknown client", ['fd' => $fd]);
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

            default:
                Log::warning("[MONITOR] Unknown message type", [
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
            Log::info("[MONITOR] Monitor client disconnected", [
                'fd' => $fd,
                'room' => $client['room']
            ]);
        }
    }

    protected function getConnectionType(): string
    {
        return 'monitor';
    }
} 