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
        $context = is_array($data) ? $data : ['raw_data' => $data];
        
        $message = sprintf(
            "[%s] %s ⟶ DROPPED | %s | Reason: %s\nContext: %s",
            substr($this->chatId, 0, 8),
            str_pad($from, 10),
            $this->extractMessageInfo($context),
            $reason,
            json_encode($context, JSON_PRETTY_PRINT)
        );
        
        // Log to both debug and error for better visibility
        $this->logger->debug($message);
        Log::error("Message dropped", [
            'from' => $from,
            'chat_id' => $this->chatId,
            'reason' => $reason,
            'context' => $context
        ]);
    }

    public function logPass($from, $to, $data)
    {
        $message = $this->formatSingleLine(
            $from,
            $to . " (PASS)",
            $this->extractMessageInfo($data)
        );
        
        // Enhanced error handling
        if (isset($data['type']) && ($data['type'] === 'error' || strpos($data['type'], 'error') !== false)) {
            $this->logError($data);
            return;
        }
        
        // Special handling for session and tool updates
        if (isset($data['type'])) {
            switch ($data['type']) {
                case 'session.update':
                    $this->logSessionUpdate($data);
                    break;
                case 'unknown':
                    $this->logUnknownMessage($data);
                    break;
            }
        }
        
        $this->logger->debug($message);
    }

    private function logError($data)
    {
        $errorInfo = [
            str_repeat("=", 80),
            "❌ ERROR DETAILS",
            str_repeat("-", 40)
        ];

        // Add basic error info
        $errorInfo[] = "Type: " . ($data['error']['type'] ?? $data['type'] ?? 'unknown');
        $errorInfo[] = "Code: " . ($data['error']['code'] ?? 'unknown');
        $errorInfo[] = "Message: " . ($data['error']['message'] ?? $data['message'] ?? 'No message provided');

        // Add parameter if present
        if (isset($data['error']['param']) || isset($data['param'])) {
            $errorInfo[] = "Parameter: " . ($data['error']['param'] ?? $data['param']);
        }

        // Add event info if present
        if (isset($data['event_id'])) {
            $errorInfo[] = "Event ID: " . $data['event_id'];
        }

        // Add full data context
        $errorInfo[] = "\nFull Error Context:";
        $errorInfo[] = json_encode($data, JSON_PRETTY_PRINT);

        // Add stack trace if available
        if (isset($data['error']['stack']) || isset($data['stack'])) {
            $errorInfo[] = "\nStack Trace:";
            $errorInfo[] = $data['error']['stack'] ?? $data['stack'];
        }

        $errorInfo[] = str_repeat("=", 80);

        // Log at error level and also to separate error log file
        $this->logger->error(implode("\n", $errorInfo));
        
        // Also log to Laravel's error log
        Log::error("MessageFlow Error", [
            'chat_id' => $this->chatId,
            'error_type' => $data['error']['type'] ?? $data['type'] ?? 'unknown',
            'error_code' => $data['error']['code'] ?? 'unknown',
            'error_message' => $data['error']['message'] ?? $data['message'] ?? 'No message provided',
            'full_context' => $data
        ]);
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

    private function extractMessageInfo($data, $toData = null)
    {
        $info = [];
        
        // Add client type if available
        if (isset($data['client_type'])) {
            $info[] = "Client: " . $data['client_type'];
        }

        // Extract type
        if (isset($data['type'])) {
            $info[] = "Type: " . $data['type'];
        }

        // Extract message details
        if (isset($data['data'])) {
            if (is_array($data['data'])) {
                $info[] = "Data: " . json_encode($data['data']);
            } else {
                $info[] = "Data length: " . strlen($data['data']);
            }
        }

        // Add any error information
        if (isset($data['error'])) {
            $info[] = "Error: " . $data['error'];
        }

        return implode(" | ", $info);
    }

    // Add new method to handle unknown messages
    private function logUnknownMessage($data)
    {
        $unknownInfo = [
            str_repeat("=", 80),
            "⚠️ UNKNOWN MESSAGE TYPE",
            str_repeat("-", 40),
            "Received Data:",
            json_encode($data, JSON_PRETTY_PRINT),
            str_repeat("=", 80)
        ];
        
        $this->logger->warning(implode("\n", $unknownInfo));
        
        Log::warning("Unknown Message Type in MessageFlow", [
            'chat_id' => $this->chatId,
            'data' => $data
        ]);
    }
} 