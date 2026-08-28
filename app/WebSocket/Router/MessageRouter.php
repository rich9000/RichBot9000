<?php

namespace App\WebSocket\Router;

use App\WebSocket\Messages\Interfaces\MessageHandlerInterface;
use Swoole\WebSocket\Server;
use Illuminate\Support\Facades\Log;

class MessageRouter
{
    private $handlers = [];
    private $tables;

    public function __construct(array $tables)
    {
        $this->tables = $tables;
    }

    public function registerHandler(string $type, MessageHandlerInterface $handler): void
    {
        $this->handlers[$type] = $handler;
    }

    public function getHandler(string $type): ?MessageHandlerInterface
    {
        return $this->handlers[$type] ?? null;
    }

    public function route(Server $server, int $fd, array $message): void
    {
        $type = $message['type'] ?? null;
        
        if (!$type) {
            Log::error("[ROUTER] Message missing type", ['message' => $message]);
            return;
        }

        if (isset($this->handlers[$type])) {
            $handler = $this->handlers[$type];
            $handler->handle($server, $fd, $message);
        } else {
            Log::error("[ROUTER] Unknown message type", ['type' => $type]);
        }
    }

    public function broadcastToRoom(Server $server, string $room, array $message, ?int $excludeFd = null): void
    {
        $roomData = $this->tables['rooms']->get($room);
        if (!$roomData) {
            return;
        }

        $clients = explode(',', $roomData['clients']);
        foreach ($clients as $fd) {
            if ($fd && $fd != $excludeFd) {
                $this->route($server, (int)$fd, $message);
            }
        }
    }
} 