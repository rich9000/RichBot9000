<?php

namespace App\WebSocket\Tables;

use Swoole\Table;
use Illuminate\Support\Facades\Log;

class TableManager
{
    private array $tables = [];

    public function initialize(): void
    {
        // Clients table - stores all connected clients (users and assistants)
        $clients = new Table(1024);
        $clients->column('type', Table::TYPE_STRING, 32);      // 'user', 'openai', 'twilio', etc.
        $clients->column('room', Table::TYPE_STRING, 64);      // Room ID
        $clients->column('user_id', Table::TYPE_STRING, 64);   // User ID (null for assistants)
        $clients->column('name', Table::TYPE_STRING, 64);      // Unique name/identifier for the client
        $clients->column('assistant_id', Table::TYPE_STRING, 64); // Assistant ID (for OpenAI connections)
        $clients->column('connected_at', Table::TYPE_INT);
        $clients->column('joined_at', Table::TYPE_INT);
        $clients->column('stream_sid', Table::TYPE_STRING, 64);
        $clients->column('call_sid', Table::TYPE_STRING, 64);
        $clients->column('status', Table::TYPE_STRING, 32);
        $clients->create();
        $this->tables['clients'] = $clients;

        // Rooms table - stores room information
        $rooms = new Table(256);
        $rooms->column('owner_fd', Table::TYPE_INT);           // File descriptor of the user who owns the room
        $rooms->column('created_at', Table::TYPE_INT);
        $rooms->column('last_activity', Table::TYPE_INT);
        $rooms->column('status', Table::TYPE_STRING, 32);      // 'active', 'ended', etc.
        $rooms->column('clients', Table::TYPE_STRING, 2048);   // JSON list of client FDs
        $rooms->create();
        $this->tables['rooms'] = $rooms;

        Log::info("[TABLES] Initialized Swoole tables");
    }

    public function getTables(): array
    {
        return $this->tables;
    }

    public function getClient(int $fd): ?array
    {
        return $this->tables['clients']->get($fd);
    }

    public function getClientByName(string $name): ?array
    {
        foreach ($this->tables['clients'] as $fd => $client) {
            if ($client['name'] === $name) {
                return ['fd' => $fd] + $client;
            }
        }
        return null;
    }

    public function getRoom(string $roomId): ?array
    {
        return $this->tables['rooms']->get($roomId);
    }

    public function getRoomOwner(string $roomId): ?array
    {
        $room = $this->getRoom($roomId);
        if (!$room) {
            return null;
        }
        return $this->getClient($room['owner_fd']);
    }

    public function getRoomAssistants(string $roomId): array
    {
        $assistants = [];
        foreach ($this->tables['clients'] as $fd => $client) {
            if ($client['room'] === $roomId && $client['type'] === 'openai') {
                $assistants[$fd] = $client;
            }
        }
        return $assistants;
    }

    public function getRoomUsers(string $roomId): array
    {
        $users = [];
        foreach ($this->tables['clients'] as $fd => $client) {
            if ($client['room'] === $roomId && $client['type'] === 'user') {
                $users[$fd] = $client;
            }
        }
        return $users;
    }

    public function createRoom(string $roomId, int $ownerFd): void
    {
        $this->tables['rooms']->set($roomId, [
            'owner_fd' => $ownerFd,
            'created_at' => time(),
            'last_activity' => time(),
            'status' => 'active',
            'clients' => '[]',
        ]);
    }

    public function endRoom(string $roomId): void
    {
        $room = $this->getRoom($roomId);
        if ($room) {
            $room['status'] = 'ended';
            $this->tables['rooms']->set($roomId, $room);
        }
    }

    public function addClient(int $fd, array $data): void
    {
        $this->tables['clients']->set($fd, array_merge([
            'connected_at' => time()
        ], $data));
    }

    public function removeClient(int $fd): void
    {
        $this->tables['clients']->del($fd);
    }

    public function kickClient(string $name): bool
    {
        $client = $this->getClientByName($name);
        if ($client) {
            $this->server->close($client['fd']);
            $this->removeClient($client['fd']);
            return true;
        }
        return false;
    }
} 