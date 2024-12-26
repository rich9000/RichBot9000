<?php

namespace App\Services\OpenAI;
use App\Models\Assistant;
use App\Services\ToolExecutor;
use App\Services\CodingExecutor;



use Illuminate\Support\Facades\Log;

class RealtimeMessageHandler
{
    private $richbotConn;
    private $openaiConn;
    private $chatId;
    private $currentResponse = null;
    private $functionCallBuffer = [];
    private $flowLogger;
    private $assistant;
    
    public function __construct($richbotConn, $openaiConn, $chatId)
    {
        $this->richbotConn = $richbotConn;
        $this->openaiConn = $openaiConn;
        $this->chatId = $chatId;
        $this->flowLogger = new MessageFlowLogger($chatId);
    }

    public function setRichbotConnection($richbotConn)
    {
        $this->richbotConn = $richbotConn;
    }

    public function setAssistant($assistant)
    {
        $this->assistant = $assistant;
    }

    public function setChatId($chatId)
    {
        $this->chatId = $chatId;
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

        

            case 'conversation.item.created':
                $this->handleConversationItemCreated($event);
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

            case 'audio':
                $message = $this->createAudioMessage($data['audio'] ?? $data);
                break;
            case 'function_result':
                $message = $this->createFunctionResultMessage($data);
                break;

            default:
                Log::info("Unhandled OpenAI event type: {$type}", ['event' => $event]);
                break;
        }
    }

    private function handleSessionEvent($message)
    {
        // Log detailed session information
        Log::info("Session event received", [
            'type' => $message['type'],
            'event_id' => $message['event_id'] ?? null,
            'session_id' => $message['session']['id'] ?? null,
            'model' => $message['session']['model'] ?? null,
            'voice' => $message['session']['voice'] ?? null,
            'instructions' => $message['session']['instructions'] ?? null,
            'tools' => $message['session']['tools'] ?? [],
            'full_session_config' => $message['session'] ?? null
        ]);

        // Forward relevant session info to client
        $this->sendToClient('session_update', [
            'session_id' => $message['session']['id'] ?? null,
            'model' => $message['session']['model'] ?? null,
            'voice' => $message['session']['voice'] ?? null
        ]);
    }



    private function handleConversationCreatedEvent($message)
    {
        Log::info("Conversation created event received", [
            'conversation_id' => $message['conversation']['id'] ?? null,
            'chat_id' => $this->chatId
        ]);
    }

    private function handleConversationItemCreated($message)
    {
        if (isset($message['item'])) {
            $this->sendToClient('conversation_update', [
                'item' => $message['item']
            ]);
            
            // If this is a user message and it's completed, create a response
            if (isset($message['item']['role']) && $message['item']['role'] === 'user' && isset($message['item']['status']) && $message['item']['status'] === 'completed') {
                Log::info("Creating response for user message", [
                    'item_id' => $message['item']['id'],
                    'chat_id' => $this->chatId
                ]);
                
                $responseMessage = [
                    'type' => 'response.create'
                ];

                try {
                    Log::info("Sending response.create", [
                        'message_id' => $message['item']['id'],
                        'message' => $responseMessage
                    ]);
                    
                    // Send response.create to OpenAI
                    $this->openaiConn->send(json_encode($responseMessage));

                    $this->flowLogger->logTransformation(
                        'HANDLER',
                        'OPENAI',
                        ['message_id' => $message['item']['id']],
                        $responseMessage,
                        'Created response request'
                    );

                    // Set a flag to track that we're waiting for a response
                    $this->currentResponse = 'pending_' . $message['item']['id'];
                    
                    Log::info("Response creation sent, waiting for response events", [
                        'message_id' => $message['item']['id'],
                        'chat_id' => $this->chatId
                    ]);
                    
                } catch (\Exception $e) {
                    Log::error("Error creating response", [
                        'error' => $e->getMessage(),
                        'message_id' => $message['item']['id'],
                        'chat_id' => $this->chatId,
                        'trace' => $e->getTraceAsString()
                    ]);
                    $this->flowLogger->logDrop('HANDLER', $responseMessage, 'Failed to send: ' . $e->getMessage());
                    
                    // Send error to client
                    $this->sendToClient('error', [
                        'message' => 'Failed to create response: ' . $e->getMessage(),
                        'message_id' => $message['item']['id']
                    ]);
                }
            } else {
                Log::info("Not a user message, skipping message creation", [
                    'item_id' => $message['item']['id'],
                    'chat_id' => $this->chatId
                ]);
            }
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
        $method_name = $message['name'];
        $method_args = json_decode($message['arguments'], true);
        
        Log::info("Function call complete", [
            'call_id' => $callId,
            'name' => $method_name,
            'arguments' => $method_args
        ]);

        $data = null;
        $optional_objects = [
            new ToolExecutor(),
            new CodingExecutor()
        ];

        try {
            // First check if method exists in this class
            if (method_exists($this, $method_name)) {
                Log::info('Executing method on RealtimeMessageHandler:', [
                    'method' => $method_name,
                    'args' => $method_args
                ]);
                $data = call_user_func([$this, $method_name], $method_args);
            } else {
                // Loop through optional objects
                foreach ($optional_objects as $index => $object) {
                    $class_name = get_class($object);
                    if (method_exists($object, $method_name)) {
                        Log::info("Executing method on {$class_name}:", [
                            'method' => $method_name,
                            'args' => $method_args
                        ]);
                        $data = call_user_func([$object, $method_name], $method_args);
                        break;
                    }
                }
            }

            if ($data === null) {
                Log::error("No handler found for method", [
                    'method' => $method_name,
                    'checked_classes' => array_merge(
                        [get_class($this)],
                        array_map(function($obj) { return get_class($obj); }, $optional_objects)
                    )
                ]);
            }

            // Send function output to OpenAI
            $functionOutput = [
                'type' => 'conversation.item.create',
                'item' => [
                    'type' => 'function_call_output',
                    'call_id' => $callId,
                    'output' => json_encode($data)
                ]
            ];

            Log::info("Sending function output to OpenAI", [
                'output' => $functionOutput,
                'call_id' => $callId
            ]);

            $this->openaiConn->send(json_encode($functionOutput));
            $this->openaiConn->send(json_encode(['type'=>'response.create']));

            // Send the function call result to the client as well
            $this->sendToClient('function_call', [
                'name' => $method_name,
                'arguments' => $method_args,
                'call_id' => $callId,
                'result' => $data
            ]);

        } catch (\Exception $e) {
            Log::error("Error executing function call", [
                'error' => $e->getMessage(),
                'method' => $method_name,
                'arguments' => $method_args,
                'trace' => $e->getTraceAsString()
            ]);

            $this->sendToClient('error', [
                'message' => "Function execution failed: {$e->getMessage()}",
                'call_id' => $callId
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

    public function getInitialSessionConfig($assistant = null)
    {
        $defaultInstructions = config('ai.default_instructions') ?? 
            "You are a helpful AI assistant. Be concise and clear in your responses. " .
            "When speaking, use a natural, conversational tone with appropriate pauses. " .
            "If you're not sure about something, say so. " .
            "If you need more information to answer accurately, ask for clarification.";

        $tools = [];
        $instructions = $defaultInstructions;
        $model = null;

        if ($assistant) {
            Log::info("Configuring session with assistant", [
                'assistant_id' => $assistant->id,
                'assistant_name' => $assistant->name,
                'model' => $assistant->model,
                'system_message' => $assistant->system_message,
                'tools_count' => count($assistant->tools)
            ]);
            
            $instructions = $assistant->system_message ?? $defaultInstructions;
            $tools = $assistant->getRealtimeAssistantTools();
            $model = $assistant->model ?? null;

            // Log the tools being configured
            Log::info("Assistant tools configuration", [
                'assistant_id' => $assistant->id,
                'tools' => $tools
            ]);
        }

        if (empty($tools)) {
            $tools = $this->getAvailableTools();
            Log::info("Using default tools configuration", [
                'tools' => $tools
            ]);
        }

        $config = [
            'type' => 'session.update',
            'event_id' => 'init_' . uniqid(),
            'session' => [
                'modalities' => ['text', 'audio'],
                'instructions' => $instructions,
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
                'tools' => $tools,
                'tool_choice' => 'auto',
                'temperature' => 0.8,
                'max_response_output_tokens' => 'inf'
            ]
        ];

        

        // Log the final session configuration
        Log::info("Final session configuration", [
            'event_id' => $config['event_id'],
            'model' => $model,
            'instructions_length' => strlen($instructions),
            'tools_count' => count($tools),
            'full_config' => $config
        ]);

        return $config;
    }

    private function getAvailableTools()
    {
        $tools = [
            [
                'type' => 'function',
                'function' => [
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
                ]
            ]
        ];

        Log::info("Default tools configuration", [
            'tools' => $tools
        ]);

        return $tools;
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

    private function handleSpeechEvent($event)
    {
        Log::info("Speech event received", [
            'type' => $event['type'],
            'chat_id' => $this->chatId
        ]);

        $this->sendToClient($event['type'], [
            'type' => $event['type'],
            'timestamp' => time()
        ]);
    }

    private function handleAudioTranscriptionComplete($event)
    {
        Log::info("Audio transcription completed", [
            'chat_id' => $this->chatId,
            'event' => $event
        ]);

        if (isset($event['item']) && isset($event['item']['content'])) {
            foreach ($event['item']['content'] as $content) {
                if ($content['type'] === 'input_audio' && isset($content['transcript'])) {
                    $this->sendToClient('audio_transcription_complete', [
                        'item_id' => $event['item']['id'],
                        'transcript' => $content['transcript']
                    ]);
                }
            }
        }
    }

    private function handleAudioTranscriptionFailed($event)
    {
        Log::error("Audio transcription failed", [
            'chat_id' => $this->chatId,
            'error' => $event['error'] ?? 'Unknown error'
        ]);

        $this->sendToClient('audio_transcription_failed', [
            'error' => $event['error'] ?? 'Audio transcription failed',
            'item_id' => $event['item']['id'] ?? null
        ]);
    }
} 