<?php

namespace App\WebSocket\Messages;

use App\WebSocket\Messages\Interfaces\MessageHandlerInterface;
use Swoole\WebSocket\Server;
use Illuminate\Support\Facades\Log;

class CommandMessageHandler implements MessageHandlerInterface
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
            Log::error("[COMMAND] Unknown client", ['fd' => $fd]);
            return;
        }

        // Validate command message
        if (!isset($message['command'])) {
            Log::error("[COMMAND] Missing command", ['fd' => $fd]);
            return;
        }

        // Broadcast command to room
        $server->push($fd, json_encode([
            'type' => 'command',
            'command' => $message['command'],
            'params' => $message['params'] ?? [],
            'target' => $message['target'] ?? 'all',
            'source' => $message['source'] ?? 'unknown'
        ]));

        Log::info("[COMMAND] Command message handled", [
            'fd' => $fd,
            'command' => $message['command'],
            'target' => $message['target'] ?? 'all',
            'source' => $message['source'] ?? 'unknown'
        ]);
    }
} 