<?php

namespace App\Services;

use OpenSwoole\WebSocket\Client;
use OpenSwoole\Timer;
use Illuminate\Support\Facades\Log;

class AIRelay
{
    private Client $localClient;
    private Client $openAiClient;
    private string $chatId;
    private int $userFd;
    private array $config;
    private $lastActivity;
    private $pingTimer;
    private ToolExecutor $toolExecutor;

    public function __construct(string $chatId, int $userFd, array $config)
    {
        $this->chatId = $chatId;
        $this->userFd = $userFd;
        $this->config = $config;
        $this->lastActivity = time();
        $this->toolExecutor = new ToolExecutor();
        
        Log::channel('relay')->info('AIRelay initialized', [
            'chat_id' => $chatId,
            'user_fd' => $userFd,
            'config' => $config
        ]);
    }

    private function handleLocalMessage($data): void
    {
        try {
            $message = json_decode($data, true);
            Log::channel('relay')->info('Local -> OpenAI message', [
                'chat_id' => $this->chatId,
                'type' => $message['type'] ?? 'unknown',
                'content_length' => strlen($data)
            ]);

            // Handle tool execution requests
            if (isset($message['type']) && $message['type'] === 'tool_request') {
                $this->handleToolRequest($message);
                return;
            }

            // Forward to OpenAI
            $sent = $this->openAiClient->push($data);
            Log::channel('relay')->info('Message forwarded to OpenAI', [
                'chat_id' => $this->chatId,
                'success' => $sent
            ]);

        } catch (\Exception $e) {
            Log::channel('relay')->error('Error handling local message', [
                'chat_id' => $this->chatId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    private function handleOpenAIMessage($data): void
    {
        try {
            $message = json_decode($data, true);
            Log::channel('relay')->info('OpenAI -> Local message', [
                'chat_id' => $this->chatId,
                'type' => $message['type'] ?? 'unknown',
                'content_length' => strlen($data)
            ]);

            // Forward to local client
            $sent = $this->localClient->push($data);
            Log::channel('relay')->info('Message forwarded to local', [
                'chat_id' => $this->chatId,
                'success' => $sent
            ]);

        } catch (\Exception $e) {
            Log::channel('relay')->error('Error handling OpenAI message', [
                'chat_id' => $this->chatId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    private function handleToolRequest($message): void
    {
        Log::channel('relay')->info('Processing tool request', [
            'chat_id' => $this->chatId,
            'tool' => $message['tool'] ?? 'unknown',
            'arguments' => $message['arguments'] ?? []
        ]);

        try {
            $result = $this->toolExecutor->{$message['tool']}($message['arguments']);
            
            // Send tool execution result back to OpenAI
            $response = json_encode([
                'type' => 'tool_response',
                'tool' => $message['tool'],
                'result' => $result
            ]);
            
            $sent = $this->openAiClient->push($response);
            Log::channel('relay')->info('Tool execution result sent', [
                'chat_id' => $this->chatId,
                'tool' => $message['tool'],
                'success' => $sent,
                'result' => $result
            ]);

        } catch (\Exception $e) {
            Log::channel('relay')->error('Tool execution error', [
                'chat_id' => $this->chatId,
                'tool' => $message['tool'] ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    public function start(): bool
    {
        try {
            Log::channel('relay')->info('Starting AIRelay', [
                'chat_id' => $this->chatId
            ]);

            // Connect to local WebSocket server
            $this->localClient = new Client("richbot9000.com", 9501, true);
            $this->localClient->setHeaders([
                'Authorization' => 'Bearer ' . env('APP_KEY'),
                'X-Relay-Type' => 'internal'
            ]);
            
            if (!$this->localClient->upgrade("/")) {
                throw new \Exception("Failed to connect to local WebSocket");
            }

            // Connect to OpenAI
            $this->openAiClient = new Client("api.openai.com", 443, true);
            $this->openAiClient->setHeaders([
                'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
                'Content-Type' => 'application/json'
            ]);

            if (!$this->openAiClient->upgrade("/v1/realtime?model=gpt-4o-realtime-preview-2024-10-01")) {
                throw new \Exception("Failed to connect to OpenAI");
            }

            // Set up ping/pong for both connections
            $this->setupHeartbeat();
            
            // Start message relay coroutines
            $this->startRelays();

            Log::channel('relay')->info('AIRelay started successfully', [
                'chat_id' => $this->chatId,
                'local_connected' => $this->localClient->connected,
                'openai_connected' => $this->openAiClient->connected
            ]);

            return true;

        } catch (\Exception $e) {
            Log::channel('relay')->error('AIRelay start error', [
                'chat_id' => $this->chatId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    private function setupHeartbeat(): void
    {
        $this->pingTimer = Timer::tick(30000, function() {
            if ($this->isStale()) {
                $this->cleanup();
                return;
            }
            
            $this->localClient->push('ping');
            $this->openAiClient->push(json_encode(['type' => 'ping']));
        });
    }

    private function startRelays(): void
    {
        // Relay from local to OpenAI
        go(function() {
            while ($this->localClient->connected) {
                $frame = $this->localClient->recv();
                if ($frame && $frame->data) {
                    $this->lastActivity = time();
                    $this->handleLocalMessage($frame->data);
                }
            }
        });

        // Relay from OpenAI to local
        go(function() {
            while ($this->openAiClient->connected) {
                $frame = $this->openAiClient->recv();
                if ($frame && $frame->data) {
                    $this->lastActivity = time();
                    $this->handleOpenAIMessage($frame->data);
                }
            }
        });
    }

    public function isStale(): bool
    {
        return (time() - $this->lastActivity) > 300; // 5 minutes
    }

    public function cleanup(): void
    {
        Log::channel('relay')->info('Cleaning up AIRelay', [
            'chat_id' => $this->chatId,
            'last_activity' => $this->lastActivity
        ]);

        Timer::clear($this->pingTimer);
        $this->localClient->close();
        $this->openAiClient->close();
    }
} 