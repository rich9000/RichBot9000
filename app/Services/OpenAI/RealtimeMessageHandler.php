<?php

namespace App\Services\OpenAI;

use Illuminate\Support\Facades\Log;

class RealtimeMessageHandler
{
    private $richbotConn;
    private $openaiConn;
    private $chatId;
    private $currentResponse = null;
    private $functionCallBuffer = [];
    private $flowLogger;
    
    public function __construct($richbotConn, $openaiConn, $chatId)
    {
        $this->richbotConn = $richbotConn;
        $this->openaiConn = $openaiConn;
        $this->chatId = $chatId;
        $this->flowLogger = new MessageFlowLogger($chatId);
    }

    public function setOpenAIConnection($openaiConn)
    {
        $this->openaiConn = $openaiConn;
        Log::info("OpenAI connection set in RealtimeMessageHandler", [
            'chat_id' => $this->chatId
        ]);
    }

    public function handleServerEvent($event)
    {
        // Convert Ratchet Message to array if needed
        if ($event instanceof \Ratchet\RFC6455\Messaging\Message) {
            $originalEvent = $event;
            $event = json_decode($event->getPayload(), true);
            $this->flowLogger->logTransformation(
                'WEBSOCKET',
                'HANDLER',
                $originalEvent,
                $event,
                'Converted Ratchet Message to array'
            );
        }

        // Handle null or invalid JSON
        if (!is_array($event)) {
            $this->flowLogger->logDrop('HANDLER', $event, 'Invalid message format - not an array');
            return;
        }

        $type = $event['type'] ?? null;
        if (!$type) {
            $this->flowLogger->logDrop('HANDLER', $event, 'Missing type field');
            return;
        }

        $this->flowLogger->logPass('HANDLER', 'EVENT_PROCESSOR', [
            'type' => $type,
            'event' => $event
        ]);

        Log::info("Handling OpenAI event", [
            'type' => $type,
            'chat_id' => $this->chatId
        ]);

        switch ($type) {
            // Session Events
            case 'session.created':
            case 'session.updated':
                $this->handleSessionEvent($event);
                break;

            // Conversation Events
            case 'conversation.created':
            case 'conversation.item.created':
                $this->handleConversationEvent($event);
                break;

            case 'conversation.item.input_audio_transcription.completed':
                $this->handleAudioTranscriptionComplete($event);
                break;

            case 'conversation.item.input_audio_transcription.failed':
                $this->handleAudioTranscriptionFailed($event);
                break;

            case 'input_audio_buffer.speech_started':
            case 'input_audio_buffer.speech_stopped':
                $this->handleSpeechEvent($event);
                break;

            // Response Events
            case 'response.created':
                $this->currentResponse = $event['response']['id'];
                $this->handleResponseCreated($event);
                break;

            case 'response.text.delta':
                $this->handleTextDelta($event);
                break;

            case 'response.audio.delta':
                $this->handleAudioDelta($event);
                break;

            case 'response.function_call_arguments.delta':
                $this->handleFunctionCallDelta($event);
                break;

            case 'response.function_call_arguments.done':
                $this->handleFunctionCallComplete($event);
                break;

            case 'response.done':
                $this->handleResponseDone($event);
                $this->currentResponse = null;
                break;

            case 'error':
                $this->handleError($event);
                break;

            case 'response.audio_transcript.done':
                $this->richbotConn->send(json_encode([
                    'type' => 'assistant_audio_transcript',
                    'chat_id' => $this->chatId,
                    'data' => [
                        'response_id' => $event['response_id'],
                        'transcript' => $event['transcript']
                    ]
                ]));
                break;

            case 'response.audio_transcript.delta':
                $this->handleTranscriptDelta($event);
                break;

            case 'response.output_item.done':
                $this->handleOutputItemDone($event);
                break;

            default:
                Log::info("Unhandled OpenAI event type: {$type}", ['event' => $event]);
                break;
        }
    }

    private function handleSessionEvent($message)
    {
        // Forward relevant session info to client
        $this->sendToClient('session_update', [
            'session_id' => $message['session']['id'] ?? null,
            'model' => $message['session']['model'] ?? null,
            'voice' => $message['session']['voice'] ?? null
        ]);
    }

    private function handleConversationEvent($message)
    {
        if (isset($message['item'])) {
            $this->sendToClient('conversation_update', [
                'item' => $message['item']
            ]);
            
            // If this is a user message, create a response
            if ($message['item']['role'] === 'user' && $message['item']['status'] === 'completed') {
                Log::info("Creating response for user message", [
                    'item_id' => $message['item']['id'],
                    'chat_id' => $this->chatId
                ]);
                
                $this->createResponse($message['item']['id']);
            }
        }
    }

    private function createResponse($itemId)
    {
        $responseMessage = [
            'type' => 'response.create',
            'response' => [
                'modalities' => ['text', 'audio'],
                'message_id' => $itemId
            ]
        ];

        try {
            Log::info("Sending response.create", [
                'message_id' => $itemId,
                'message' => $responseMessage
            ]);
            
            // Send response.create to OpenAI
            
            $this->openaiConn->send(json_encode(['type' => 'response.create']));

            

            $this->flowLogger->logTransformation(
                'HANDLER',
                'OPENAI',
                ['message_id' => $itemId],
               'regular response create',
                'Created response request'
            );

            // Set a flag to track that we're waiting for a response
            $this->currentResponse = 'pending_' . $itemId;
            
            Log::info("Response creation sent, waiting for response events", [
                'message_id' => $itemId,
                'chat_id' => $this->chatId
            ]);
        } catch (\Exception $e) {
            Log::error("Error creating response", [
                'error' => $e->getMessage(),
                'message_id' => $itemId,
                'chat_id' => $this->chatId,
                'trace' => $e->getTraceAsString()
            ]);
            $this->flowLogger->logDrop('HANDLER', $responseMessage, 'Failed to send: ' . $e->getMessage());
            
            // Send error to client
            $this->sendToClient('error', [
                'message' => 'Failed to create response: ' . $e->getMessage(),
                'message_id' => $itemId
            ]);
        }
    }

    private function handleTextDelta($message)
    {
        Log::info("Text delta received", [
            'response_id' => $message['response_id'],
            'delta_length' => strlen($message['delta'] ?? '')
        ]);

        $this->sendToClient('assistant_text_delta', [
            'delta' => $message['delta'],
            'response_id' => $message['response_id']
        ]);
    }

    private function handleAudioDelta($message)
    {
        Log::info("Audio delta received", [
            'response_id' => $message['response_id'],
            'delta_length' => strlen($message['delta'] ?? '')
        ]);

        $this->sendToClient('assistant_audio_delta', [
            'delta' => $message['delta'],
            'response_id' => $message['response_id']
        ]);
    }

    private function handleTranscriptDelta($message)
    {
        $this->sendToClient('assistant_audio_transcript_delta', [
            'response_id' => $message['response_id'],
            'delta' => $message['delta']
        ]);
    }

    private function handleFunctionCallDelta($message)
    {
        $callId = $message['call_id'];
        if (!isset($this->functionCallBuffer[$callId])) {
            $this->functionCallBuffer[$callId] = '';
        }
        $this->functionCallBuffer[$callId] .= $message['delta'];
    }

    private function handleFunctionCallComplete($message)
    {
        $callId = $message['call_id'];
        $arguments = $message['arguments'];
        
        try {
            $functionCall = json_decode($arguments, true);
            $this->sendToClient('function_call', [
                'name' => $functionCall['name'] ?? null,
                'arguments' => $functionCall,
                'call_id' => $callId
            ]);
        } catch (\Exception $e) {
            Log::error("Error processing function call", [
                'error' => $e->getMessage(),
                'arguments' => $arguments
            ]);
        }

        unset($this->functionCallBuffer[$callId]);
    }

    private function handleResponseDone($message)
    {
        $this->sendToClient('assistant_response_complete', [
            'response_id' => $message['response']['id'],
            'usage' => $message['response']['usage'] ?? null
        ]);
    }

    private function handleError($message)
    {
        Log::error("OpenAI Error", [
            'error' => $message['error'],
            'chat_id' => $this->chatId
        ]);
        
        $this->sendToClient('error', [
            'error' => $message['error']
        ]);
    }

    private function handleResponseCreated($message)
    {
        Log::info("Response created event received", [
            'response_id' => $message['response']['id'] ?? null,
            'chat_id' => $this->chatId,
            'status' => $message['response']['status'] ?? null
        ]);

        $this->currentResponse = $message['response']['id'];
        $this->sendToClient('response.created', [
            'response_id' => $this->currentResponse,
            'status' => $message['response']['status']
        ]);
    }

    public function createClientMessage($type, $data)
    {
        $originalData = $data;
        $message = null;

        switch ($type) {
            case 'text':
                $message = $this->createTextMessage($data);
                break;
            case 'audio':
                $message = $this->createAudioMessage($data['audio'] ?? $data);
                break;
            case 'function_result':
                $message = $this->createFunctionResultMessage($data);
                break;
            default:
                $this->flowLogger->logDrop('MESSAGE_CREATOR', $data, "Unknown message type: {$type}");
                return null;
        }

        if ($message) {
            $this->flowLogger->logTransformation(
                'CLIENT',
                'OPENAI',
                $originalData,
                $message,
                "Created {$type} message"
            );
        }

        return $message;
    }

    private function createTextMessage($text)
    {
        return [
            'type' => 'conversation.item.create',
            'item' => [
                'type' => 'message',
                'role' => 'user',
                'content' => [
                    [
                        'type' => 'input_text',
                        'text' => $text['content'] ?? $text // Handle both string and object input
                    ]
                ]
            ]
        ];
    }

    private function createAudioMessage($base64Audio)
    {
        return [
            'type' => 'input_audio_buffer.append',
            'audio' => $base64Audio
        ];
    }

    private function createFunctionResultMessage($data)
    {
        return [
            'type' => 'conversation.item.create',
            'item' => [
                'type' => 'function_call_response',
                'name' => $data['name'],
                'response' => $data['response']
            ]
        ];
    }

    private function sendToClient($type, $data)
    {
        try {
            $message = [
                'type' => $type,
                'chat_id' => $this->chatId,
                'data' => $data,
                'timestamp' => time()
            ];

            $this->flowLogger->logTransformation(
                'HANDLER',
                'CLIENT',
                $data,
                $message,
                'Formatted message for client'
            );

            $this->richbotConn->send(json_encode($message));
        } catch (\Exception $e) {
            $this->flowLogger->logDrop('CLIENT', $message, 'Send error: ' . $e->getMessage());
            Log::error("Error sending to client", [
                'error' => $e->getMessage(),
                'type' => $type,
                'chat_id' => $this->chatId
            ]);
        }
    }

    public function getInitialSessionConfig()
    {
        $defaultInstructions = config('ai.default_instructions') ?? 
            "You are a helpful AI assistant. Be concise and clear in your responses. " .
            "When speaking, use a natural, conversational tone with appropriate pauses. " .
            "If you're not sure about something, say so. " .
            "If you need more information to answer accurately, ask for clarification.";

        return [
            'type' => 'session.update',
            'event_id' => 'init_' . uniqid(),  // Optional but helpful for tracking
            'session' => [
                'modalities' => ['text', 'audio'],
                'instructions' => $defaultInstructions,
                'voice' => 'sage',
                'input_audio_format' => 'pcm16',
                'output_audio_format' => 'pcm16',
                'input_audio_transcription' => [
                    'model' => 'whisper-1'
                ],
                'turn_detection' => [
                    'type' => 'server_vad',
                    'threshold' => 0.5,
                    'prefix_padding_ms' => 300,
                    'silence_duration_ms' => 500,
                    'create_response' => true
                ],
                'tools' => $this->getAvailableTools(),
                'tool_choice' => 'auto',
                'temperature' => 0.8,
                'max_response_output_tokens' => 'inf'
            ]
        ];
    }

    private function getAvailableTools()
    {
        // Define available tools/functions the AI can use
        return [
            [
                'type' => 'function',
                'name' => 'search_knowledge_base',
                'description' => 'Search the internal knowledge base for information',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'query' => [
                            'type' => 'string',
                            'description' => 'The search query'
                        ]
                    ],
                    'required' => ['query']
                ]
            ],
            // Add more tools as needed
        ];
    }

    private function handleOutputItemDone($event) 
    {
        if (isset($event['item']['content'])) {
            foreach ($event['item']['content'] as $content) {
                if ($content['type'] === 'audio' && isset($content['transcript'])) {
                    $this->sendToClient('assistant_transcript', [
                        'response_id' => $event['response_id'],
                        'transcript' => $content['transcript']
                    ]);
                }
            }
        }
    }
} 