<?php

namespace App\WebSocket\Messages;

use App\WebSocket\Messages\Interfaces\MessageHandlerInterface;
use Swoole\WebSocket\Server;
use Illuminate\Support\Facades\Log;

class ErrorMessageHandler implements MessageHandlerInterface
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
            Log::error("[ERROR] Unknown client", ['fd' => $fd]);
            return;
        }

        // Validate error message
        if (!isset($message['error'])) {
            Log::error("[ERROR] Missing error details", ['fd' => $fd]);
            return;
        }

        // Broadcast error to room
        $server->push($fd, json_encode([
            'type' => 'error',
            'error' => $message['error'],
            'code' => $message['code'] ?? 'unknown',
            'details' => $message['details'] ?? [],
            'source' => $message['source'] ?? 'unknown'
        ]));

        Log::error("[ERROR] Error message handled", [
            'fd' => $fd,
            'error' => $message['error'],
            'code' => $message['code'] ?? 'unknown',
            'source' => $message['source'] ?? 'unknown'
        ]);
    }
} 