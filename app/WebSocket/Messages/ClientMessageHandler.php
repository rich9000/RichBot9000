<?php

namespace App\WebSocket\Messages;

use App\WebSocket\Messages\Interfaces\MessageHandlerInterface;
use Swoole\WebSocket\Server;
use Illuminate\Support\Facades\Log;

class ClientMessageHandler implements MessageHandlerInterface
{
    private $tables;
    private $server;

    public function __construct(array $tables, Server $server)
    {
        $this->tables = $tables;
        $this->server = $server;
    }

    public function handle(Server $server, int $fd, array $message): void
    {
        $client = $this->tables['clients']->get($fd);
        if (!$client) {
            Log::error("[CLIENT] Unknown client", ['fd' => $fd]);
            return;
        }

        $messageType = $message['type'] ?? null;
        
        switch ($messageType) {
            case 'get_all_clients':
                $this->handleGetAllClients($server, $fd);
                break;
            case 'get_all_rooms':
                $this->handleGetAllRooms($server, $fd);
                break;
            case 'get_room_status':
                $this->handleGetRoomStatus($server, $fd, $message);
                break;
            default:
                Log::warning("[CLIENT] Unknown message type", [
                    'type' => $messageType,
                    'fd' => $fd
                ]);
        }
    }

    private function handleTextMessage(int $fd, array $message, array $assistants): void
    {
        $client = $this->tables['clients']->get($fd);
        
        // Forward text message to all assistants
        foreach ($assistants as $assistantFd => $assistant) {
            $this->server->push($assistantFd, json_encode([
                'type' => 'text',
                'content' => $message['content'],
                'sender' => [
                    'fd' => $fd,
                    'name' => $client['name'],
                    'user_id' => $client['user_id']
                ]
            ]));
        }

        Log::info("[CLIENT] Text message forwarded", [
            'fd' => $fd,
            'content_length' => strlen($message['content']),
            'assistants' => array_keys($assistants)
        ]);
    }

    private function handleAudioMessage(int $fd, array $message, array $assistants): void
    {
        $client = $this->tables['clients']->get($fd);
        
        // Forward audio message to all assistants
        foreach ($assistants as $assistantFd => $assistant) {
            $this->server->push($assistantFd, json_encode([
                'type' => 'audio',
                'data' => $message['data'],
                'format' => $message['format'] ?? 'wav',
                'sender' => [
                    'fd' => $fd,
                    'name' => $client['name'],
                    'user_id' => $client['user_id']
                ]
            ]));
        }

        Log::info("[CLIENT] Audio message forwarded", [
            'fd' => $fd,
            'data_length' => strlen($message['data']),
            'format' => $message['format'] ?? 'wav',
            'assistants' => array_keys($assistants)
        ]);
    }

    private function handleCommandMessage(int $fd, array $message, array $assistants): void
    {
        $client = $this->tables['clients']->get($fd);
        
        // Forward command to all assistants
        foreach ($assistants as $assistantFd => $assistant) {
            $this->server->push($assistantFd, json_encode([
                'type' => 'command',
                'command' => $message['command'],
                'params' => $message['params'] ?? [],
                'sender' => [
                    'fd' => $fd,
                    'name' => $client['name'],
                    'user_id' => $client['user_id']
                ]
            ]));
        }

        Log::info("[CLIENT] Command message forwarded", [
            'fd' => $fd,
            'command' => $message['command'],
            'assistants' => array_keys($assistants)
        ]);
    }

    private function handleGetAllClients(Server $server, int $fd): void
    {
        $clients = [];
        
        // Convert table to array format
        foreach ($this->tables['clients'] as $clientFd => $clientData) {
            $clients[$clientFd] = $clientData;
        }

        $response = [
            'type' => 'all_clients',
            'clients' => $clients,
            'timestamp' => time()
        ];

        $server->push($fd, json_encode($response));
        
        Log::info("[CLIENT] Sent all clients data", [
            'requesting_fd' => $fd,
            'client_count' => count($clients)
        ]);
    }

    private function handleGetAllRooms(Server $server, int $fd): void
    {
        $rooms = [];
        
        // Convert table to array format
        foreach ($this->tables['rooms'] as $roomName => $roomData) {
            $rooms[$roomName] = $roomData;
        }

        $response = [
            'type' => 'all_rooms',
            'rooms' => $rooms,
            'timestamp' => time()
        ];

        $server->push($fd, json_encode($response));
        
        Log::info("[CLIENT] Sent all rooms data", [
            'requesting_fd' => $fd,
            'room_count' => count($rooms)
        ]);
    }

    private function handleGetRoomStatus(Server $server, int $fd, array $message): void
    {
        $roomName = $message['room'] ?? null;
        
        if (!$roomName) {
            Log::error("[CLIENT] Missing room name for room status request", ['fd' => $fd]);
            $server->push($fd, json_encode([
                'type' => 'error',
                'error' => 'Missing room name',
                'source' => 'server'
            ]));
            return;
        }

        $roomData = $this->tables['rooms']->get($roomName);
        
        if (!$roomData) {
            Log::warning("[CLIENT] Room not found", ['fd' => $fd, 'room' => $roomName]);
            $server->push($fd, json_encode([
                'type' => 'room_status',
                'room' => $roomName,
                'found' => false,
                'error' => 'Room not found'
            ]));
            return;
        }

        // Get detailed client information for this room
        $clients = [];
        try {
            $clientList = json_decode($roomData['clients'] ?? '[]', true);
            foreach ($clientList as $clientFd) {
                $clientData = $this->tables['clients']->get($clientFd);
                if ($clientData) {
                    $clients[$clientFd] = $clientData;
                }
            }
        } catch (\Exception $e) {
            Log::error("[CLIENT] Error parsing room clients", [
                'room' => $roomName,
                'error' => $e->getMessage()
            ]);
        }

        $response = [
            'type' => 'room_status',
            'room' => $roomName,
            'found' => true,
            'room_data' => $roomData,
            'clients' => $clients,
            'client_count' => count($clients),
            'timestamp' => time()
        ];

        $server->push($fd, json_encode($response));
        
        Log::info("[CLIENT] Sent room status", [
            'requesting_fd' => $fd,
            'room' => $roomName,
            'client_count' => count($clients)
        ]);
    }
} 