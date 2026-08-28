<?php

namespace App\WebSocket\Handlers;

use App\WebSocket\Handlers\Base\BaseConnectionHandler;
use Swoole\WebSocket\Server;
use Illuminate\Support\Facades\Log;

class RemoteRichbotConnectionHandler extends BaseConnectionHandler
{
    public function handleConnection(Server $server, int $fd, array $params = []): void
    {
        if (!$this->validateConnection($params)) {
            $server->close($fd);
            return;
        }

        $room = $params['room'];
        $assistantId = $params['assistant_id'];
        $apiToken = $params['api_token'];

        // Validate API token
        if (!$apiToken) {
            Log::error("[RICHBOT] Missing API token", ['fd' => $fd]);
            $server->close($fd);
            return;
        }

        // Join room
        $this->joinRoom($fd, $room, [
            'name' => $room,
            'assistant_id' => $assistantId,
        ]);

        Log::info("[RICHBOT] Remote richbot connected", [
            'fd' => $fd,
            'room' => $room,
            'assistant_id' => $assistantId
        ]);
    }

    public function handleMessage(Server $server, int $fd, array $message): void
    {
        $client = $this->tables['clients']->get($fd);
        if (!$client) {
            Log::error("[RICHBOT] Unknown client", ['fd' => $fd]);
            return;
        }

        $room = $client['room'];
        
        // Handle different message types
        switch ($message['type']) {
            case 'text':
                $this->broadcastToRoom($room, [
                    'type' => 'text',
                    'content' => $message['content'],
                    'source' => 'richbot'
                ], $fd);
                break;

            case 'media':
                $this->broadcastToRoom($room, [
                    'type' => 'media',
                    'data' => $message['data'],
                    'source' => 'richbot'
                ], $fd);
                break;

            case 'status':
                $this->broadcastToRoom($room, [
                    'type' => 'status',
                    'status' => $message['status'],
                    'source' => 'richbot'
                ], $fd);
                break;

            default:
                Log::warning("[RICHBOT] Unknown message type", [
                    'type' => $message['type'],
                    'fd' => $fd
                ]);
        }
    }

    public function handleClose(Server $server, int $fd): void
    {
        $client = $this->tables['clients']->get($fd);
        if ($client) {
            $this->leaveRoom($server, $fd, $client['room']);
            Log::info("[RICHBOT] Remote richbot disconnected", [
                'fd' => $fd,
                'room' => $client['room']
            ]);
        }
    }

    public function getConnectionType(): string
    {
        return 'remote_richbot';
    }
} 