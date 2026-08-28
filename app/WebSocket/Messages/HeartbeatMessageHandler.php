<?php

namespace App\WebSocket\Messages;

use App\WebSocket\Messages\Interfaces\MessageHandlerInterface;
use Swoole\WebSocket\Server;
use Illuminate\Support\Facades\Log;

class HeartbeatMessageHandler implements MessageHandlerInterface
{
    private $tables;

    public function __construct(array $tables = [])
    {
        $this->tables = $tables;
    }

    public function handle(Server $server, int $fd, array $message): void
    {
        $client = null;
        if (!empty($this->tables) && isset($this->tables['clients'])) {
            $client = $this->tables['clients']->get($fd);
        }
        
        if (!$client && !empty($this->tables)) {
            Log::error("[HEARTBEAT] Unknown client", ['fd' => $fd]);
            return;
        }

        // Send heartbeat response
        $server->push($fd, json_encode([
            'type' => 'heartbeat',
            'timestamp' => time(),
            'status' => 'ok'
        ]));

        Log::debug("[HEARTBEAT] Heartbeat handled", [
            'fd' => $fd,
            'timestamp' => time()
        ]);
    }
} 