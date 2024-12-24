<?php

namespace App\Services;

use OpenSwoole\Table;
use Illuminate\Support\Facades\Log;

class RelayManager
{
    private Table $relayTable;
    private $server;
    private array $openaiRelays = [];

    public function __construct(Table $relayTable, $server)
    {
        $this->relayTable = $relayTable;
        $this->server = $server;
    }

    public function handleMessage(string $chatId, array $data): void
    {
        try {
            Log::info('Handling message', [
                'chat_id' => $chatId,
                'event' => $data['event'] ?? 'unknown',
                'type' => $data['type'] ?? 'unknown'
            ]);

            $relay = $this->getOpenAIRelay($chatId);
            if (!$relay || !$relay->isConnected()) {
                Log::error('No active OpenAI relay found', ['chat_id' => $chatId]);
                return;
            }

            $relay->sendMessage($data);

        } catch (\Exception $e) {
            Log::error('Message handling error', [
                'chat_id' => $chatId,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function startOpenAIRelay(string $chatId, int $clientFd, string $assistantId): bool
    {
        try {
            Log::info('Starting OpenAI relay', [
                'chat_id' => $chatId,
                'client_fd' => $clientFd,
                'assistant_id' => $assistantId
            ]);

            // Create new OpenAI relay
            $relay = new OpenAIRelay($chatId, $clientFd, $this->server, $assistantId);
            
            // Store in relays array
            $this->openaiRelays[$chatId] = $relay;

            // Connect to OpenAI
            if (!$relay->connect()) {
                throw new \Exception("Failed to establish OpenAI connection");
            }

            // Update relay table
            $this->relayTable->set($chatId, [
                'chat_id' => $chatId,
                'client_fd' => $clientFd,
                'type' => 'openai',
                'status' => 'connected',
                'assistant_id' => $assistantId,
                'last_activity' => time()
            ]);

            Log::info('OpenAI relay started successfully', [
                'chat_id' => $chatId,
                'assistant_id' => $assistantId
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error('Failed to start OpenAI relay', [
                'chat_id' => $chatId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function getOpenAIRelay(string $chatId): ?OpenAIRelay
    {
        return $this->openaiRelays[$chatId] ?? null;
    }

    public function cleanup(): void
    {
        $now = time();
        
        // Clean up stale relays
        foreach ($this->relayTable as $chatId => $relay) {
            if ($now - $relay['last_activity'] > 600) { // 10 minutes
                $this->closeRelay($chatId);
            }
        }
    }

    public function closeRelay(string $chatId): void
    {
        try {
            if (isset($this->openaiRelays[$chatId])) {
                $this->openaiRelays[$chatId]->disconnect();
                unset($this->openaiRelays[$chatId]);
            }
            $this->relayTable->del($chatId);
        } catch (\Exception $e) {
            Log::error('Error closing relay', [
                'chat_id' => $chatId,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function isRelayActive(string $chatId): bool
    {
        $relay = $this->getOpenAIRelay($chatId);
        return $relay && $relay->isConnected();
    }
} 