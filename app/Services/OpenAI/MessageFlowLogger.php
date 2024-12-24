<?php

namespace App\Services\OpenAI;

use Illuminate\Support\Facades\Log;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Formatter\LineFormatter;

class MessageFlowLogger
{
    private $logger;
    private $chatId;

    public function __construct($chatId)
    {
        $this->chatId = $chatId;
        $this->logger = new Logger('message_flow');
        
        $dateFormat = "Y-m-d H:i:s";
        $output = "%datetime% %message%\n";
        $formatter = new LineFormatter($output, $dateFormat);
        
        $handler = new StreamHandler(storage_path('logs/message_flow.log'), Logger::DEBUG);
        $handler->setFormatter($formatter);
        $this->logger->pushHandler($handler);
    }

    public function logTransformation($from, $to, $fromData, $toData, $notes = null)
    {
        $message = $this->formatSingleLine(
            $from,
            $to,
            $this->extractMessageInfo($fromData, $toData)
        );
        $this->logger->debug($message);
    }

    public function logDrop($from, $data, $reason)
    {
        $message = sprintf(
            "[%s] %s ⟶ DROPPED | %s | Reason: %s",
            substr($this->chatId, 0, 8),
            str_pad($from, 10),
            $this->extractMessageInfo($data),
            $reason
        );
        $this->logger->debug($message);
    }

    public function logPass($from, $to, $data)
    {
        $message = $this->formatSingleLine(
            $from,
            $to . " (PASS)",
            $this->extractMessageInfo($data)
        );
        $this->logger->debug($message);
    }

    private function formatSingleLine($from, $to, $info)
    {
        return sprintf(
            "[%s] %s ⟶ %s | %s",
            substr($this->chatId, 0, 8),
            str_pad($from, 10),
            str_pad($to, 10),
            $info
        );
    }

    private function extractMessageInfo($fromData, $toData = null)
    {
        $info = [];
        
        // Extract type
        if (isset($toData['type'])) {
            $info[] = "Type: " . $toData['type'];
        } elseif (isset($fromData['type'])) {
            $info[] = "Type: " . $fromData['type'];
        }

        // Extract event info
        if (isset($toData['event']['type']) || isset($fromData['event']['type'])) {
            $info[] = "Event: " . ($toData['event']['type'] ?? $fromData['event']['type']);
        }

        // Extract role
        if (isset($toData['item']['role']) || isset($fromData['item']['role'])) {
            $info[] = "Role: " . ($toData['item']['role'] ?? $fromData['item']['role']);
        }

        // Extract status
        if (isset($toData['item']['status']) || isset($fromData['item']['status'])) {
            $info[] = "Status: " . ($toData['item']['status'] ?? $fromData['item']['status']);
        }

        // For audio messages, just note presence
        if (isset($fromData['audio']) || isset($toData['audio'])) {
            $info[] = "Audio: present";
            return implode(" | ", $info);
        }

        // For audio deltas, just note size
        if (isset($fromData['delta']) && (isset($fromData['type']) && $fromData['type'] === 'response.audio.delta')) {
            $info[] = "Audio delta: " . strlen($fromData['delta']) . " bytes";
            return implode(" | ", $info);
        }

        // Extract text content if present (truncated)
        if (isset($fromData['content'])) {
            $text = is_string($fromData['content']) ? $fromData['content'] : json_encode($fromData['content']);
            $info[] = "Text: " . substr($text, 0, 30);
        } elseif (isset($toData['content'])) {
            $text = is_string($toData['content']) ? $toData['content'] : json_encode($toData['content']);
            $info[] = "Text: " . substr($text, 0, 30);
        }

        return implode(" | ", $info);
    }
} 