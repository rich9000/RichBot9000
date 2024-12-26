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
        
        // Special handling for session updates
        if (isset($toData['type']) && $toData['type'] === 'session.update') {
            $this->logSessionUpdate($toData);
        }
        
        $this->logger->debug($message);
    }

    public function logSessionUpdate($data)
    {
        $session = $data['session'] ?? [];
        $eventId = $data['event_id'] ?? 'unknown';
        
        $sessionInfo = [
            str_repeat("=", 80),
            "🔄 SESSION UPDATE [{$eventId}]",
            str_repeat("-", 40),
            "Basic Configuration:",
            "  Model: " . ($session['model'] ?? 'not specified'),
            "  Voice: " . ($session['voice'] ?? 'not specified'),
            "  Modalities: " . implode(', ', $session['modalities'] ?? []),
            "  Object Type: " . ($session['object'] ?? 'not specified'),
            "  Expires At: " . ($session['expires_at'] ? date('Y-m-d H:i:s', $session['expires_at']) : 'not specified'),
            str_repeat("-", 40)
        ];

        if (isset($session['instructions'])) {
            $sessionInfo[] = "Instructions:";
            $sessionInfo[] = "  Length: " . strlen($session['instructions']) . " characters";
            $sessionInfo[] = "  Preview: " . substr($session['instructions'], 0, 100) . "...";
            $sessionInfo[] = str_repeat("-", 40);
        }

        if (isset($session['tools'])) {
            $sessionInfo[] = "Tools Configuration:";
            if (empty($session['tools'])) {
                $sessionInfo[] = "  ⚠️ No tools configured";
            } else {
                foreach ($session['tools'] as $index => $tool) {
                    $sessionInfo[] = $this->formatToolInfo($tool, $index + 1);
                }
            }
            $sessionInfo[] = str_repeat("-", 40);
        }

        // Add validation warnings
        $warnings = $this->validateSessionConfig($session);
        if (!empty($warnings)) {
            $sessionInfo[] = "⚠️ Configuration Warnings:";
            foreach ($warnings as $warning) {
                $sessionInfo[] = "  - " . $warning;
            }
            $sessionInfo[] = str_repeat("-", 40);
        }

        $sessionInfo[] = str_repeat("=", 80);
        
        $this->logger->info(implode("\n", $sessionInfo));
    }

    private function validateSessionConfig($session)
    {
        $warnings = [];
        
        // Check for required fields
        $requiredFields = ['model', 'modalities', 'tools'];
        foreach ($requiredFields as $field) {
            if (!isset($session[$field])) {
                $warnings[] = "Missing required field: {$field}";
            }
        }

        // Validate tools configuration
        if (isset($session['tools']) && is_array($session['tools'])) {
            foreach ($session['tools'] as $index => $tool) {
                if (!isset($tool['type'])) {
                    $warnings[] = "Tool {$index}: Missing 'type' field";
                    continue;
                }

                if ($tool['type'] === 'function') {
                    if (!isset($tool['function'])) {
                        $warnings[] = "Tool {$index}: Missing 'function' configuration";
                        continue;
                    }

                    $function = $tool['function'];
                    // Check required function fields
                    foreach (['name', 'description', 'parameters'] as $field) {
                        if (!isset($function[$field])) {
                            $warnings[] = "Tool {$index}: Missing function.{$field}";
                        }
                    }

                    // Validate parameters structure
                    if (isset($function['parameters'])) {
                        $params = $function['parameters'];
                        if (!isset($params['type']) || $params['type'] !== 'object') {
                            $warnings[] = "Tool {$index}: Parameters must have type 'object'";
                        }
                        if (!isset($params['properties']) || !is_array($params['properties'])) {
                            $warnings[] = "Tool {$index}: Missing or invalid properties";
                        }
                        if (!isset($params['required']) || !is_array($params['required'])) {
                            $warnings[] = "Tool {$index}: Missing or invalid required fields";
                        }

                        // Check each property
                        if (isset($params['properties'])) {
                            foreach ($params['properties'] as $paramName => $paramConfig) {
                                if (!isset($paramConfig['type'])) {
                                    $warnings[] = "Tool {$index}: Parameter '{$paramName}' missing type";
                                }
                                if (!isset($paramConfig['description'])) {
                                    $warnings[] = "Tool {$index}: Parameter '{$paramName}' missing description";
                                }
                            }
                        }
                    }

                    // Log the actual structure for debugging
                    $this->logger->debug("Tool {$index} structure:", [
                        'name' => $function['name'] ?? 'missing',
                        'parameters' => $function['parameters'] ?? 'missing'
                    ]);
                }
            }
        }

        return $warnings;
    }

    private function formatToolInfo($tool, $index)
    {
        $info = ["  {$index}. Type: {$tool['type']}"];
        
        if (isset($tool['function'])) {
            $func = $tool['function'];
            $info[] = "     Name: " . ($func['name'] ?? '⚠️ Missing name');
            $info[] = "     Description: " . substr($func['description'] ?? '⚠️ Missing description', 0, 100);
            
            if (isset($func['parameters'])) {
                $info[] = "     Parameters:";
                if (isset($func['parameters']['properties'])) {
                    foreach ($func['parameters']['properties'] as $name => $param) {
                        $required = in_array($name, $func['parameters']['required'] ?? []) ? '(Required)' : '(Optional)';
                        $info[] = sprintf("       - %s %s: %s", 
                            $name,
                            $required,
                            $param['description'] ?? 'No description'
                        );
                    }
                } else {
                    $info[] = "       ⚠️ No properties defined";
                }
            } else {
                $info[] = "     ⚠️ No parameters defined";
            }
        } else {
            $info[] = "     ⚠️ Invalid tool configuration - missing function definition";
        }
        
        return implode("\n", $info);
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
        
        // Special handling for session and tool updates
        if (isset($data['type'])) {
            switch ($data['type']) {
                case 'session.update':
                    $this->logSessionUpdate($data);
                    break;
                case 'error':
                    $this->logError($data);
                    break;
            }
        }
        
        $this->logger->debug($message);
    }

    private function logError($data)
    {
        $errorInfo = [
            "❌ ERROR DETAILS",
            "Type: " . ($data['error']['type'] ?? 'unknown'),
            "Code: " . ($data['error']['code'] ?? 'unknown'),
            "Message: " . ($data['error']['message'] ?? 'No message provided'),
        ];

        if (isset($data['error']['param'])) {
            $errorInfo[] = "Parameter: " . $data['error']['param'];
        }

        $this->logger->error(implode("\n", $errorInfo));
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