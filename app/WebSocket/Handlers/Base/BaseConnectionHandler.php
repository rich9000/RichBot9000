<?php

namespace App\WebSocket\Handlers\Base;

use App\WebSocket\Handlers\Interfaces\ConnectionHandlerInterface;
use Swoole\WebSocket\Server;
use Swoole\Table;
use Illuminate\Support\Facades\Log;

abstract class BaseConnectionHandler implements ConnectionHandlerInterface
{
    protected $tables;
    protected $server;
    
    public function __construct(array $tables, Server $server) 
    {
        $this->tables = $tables;
        $this->server = $server;
    }

    protected function validateConnection(array $params): bool 
    {
        if (empty($params['room'])) {
            Log::error("[CONNECTION] Missing room parameter", $params);
            return false;
        }
        return true;
    }

    protected function joinRoom(int $fd, string $room, array $extra = []): void 
    {
        $existing = $this->tables['rooms']->get($room) ?: [
            'owner_fd' => $fd,
            'created_at' => time(),
            'last_activity' => time(),
            'status' => 'active',
            'clients' => '[]',
        ];

        $clients = json_decode($existing['clients'] ?? '[]', true);
        if (!is_array($clients)) {
            $clients = [];
        }
        if (!in_array($fd, $clients, true)) {
            $clients[] = $fd;
        }

        $existing['clients'] = json_encode(array_values($clients));
        $existing['last_activity'] = time();
        if (empty($existing['status'])) {
            $existing['status'] = 'active';
        }
        if (empty($existing['created_at'])) {
            $existing['created_at'] = time();
        }
        if (empty($existing['owner_fd'])) {
            $existing['owner_fd'] = $fd;
        }
        $this->tables['rooms']->set($room, $existing);

        $clientData = $this->tables['clients']->get($fd) ?? [];
        $clientData['room'] = $room;
        $clientData['type'] = $this->getConnectionType();
        $clientData['joined_at'] = time();
        $clientData['connected_at'] = !empty($clientData['connected_at']) ? $clientData['connected_at'] : time();
        $clientData['status'] = 'connected';
        if (empty($clientData['name'])) {
            $clientData['name'] = $extra['name'] ?? $room;
        }
        foreach (['assistant_id', 'stream_sid', 'call_sid', 'user_id'] as $field) {
            if (!empty($extra[$field])) {
                $clientData[$field] = (string) $extra[$field];
            }
        }
        $this->tables['clients']->set($fd, $clientData);

        Log::info("[CONNECTION] Client joined room", [
            'fd' => $fd,
            'room' => $room,
            'type' => $this->getConnectionType()
        ]);
    }

    protected function leaveRoom(int $fd): void 
    {
        $client = $this->tables['clients']->get($fd);
        if (!$client) return;

        $room = $client['room'];
        $existing = $this->tables['rooms']->get($room);
        if ($existing) {
            $clients = json_decode($existing['clients'] ?? '[]', true);
            if (!is_array($clients)) {
                $clients = [];
            }
            $existing['clients'] = json_encode(array_values(array_diff($clients, [$fd])));
            $existing['last_activity'] = time();
            $this->tables['rooms']->set($room, $existing);
        }

        $this->tables['clients']->del($fd);

        Log::info("[CONNECTION] Client left room", [
            'fd' => $fd,
            'room' => $room,
            'type' => $this->getConnectionType()
        ]);
    }

    protected function broadcastToRoom(string $room, array $message): void 
    {
        $clients = $this->tables['rooms']->get($room, 'clients') ?? '[]';
        $clients = json_decode($clients, true);

        foreach ($clients as $fd) {
            if ($this->server->isEstablished($fd)) {
                $this->server->push($fd, json_encode($message));
            }
        }
    }

    abstract protected function getConnectionType(): string;
} 