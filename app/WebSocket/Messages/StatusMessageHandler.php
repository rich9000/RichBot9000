<?php

namespace App\WebSocket\Messages;

use App\WebSocket\Messages\Interfaces\MessageHandlerInterface;
use Swoole\WebSocket\Server;
use Illuminate\Support\Facades\Log;

class StatusMessageHandler implements MessageHandlerInterface
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
            Log::error("[STATUS] Unknown client", ['fd' => $fd]);
            return;
        }

        // Validate status message
        if (!isset($message['status'])) {
            Log::error("[STATUS] Missing status", ['fd' => $fd]);
            return;
        }

        // Broadcast status to room
        $server->push($fd, json_encode([
            'type' => 'status',
            'status' => $message['status'],
            'details' => $message['details'] ?? [],
            'source' => $message['source'] ?? 'unknown'
        ]));

        Log::info("[STATUS] Status message handled", [
            'fd' => $fd,
            'status' => $message['status'],
            'source' => $message['source'] ?? 'unknown'
        ]);
    }
} 