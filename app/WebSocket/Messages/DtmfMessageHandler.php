<?php

namespace App\WebSocket\Messages;

use App\WebSocket\Messages\Interfaces\MessageHandlerInterface;
use Swoole\WebSocket\Server;
use Illuminate\Support\Facades\Log;

class DtmfMessageHandler implements MessageHandlerInterface
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
            Log::error("[DTMF] Unknown client", ['fd' => $fd]);
            return;
        }

        // Validate DTMF message
        if (!isset($message['digit'])) {
            Log::error("[DTMF] Missing digit", ['fd' => $fd]);
            return;
        }

        // Broadcast DTMF to room
        $server->push($fd, json_encode([
            'type' => 'dtmf',
            'digit' => $message['digit'],
            'source' => $message['source'] ?? 'unknown'
        ]));

        Log::info("[DTMF] DTMF message handled", [
            'fd' => $fd,
            'digit' => $message['digit'],
            'source' => $message['source'] ?? 'unknown'
        ]);
    }
} 