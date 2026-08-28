<?php

namespace App\WebSocket\Messages;

use App\WebSocket\Messages\Interfaces\MessageHandlerInterface;
use Swoole\WebSocket\Server;
use Illuminate\Support\Facades\Log;

class BroadcastMessageHandler implements MessageHandlerInterface
{
    protected array $tables;

    public function __construct(array $tables)
    {
        $this->tables = $tables;
    }

    public function handle(Server $server, int $fd, array $message): void
    {
        $client = $this->tables['clients']->get($fd);
        if (!$client) {
            Log::error("[BROADCAST] Unknown client", ['fd' => $fd]);
            return;
        }

        // Validate broadcast message
        if (!isset($message['content'])) {
            Log::error("[BROADCAST] Missing content", ['fd' => $fd]);
            return;
        }

        $content = $message['content'];
        $source = $message['source'] ?? 'unknown';
        $target = $message['target'] ?? 'all'; // 'all', 'room', or specific client type
        $room = $message['room'] ?? null;
        $clientType = $message['client_type'] ?? null;

        $broadcastMessage = [
            'type' => 'broadcast',
            'content' => $content,
            'source' => $source,
            'timestamp' => time()
        ];

        $sentCount = 0;
        $failedCount = 0;

        // Broadcast to appropriate targets
        foreach ($this->tables['clients'] as $targetFd => $targetClient) {
            // Skip the sender
            if ($targetFd == $fd) {
                continue;
            }

            // Apply filters
            if ($target === 'room' && $room && $targetClient['room'] !== $room) {
                continue;
            }

            if ($clientType && $targetClient['type'] !== $clientType) {
                continue;
            }

            // Send the message
            try {
                $success = $server->push($targetFd, json_encode($broadcastMessage));
                if ($success) {
                    $sentCount++;
                } else {
                    $failedCount++;
                }
            } catch (\Exception $e) {
                $failedCount++;
                Log::warning("[BROADCAST] Failed to send to client", [
                    'target_fd' => $targetFd,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Send confirmation back to sender
        $server->push($fd, json_encode([
            'type' => 'broadcast_response',
            'status' => 'sent',
            'sent_count' => $sentCount,
            'failed_count' => $failedCount,
            'total_targets' => $sentCount + $failedCount,
            'source' => 'server'
        ]));

        Log::info("[BROADCAST] Broadcast message sent", [
            'sender_fd' => $fd,
            'source' => $source,
            'target' => $target,
            'sent_count' => $sentCount,
            'failed_count' => $failedCount,
            'content_length' => strlen($content)
        ]);
    }
} 