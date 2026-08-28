<?php

namespace App\WebSocket\Messages;

use App\WebSocket\Messages\Interfaces\MessageHandlerInterface;
use Swoole\WebSocket\Server;
use Illuminate\Support\Facades\Log;

class SystemMessageHandler implements MessageHandlerInterface
{
    protected array $tables;

    public function __construct(array $tables)
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
            Log::error("[SYSTEM] Unknown client", ['fd' => $fd]);
            return;
        }

        // Validate system message
        if (!isset($message['action'])) {
            Log::error("[SYSTEM] Missing action", ['fd' => $fd]);
            return;
        }

        // Broadcast system message to room
        $server->push($fd, json_encode([
            'type' => 'system',
            'action' => $message['action'],
            'data' => $message['data'] ?? [],
            'source' => $message['source'] ?? 'system'
        ]));

        Log::info("[SYSTEM] System message handled", [
            'fd' => $fd,
            'action' => $message['action'],
            'source' => $message['source'] ?? 'system'
        ]);
    }
} 