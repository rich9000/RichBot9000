<?php

namespace App\WebSocket\Orchestrators;

use App\WebSocket\Services\WebSocketControlService;
use Illuminate\Support\Facades\Log;

class ConversationOrchestrator
{
    protected $webSocketService;
    protected $handlers = [];

    public function __construct(WebSocketControlService $webSocketService)
    {
        $this->webSocketService = $webSocketService;
    }

    public function registerHandler(string $type, callable $handler)
    {
        $this->handlers[$type] = $handler;
        Log::info("[ConversationOrchestrator] Registered handler for type: {$type}");
    }

    public function handleMessage($msg)
    {
        try {
            $data = json_decode($msg, true);
            if (!isset($data['type'])) {
                Log::warning("[ConversationOrchestrator] Message missing type field", ['message' => $msg]);
                return;
            }
            
            $type = $data['type'];
            if (isset($this->handlers[$type])) {
                call_user_func($this->handlers[$type], $data);
            } else {
                Log::info("[ConversationOrchestrator] No handler registered for type: {$type}");
            }
        } catch (\Exception $e) {
            Log::error("[ConversationOrchestrator] Error handling message", [
                'error' => $e->getMessage(),
                'message' => $msg
            ]);
        }
    }

    public function start()
    {
        $this->webSocketService->onMessage(function ($msg) {
            $this->handleMessage($msg);
        });

        $this->webSocketService->onError(function ($e) {
            Log::error("[ConversationOrchestrator] WebSocket error", [
                'error' => $e->getMessage()
            ]);
        });

        $this->webSocketService->onClose(function ($code, $reason) {
            Log::info("[ConversationOrchestrator] WebSocket connection closed", [
                'code' => $code,
                'reason' => $reason
            ]);
        });

        Log::info("[ConversationOrchestrator] Started with " . count($this->handlers) . " handlers");
    }

    public function send($message)
    {
        $this->webSocketService->send($message);
    }
} 