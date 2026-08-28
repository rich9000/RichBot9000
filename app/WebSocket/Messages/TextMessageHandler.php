<?php

namespace App\WebSocket\Messages;

use App\WebSocket\Messages\Interfaces\MessageHandlerInterface;
use Swoole\WebSocket\Server;
use Illuminate\Support\Facades\Log;

class TextMessageHandler implements MessageHandlerInterface
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
            Log::error("[TEXT] Unknown client", ['fd' => $fd]);
            return;
        }

        // Validate text message
        if (!isset($message['content'])) {
            Log::error("[TEXT] Missing content", ['fd' => $fd]);
            return;
        }

        // Broadcast text to room
        $server->push($fd, json_encode([
            'type' => 'text',
            'content' => $message['content'],
            'source' => $message['source'] ?? 'unknown'
        ]));

        Log::info("[TEXT] Text message handled", [
            'fd' => $fd,
            'content_length' => strlen($message['content']),
            'source' => $message['source'] ?? 'unknown'
        ]);
    }
} 