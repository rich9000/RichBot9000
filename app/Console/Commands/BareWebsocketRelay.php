<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use React\EventLoop\Factory;
use Ratchet\Client\Connector;
use React\Socket\Connector as ReactConnector;
use Illuminate\Support\Facades\Log;
use App\Models\Assistant;
use App\Services\Logging\OpenAILogger;
use App\Services\ToolExecutor;
use App\Services\CodingExecutor;
use App\Services\Executors\RichBotExecutor;
use App\Services\Executors\SurveyExecutor;
use App\Models\User;
use App\Models\Tool;
use App\Services\Executors\RainbowExecutor;
use App\Services\Executors\RainbowKnowledgeBaseExecutor;
use App\Services\Executors\RainbowDashboardTicketExecutor;
use App\Models\Conversation;
use App\Models\AudioFile;
use App\Models\ConversationPath;
use App\Services\OpenAISessionMaker;
use App\Services\Executors\ContactExecutor;
use Illuminate\Support\Facades\File;




class BareWebsocketRelay extends Command
{
    protected $signature = 'bare:assistant {room} {assistant_id} {--conversation_id=}  {--debug} {--audio-output-pcm16} {--audio-output-g711_alaw} {--user_id=} {--richbot_token=} {--second-delay=}';
    protected $description = 'Start a WebSocket relay between OpenAI and the bare WebSocket server';

    private $richbotConn;
    private $session_data = [];
    private $openaiConn;
    private $room;
    private $assistant;
    private $callSid;
    private $conversation;
    private $conversation_id; //OPTIONAL: conversation id for the current conversation in string format
    private $currentResponse = null;
    private $messageCount = 0;
    private $lastHeartbeat = null;
    private $startTime;
    private $isDebug = false;
    private $openaiMessageCount = 0;
    private $bareMessageCount = 0;
    private $lastMessageTime;
    private $outputFormat = 'g711_ulaw';
    private $userId;
    private $user;
    private $richbotToken;
    private $secondDelay;
    private $isSpeechActive = false;
    private $pendingResponseIds = [];
    private $turnDetectionSettings;
    private $defaultTurnDetectionSettings = [
        'type' => 'server_vad',
        'silence_duration_ms' => 1000,
        'create_response' => true
    ];

    // DTMF tracking properties
    private $dtmfHistory = [];
    private $dtmfWindowStart = null;
    private $dtmfWindowDuration = 5; // seconds
    private $dtmfPatterns = [
        'emergency' => ['911'],
        'help' => ['55'],
        'transfer' => ['*1'],
        'restart' => ['*2'],
        'load_assistant' => ['*3'],
        'pause' => ['*4'],
        'resume' => ['*5'],
        'clear_history' => ['*6'],
        // Add more patterns as needed
    ];

    
    public function handle()
    {



        $this->turnDetectionSettings = $this->defaultTurnDetectionSettings;

        $this->startTime = time();
        $this->isDebug = $this->option('debug');
        $this->room = $this->argument('room');
        $this->conversation_id = $this->option('conversation_id') ?? null;
        $assistantId = $this->argument('assistant_id');
        $this->userId = $this->option('user_id') ?? null;
        $this->richbotToken = $this->option('richbot_token') ?? null;
        $this->secondDelay = $this->option('second-delay') ?? 0;

        $this->info("Starting Bare WebSocket Relay");
        $this->info("===========================");
        $this->info("Room: {$this->room}");
        $this->info("Assistant ID: {$assistantId}");
        $this->info("Debug Mode: " . ($this->isDebug ? 'ON' : 'OFF'));




        
        if ($this->richbotToken) {
            $this->info("Richbot Token: {$this->richbotToken}");
        }

        if($this->userId)
        {
            $user = User::find($this->userId);
            if($user)
            {
                $this->user = $user;
                $this->info("User: {$user->name}");
            }
        }
        if($this->secondDelay)
        {
            $this->info("Second Delay: {$this->secondDelay}");
            sleep($this->secondDelay);
        }

        $this->outputFormat = $this->option('audio-output-pcm16') ? 'pcm16' : 'g711_ulaw';
        $this->outputFormat = $this->option('audio-output-g711_alaw') ? 'g711_alaw' : 'g711_ulaw';

        $this->info('Starting Bare WebSocket Relay');
        $this->info('==========================');
        $this->info("Room: {$this->room}");
        $this->info("Assistant ID: {$assistantId}");
        $this->info("Debug Mode: " . ($this->isDebug ? 'ON' : 'OFF'));
        if ($this->userId) {
            $this->info("User ID: {$this->userId}");
        }
        $this->info('==========================');

        $this->assistant = Assistant::find($assistantId);

        if (!$this->assistant) {
            $this->error("Assistant not found: {$assistantId}");
            Log::error("[OPENAI RELAY] Assistant not found", ['assistant_id' => $assistantId]);
            return;
        }

        if($this->conversation_id) {
            $this->conversation = Conversation::find($this->conversation_id);
        } else {
            $this->conversation = Conversation::create([
                'room' => $this->room,
                'assistant_id' => $assistantId,
                'assistant_type' => 'assistant',
                //'user_id' => $this->userId
            ]);
        }

        $this->info("Assistant: {$this->assistant->name}");
        $this->info("Model: {$this->assistant->model}");

        // Log connection details
        Log::info("[OPENAI RELAY] Starting connection", [
            'room' => $this->room,
            'assistant_id' => $assistantId,
            'assistant_name' => $this->assistant->name,
            'debug_mode' => $this->isDebug,
            'user_id' => $this->userId,
            'has_richbot_token' => !empty($this->richbotToken)
        ]);

        $initialConfig = $this->getInitialSessionConfig();

        $loop = Factory::create();
        $connector = new Connector($loop, new ReactConnector($loop, [
            'tls' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]));

        // Connect to Bare WebSocket Server
        $bareUrl = "wss://richbot9000.local:9502/openai-realtime/{$this->room}/{$this->assistant->id}";
        $connector($bareUrl)
            ->then(function($bareConn) use ($loop, $connector, $initialConfig) {
                Log::info("[OPENAI RELAY] Connected to Bare WebSocket", [
                    'room' => $this->room,
                    'assistant' => $this->assistant->id
                ]);

                $this->richbotConn = $bareConn;
                
                // Join the specified room
                $this->richbotConn->send(json_encode([
                    'type' => 'join',
                    'room' => $this->room
                ]));
                      
                // Connect to OpenAI
                
                $openaiUrl = "wss://api.openai.com/v1/realtime?model=gpt-4o-mini-realtime-preview";
                //$openaiUrl = "wss://api.openai.com/v1/realtime?model=gpt-4o-mini-2024-07-18";
                $openaiHeaders = [
                    'Authorization' => 'Bearer ' . config('services.openai.api_key'),
                    'OpenAI-Beta' => 'realtime=v1'
                ];

                Log::info("[OPENAI RELAY] Connecting to OpenAI WebSocket");

                $connector($openaiUrl, [], $openaiHeaders)
                    ->then(function($openaiConn) use ($loop, $initialConfig) {
                        Log::info("[OPENAI RELAY] Connected to OpenAI WebSocket");

                        $this->openaiConn = $openaiConn;
                        $this->lastMessageTime = time();

                        // Add inactivity checker timer
                        $loop->addPeriodicTimer(2, function() {
                            $timeSinceLastMessage = time() - $this->lastMessageTime;
                            if ($timeSinceLastMessage >= 60) {
                                try {
                                    $this->openaiConn->send(json_encode(['type' => 'response.create']));
                                    Log::debug("[OPENAI RELAY] Sent response.create due to inactivity", [
                                        'seconds_inactive' => $timeSinceLastMessage
                                    ]);
                                } catch (\Exception $e) {
                                    Log::error("[OPENAI RELAY] Failed to send inactivity response.create", [
                                        'error' => $e->getMessage()
                                    ]);
                                }
                                // Reset timer after sending
                                $this->lastMessageTime = time();
                            }
                        });

                        // Handle messages from Bare WebSocket Server
                        $this->richbotConn->on('message', function($msg) {
                            $this->handleBareMessage($msg);
                        });

                        // Handle messages from OpenAI
                        $this->openaiConn->on('message', function($msg) {
                            $this->handleOpenAIMessage($msg);
                        });

                        // Set up heartbeat
                        $loop->addPeriodicTimer(30, function() {
                            $this->sendHeartbeat();
                        });

                        // Handle connection closures
                        $this->richbotConn->on('close', function() use ($loop) {
                            Log::error("[OPENAI RELAY] Bare WebSocket connection closed");
                            $loop->stop();
                        });

                        $this->openaiConn->on('close', function() use ($loop) {
                            Log::error("[OPENAI RELAY] OpenAI connection closed");
                            $loop->stop();
                        });
                        
                        // Send initial session configuration
                        Log::info("[OPENAI RELAY] Sending initial session configuration", [
                            'config' => $initialConfig
                        ]);

                        $this->openaiConn->send(json_encode($initialConfig));

                        //
                        $path_state = $this->conversation->path_state;
                        if($path_state['twilio_call']['direction'] == 'inbound')
                        {
                            $this->openaiConn->send(json_encode(['type' => 'response.create']));
                        }

                        //$this->openaiConn->send(json_encode(['type' => 'response.create']));

                    }, function($e) use ($loop) {
                        Log::error("[OPENAI RELAY] Could not connect to OpenAI", [
                            'error' => $e->getMessage()
                        ]);
                        $loop->stop();
                    });

            }, function($e) use ($loop) {
                Log::error("[OPENAI RELAY] Could not connect to Bare WebSocket", [
                    'error' => $e->getMessage()
                ]);
                $loop->stop();
            });

        $loop->run();
    }

    private function displayStatus()
    {
        $uptime = time() - $this->startTime;
        $lastHeartbeatAgo = $this->lastHeartbeat ? (time() - $this->lastHeartbeat) : 'N/A';

        $this->line("\n=== Relay Status ===");
        $this->line(sprintf("Uptime: %02d:%02d:%02d", 
            floor($uptime / 3600),
            floor(($uptime % 3600) / 60),
            $uptime % 60
        ));
        $this->line("Total Messages: {$this->messageCount}");
        $this->line("OpenAI Messages: {$this->openaiMessageCount}");
        $this->line("Bare Messages: {$this->bareMessageCount}");
        $this->line("Last Heartbeat: {$lastHeartbeatAgo}s ago");
        $this->line("Current Room: {$this->room}");
        $this->line("Assistant: {$this->assistant->name}");
        $this->line("Connections:");
        $this->line(" - Bare WebSocket: " . ($this->richbotConn ? 'Active' : 'Inactive'));
        $this->line(" - OpenAI: " . ($this->openaiConn ? 'Active' : 'Inactive'));
        if ($this->currentResponse) {
            $this->line("Current Response: {$this->currentResponse}");
        }
    }

    private function debugLog($message, $context = [])
    {
        if ($this->isDebug) {
            $this->info("[DEBUG] " . $message . ($context ? ": " . json_encode($context) : ""));
            Log::debug("[OPENAI RELAY] " . $message, $context);

        }
        
    }

    private function handleBareMessage($msg)
    {
        try {
            $message = json_decode($msg, true);
            $this->bareMessageCount++;
            $this->lastMessageTime = time();

            // Handle case where message might be a string or invalid JSON
            if (!is_array($message)) {
                Log::debug("[BARE RELAY] Received non-JSON message", [
                    'message' => $msg,
                    'room' => $this->room,
                    'timestamp' => date('Y-m-d H:i:s.u')
                ]);
                return;
            }

            // Get message type from either type or event field, with fallback to empty string
            $type = $message['type'] ?? $message['event'] ?? '';

            if($this->isDebug)
            {
                Log::info("[BARE RELAY] Received message", [
                    'type' => $type,
                    'timestamp' => date('Y-m-d H:i:s.u'),
                    'message_size' => strlen($msg),
                    'has_media' => isset($message['media']) || isset($message['data']) || isset($message['delta']),
                    'room' => $this->room,
                    'configured_format' => $this->outputFormat,
                    'raw_message' => $message
                ]);
            }

            if (empty($type)) {
                Log::debug("[BARE RELAY] Message has no type or event", [
                    'message' => $message,
                    'room' => $this->room,
                    'timestamp' => date('Y-m-d H:i:s.u')
                ]);
                return;
            }

            switch ($type) {
                case 'mark':
                    $markName = $message['mark']['name'] ?? null;
                    
                    Log::info("[BARE RELAY] Mark received from Twilio", [
                        'mark' => $markName,
                        'pending_responses' => $this->pendingResponseIds,
                        'room' => $this->room,
                        'timestamp' => date('Y-m-d H:i:s.u')
                    ]);

                    // Remove the response ID from pending list
                    if ($markName && in_array($markName, $this->pendingResponseIds)) {
                        $this->pendingResponseIds = array_diff($this->pendingResponseIds, [$markName]);
                        
                        Log::info("[BARE RELAY] Removed response from pending list", [
                            'mark' => $markName,
                            'remaining_pending' => $this->pendingResponseIds,
                            'room' => $this->room,
                            'timestamp' => date('Y-m-d H:i:s.u')
                        ]);

                        // Only re-enable turn detection if no more pending responses
                        if (empty($this->pendingResponseIds)) {
                            $this->isSpeechActive = false; // Reset speech active flag when no more pending responses
                            
                            $this->updateTurnDetection($this->turnDetectionSettings);
                           
                                                       
                            Log::info("[BARE RELAY] Turn detection re-enabled and speech active reset", [
                                'room' => $this->room,
                                'timestamp' => date('Y-m-d H:i:s.u')
                            ]);
                        } else {
                            Log::info("[BARE RELAY] Still waiting for more marks", [
                                'pending_responses' => $this->pendingResponseIds,
                                'room' => $this->room,
                                'timestamp' => date('Y-m-d H:i:s.u')
                            ]);
                        }
                    }
                    break;

                case 'dtmf':
                    if (isset($message['dtmf']) && isset($message['dtmf']['digit'])) {
                        Log::info("[BARE RELAY] DTMF received", [
                            'digit' => $message['dtmf']['digit'],
                            'room' => $this->room,
                            'timestamp' => date('Y-m-d H:i:s.u')
                        ]);

                        // Track DTMF input
                        $this->handleDtmf($message['dtmf']['digit']);

                        // Convert to OpenAI format
                        $openaiMessage = [
                            'type' => 'conversation.item.create',
                            'item' => [
                                'type' => 'message',
                                'role' => 'user',
                                'content' => [
                                    [
                                        'type' => 'input_text',
                                        'text' => $message['dtmf']['digit']
                                    ]
                                ]
                            ]
                        ];
                        
                        $this->openaiConn->send(json_encode($openaiMessage));
                       // $this->openaiConn->send(json_encode(['type' => 'response.create']));
                    }
                    break;

                case 'input_audio_buffer.speech_started':
                    $this->isSpeechActive = true;
                    Log::info("[BARE RELAY] Speech started, ********* pausing output buffer", [
                        'room' => $this->room,
                        'timestamp' => date('Y-m-d H:i:s.u')
                    ]);

                    $this->clearAudioQueue();
                    break;

                case 'input_audio_buffer.speech_stopped':
                    $this->isSpeechActive = false;
                    Log::info("[BARE RELAY] Speech stopped, resuming output buffer", [
                        'room' => $this->room,
                        'timestamp' => date('Y-m-d H:i:s.u')
                    ]);
                    break;

                case 'message':
                    if (isset($message['content'])) {
                        Log::info("[BARE RELAY] Text message received", [
                            'content_length' => strlen($message['content']),
                            'room' => $this->room,
                            'timestamp' => date('Y-m-d H:i:s.u')
                        ]);
                        
                        // Convert to OpenAI format
                        $openaiMessage = [
                            'type' => 'conversation.item.create',
                            'item' => [
                                'type' => 'message',
                                'role' => 'user',
                                'content' => [
                                    [
                                        'type' => 'input_text',
                                        'text' => $message['content']
                                    ]
                                ]
                            ]
                        ];
                        
                        $this->openaiConn->send(json_encode($openaiMessage));
                        $this->openaiConn->send(json_encode(['type' => 'response.create']));
                    }
                    break;

                case 'media_data':
                    if (isset($message['data'])) {
                        Log::info("[BARE RELAY] Media data received", [
                            'bytes' => strlen($message['data']),
                            'room' => $this->room,
                            'timestamp' => date('Y-m-d H:i:s.u')
                        ]);
                        $this->forwardToOpenAI($message['data'], 'audio');
                    }
                    break;

                case 'input_audio_buffer.append':
                    if (isset($message['audio'])) {
                        // Add format verification logging
                        if($this->isDebug)
                        {
                            Log::info("[BARE RELAY] Input audio format verification", [
                                'configured_format' => $this->outputFormat,
                                'audio_bytes' => strlen($message['audio']),
                                'format' => $message['format'] ?? 'not_specified',
                                'room' => $this->room,
                                'timestamp' => date('Y-m-d H:i:s.u')
                            ]);
                        }

                        $this->forwardToOpenAI($message['audio'], 'audio');

                    } else {

                        Log::error("[BARE RELAY] messed up message no audio", [
                            'type' => $message['type'],
                            'room' => $this->room,
                            'timestamp' => date('Y-m-d H:i:s.u'),
                            'message' => $message
                        ]);

                    }
                    break;

                case 'media_ready':
                    Log::info("[BARE RELAY] Media system ready", [
                        'room' => $this->room,
                        'timestamp' => date('Y-m-d H:i:s.u')
                    ]);
                    break;

                case 'left':
                    Log::info("[BARE RELAY] Left room", [
                        'room' => $message['room'],
                        'timestamp' => date('Y-m-d H:i:s.u')
                    ]);
                    break;
                case 'response.cancel':
                    Log::info("[BARE RELAY] Response cancelled", [
                        'room' => $this->room,
                        'timestamp' => date('Y-m-d H:i:s.u')
                    ]);
                    break;
                case 'response.done':
                    Log::info("[BARE RELAY] Response done", [
                        'room' => $this->room,
                        'timestamp' => date('Y-m-d H:i:s.u')
                    ]);
                    break;
                case 'joined':
                    Log::info("[BARE RELAY] Joined room", [
                        'room' => $this->room,
                        'timestamp' => date('Y-m-d H:i:s.u')
                    ]);
                    break;

                case 'session.update':
                    Log::info("[BARE RELAY] Session update", [
                        'room' => $this->room,
                        'timestamp' => date('Y-m-d H:i:s.u'),
                        'message' => $message
                    ]);

                    

                    $this->openaiConn->send(json_encode($message));
                    break;


                case 'response.create':
                    Log::info("[BARE RELAY] Response create", [
                        'room' => $this->room,
                        'timestamp' => date('Y-m-d H:i:s.u')
                    ]);


                    $this->openaiConn->send(json_encode($message));



                    break;
                    
                    
                    
                    

                default:
                    Log::debug("[BARE RELAY] Unknown message type", [
                        'type' => $type,
                        'room' => $this->room,
                        'timestamp' => date('Y-m-d H:i:s.u')
                    ]);
            }
        } catch (\Exception $e) {
            Log::error("[BARE RELAY] Error processing Bare message", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'original_message' => $msg,
                'room' => $this->room,
                'timestamp' => date('Y-m-d H:i:s.u')
            ]);
        }
    }

    private function handleOpenAIMessage($msg)
    {
        try {
            $message = json_decode($msg->getPayload(), true);
            $this->messageCount++;
            $this->openaiMessageCount++;
            $this->lastMessageTime = time();

            $this->logOpenAIMessage($message['type'] ?? 'unknown', $message);

            $this->logOpenAIResponse($message['type'] ?? 'unknown', $message);

            $type = $message['type'] ?? '';

            switch ($type) {               
                case 'input_audio_buffer.speech_started':
                    if (!$this->isSpeechActive) {
                        $this->isSpeechActive = true;
                        Log::info("[BARE RELAY] Speech started, handling interruption", [
                            'room' => $this->room,
                            'timestamp' => date('Y-m-d H:i:s.u')
                        ]);

                        $interruptionSequence = $this->generateSpeechInterruptionSequence($this->room);

                        // Clear client buffer
                        $this->richbotConn->send(json_encode($interruptionSequence['client']));
                        Log::info("[BARE RELAY] Cleared client buffer", [
                            'room' => $this->room,
                            'timestamp' => date('Y-m-d H:i:s.u')
                        ]);

                        // Cancel OpenAI response
                        $this->openaiConn->send(json_encode($interruptionSequence['openai']));
                        Log::info("[BARE RELAY] Cancelled OpenAI response", [
                            'room' => $this->room,
                            'timestamp' => date('Y-m-d H:i:s.u')
                        ]);

                        $this->clearAudioQueue();
                    } else {
                        Log::info("[BARE RELAY] Speech already active, skipping interruption", [
                            'room' => $this->room,
                            'timestamp' => date('Y-m-d H:i:s.u')
                        ]);
                    }
                    break;

                case 'response.created':
                    $response_id = $message['response']['id'];
                    $event_id = $message['event_id'] ?? null;

                    $this->currentResponse = $response_id;
                    $this->pendingResponseIds[] = $response_id;

                    Log::info("[BARE RELAY] Response created received", [
                        'response_id' => $response_id,
                        'event_id' => $event_id,
                        'pending_responses' => $this->pendingResponseIds,
                        'room' => $this->room,
                        'timestamp' => date('Y-m-d H:i:s.u')
                    ]);

                    // Send mark to Twilio
                    $markMessage = [
                        'event' => 'mark',
                        'streamSid' => false,
                        'mark' => [
                            'name' => $response_id
                        ]
                    ];
                    
                    $this->richbotConn->send(json_encode($markMessage));
                    
                    Log::info("[BARE RELAY] ************* Mark sent to Twilio", [
                        'response_id' => $response_id,
                        'pending_responses' => $this->pendingResponseIds,
                        'room' => $this->room,
                        'timestamp' => date('Y-m-d H:i:s.u')
                    ]);

                    // Turn off turn detection until mark is received
                    $this->updateTurnDetection([]);
                    
                    Log::info("[BARE RELAY] *************** Turn detection disabled waiting for mark", [
                        'response_id' => $response_id,
                        'pending_responses' => $this->pendingResponseIds,
                        'room' => $this->room,
                        'timestamp' => date('Y-m-d H:i:s.u')
                    ]);
                    break;

                case 'response.audio.delta':
                    if (isset($message['delta']) && !$this->isSpeechActive) {                      
                        $mediaMessage = json_encode([
                            'event' => 'media',
                            'streamSid' => false,
                            'media' => [
                                'payload' => $message['delta']
                            ]
                        ]);
                        
                        $this->richbotConn->send($mediaMessage);
                    } else if ($this->isSpeechActive) {
                        Log::debug("[BARE RELAY] Skipping audio delta due to active speech", [
                            'room' => $this->room,
                            'timestamp' => date('Y-m-d H:i:s.u')
                        ]);
                    }
                    break;

                case 'error':
                    Log::error("[BARE RELAY] OpenAI Error", [
                        'error' => $message['error'],
                        'room' => $this->room,
                        'timestamp' => date('Y-m-d H:i:s.u')
                    ]);
                    break;

                case 'response.function_call_arguments.delta':
                    Log::info("[BARE RELAY] Function call arguments delta", [
                        'call_id' => $message['call_id'] ?? 'unknown',
                        'delta' => $message['delta'] ?? '',
                        'room' => $this->room,
                        'timestamp' => date('Y-m-d H:i:s.u')
                    ]);
                    break;

                case 'response.function_call_arguments.done':
                    Log::info("[BARE RELAY] Function call arguments complete", [
                        'call_id' => $message['call_id'] ?? 'unknown',
                        'name' => $message['name'] ?? 'unknown',
                        'arguments' => $message['arguments'] ?? '',
                        'room' => $this->room,
                        'timestamp' => date('Y-m-d H:i:s.u')
                    ]);
                    break;

                case 'response.output_item.done':
                    Log::info("[BARE RELAY] Output item done", [
                        'item' => $message['item'],
                        'room' => $this->room,
                        'timestamp' => date('Y-m-d H:i:s.u')
                    ]);
                    if (isset($message['item']) && $message['item']['type'] === 'function_call') {

                        Log::info("[BARE RELAY][FUNCTION CALL] Output item done", [
                            'item' => $message['item'],
                            'name' => $message['item']['name'],
                            'arguments' => $message['item']['arguments'],
                            'room' => $this->room,
                            'timestamp' => date('Y-m-d H:i:s.u')
                        ]);

                        $data = $this->handleFunction($message['item']);

                        $log_data = [
                            'message' => $message,
                            'room' => $this->room,
                            'timestamp' => date('Y-m-d H:i:s.u'),
                            'data' => $data
                        ];

                        Log::channel('openai_tools')->info('Function call', $log_data);

                        Log::info("[BARE RELAY][FUNCTION CALL] Received message *********", [
                            'message' => $message,
                            'room' => $this->room,
                            'timestamp' => date('Y-m-d H:i:s.u'),
                            'assistant' => $this->assistant->success_tool_id,                            
                            'name' => $message['item']['name'],
                        ]);


                        Log::info("[BARE RELAY][FUNCTION CALL] data &&&&&&&&&&&&", [
                            'data' => $data,                         
                        ]);

                        $success_tool = Tool::find($this->assistant->success_tool_id);

                        if($success_tool && $success_tool->name == $message['item']['name']) {

                            Log::info('[BareWebsocketRelay][FUNCTION CALLING] Success tool matched', [
                               'assistant' => $this->assistant->success_assistant_id
                            ]);

                            if($data['success'] == true) {

                                Log::info('[BareWebsocketRelay][FUNCTION CALLING] Success tool matched', [
                                    'tool_name' => $success_tool->name,
                                   
                                ]);
                                
                        

                                if($this->assistant->success_assistant_id) {

                                    $success_assistant = Assistant::find($this->assistant->success_assistant_id);

                                    $this->updateAssistant($success_assistant);

                                    Log::info('[BareWebsocketRelay][FUNCTION CALLING] Success tool matched, ending call', [
                                        'tool_name' => $success_tool->name,
                                        'room' => $this->room
                                    ]);

                                    $this->playAudioFile(10);

                                    
                                } else {

                                    Log::info('[BareWebsocketRelay][FUNCTION CALLING] Success tool matched no success assistant, ending call', [
                                        'tool_name' => $success_tool->name,
                                        'room' => $this->room
                                    ]);

                                    $this->sendEndCall();
                                    return; 
                                }
                            }

                         
                        }

                        $this->openaiConn->send(json_encode(['type' => 'response.create']));


                    }

                    
                    break;               

                default:
                   // Log::debug("[BARE RELAY] Unhandled OpenAI message type", [
                   //     'type' => $type,
                   //     'room' => $this->room,
                   //     'message' => $message['type'],
                   //     'timestamp' => date('Y-m-d H:i:s.u')
                   // ]);
                    
            }

            //forward to the bare websocket server
            $this->richbotConn->send(json_encode($message));

            return;

        } catch (\Exception $e) {
            Log::error("[BARE RELAY] Error processing OpenAI message", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'room' => $this->room,
                'timestamp' => date('Y-m-d H:i:s.u')
            ]);
        }
    }


    private function handleFunction($data)
    {
        Log::info('[BareWebsocketRelay][FUNCTION CALLING] Handling function', [
            'data' => $data
        ]);

        try {
            $callId = $data['call_id'] ?? null;
            $method_name = $data['name'] ?? null;
            $method_args = $data['arguments'] ?? [];

            // Decode JSON arguments if they're a string
            if (is_string($method_args)) {
                $method_args = json_decode($method_args, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    Log::error('[BareWebsocketRelay][FUNCTION CALLING] Invalid JSON arguments', [
                        'arguments' => $method_args,
                        'error' => json_last_error_msg()
                    ]);
                    return null;
                }
            }

            Log::info('[BareWebsocketRelay][FUNCTION CALLING] Starting function call', [
                'call_id' => $callId,
                'method' => $method_name,
                'arguments' => $method_args
            ]);

            if (!$method_name) {
                Log::error('[BareWebsocketRelay][FUNCTION CALLING] Missing method name');
                return null;
            }

            $data = null;

            $control_method_names =['websocket_control_change_assistant','end_call','end_conversation','end_websocket_connection','pause_conversation','resume_conversation'];
            if(in_array($method_name, $control_method_names)) {

                if($method_name === 'end_call') {                    
                    //send twilio message to end call to all room participants
                    $this->sendEndCall();
                    return ['status' => 'success', 'message' => 'Call ended'];            
                    
                } else if($method_name === 'end_conversation') {
                    
                    //$this->endConversation();

                } else if($method_name === 'end_websocket_connection') {

                    $this->sendEndCall();

                    
                    return ['status' => 'success', 'message' => 'Websocket connection ended'];
                    

                } else if($method_name === 'pause_conversation') {
//                    $this->pauseConversation();
                } else if($method_name === 'resume_conversation') { 
//                    $this->resumeConversation();
                }

            }

            // Check if method exists in this class
            if (method_exists($this, $method_name)) {
                Log::info('[BareWebsocketRelay][FUNCTION CALLING] Executing method in BareWebsocketRelay', [
                    'method' => $method_name,
                    'args' => $method_args
                ]);
                $data = call_user_func([$this, $method_name], $method_args);
                Log::info('[BareWebsocketRelay][FUNCTION CALLING] Method execution completed', [
                    'method' => $method_name,
                    'data' => $data
                ]);
            } else {
                // Loop through optional objects
                $optional_objects = [
                    new ContactExecutor($this->user),
                    new ToolExecutor($this->user),
                    new CodingExecutor($this->user),
                    new SurveyExecutor($this->user),
                    new RainbowExecutor($this->user),
                    new RainbowDashboardTicketExecutor($this->conversation),
                    new RainbowKnowledgeBaseExecutor(),
                    new RichBotExecutor($this->user),
                    new BaseToolsExecutor($this->user),
                    
                ];

                foreach ($optional_objects as $index => $object) {
                    $class_name = get_class($object);
                    if (method_exists($object, $method_name)) {                       
                        if(method_exists($object, 'setConversation')) {
                            $object->setConversation($this->conversation);  
                        }

                        if(method_exists($object, 'setRelayObject')) {
                            $object->setRelayObject($this);
                        }
                       
                        Log::info('[BareWebsocketRelay][FUNCTION CALLING] Executing method on optional object', [
                            'class' => $class_name,
                            'method' => $method_name,
                            'args' => $method_args
                        ]);

                        $data = call_user_func([$object, $method_name], $method_args);
                        
                        $this->playAudioFile(11);

                        Log::info('[BareWebsocketRelay][FUNCTION CALLING] Optional object method execution completed', [
                            'class' => $class_name,
                            'method' => $method_name,
                            'has_data' => !is_null($data)
                        ]);
                        break;
                    }
                }
            }

            if($data === null) {
                Log::error('[BareWebsocketRelay][FUNCTION CALLING] No data returned from function call', [
                    'method' => $method_name,
                    'args' => $method_args
                ]);
            }
    
            // Send function output back to OpenAI
            $functionOutput = [
                'type' => 'conversation.item.create',
                'item' => [
                    'type' => 'function_call_output',
                    'call_id' => $callId,
                    'output' => json_encode($data)
                ]
            ];

            if($this->conversation_id) {
                $this->conversation->addMessage('tool', json_encode($data));
            }
    
            $this->openaiConn->send(json_encode($functionOutput));
           
        } catch (\Exception $e) {
            Log::error('[BareWebsocketRelay][FUNCTION CALLING] Function execution failed', [
                'error' => $e->getMessage(),
                'method' => $method_name ?? 'unknown',
                'arguments' => $method_args ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);
        }

        return $data;
    }


    private function sendEndCall()
    {
        Log::info('[BareWebsocketRelay][SEND END CALL] Sending end call message', [
            'room' => $this->room,
            'timestamp' => date('Y-m-d H:i:s.u')
        ]);

        $endCallMessage = [
            'type' => 'end_call',
            'room' => $this->room,
            'timestamp' => date('Y-m-d H:i:s.u')
        ];

        // Send to the room
       $send_result = $this->richbotConn->send(json_encode($endCallMessage));
        
        Log::info('[BareWebsocketRelay][SEND END CALL] End call message sent', [
            'room' => $this->room,
            'message' => $endCallMessage,
            'send_result' => $send_result
        ]);

        // Also send a message to the room to notify all participants
        $this->richbotConn->send(json_encode([
            'type' => 'message',
            'room' => $this->room,
            'message' => 'Call ended'
        ]));
    }
    


    private function logOpenAIMessage(string $type, array $data)
    {
        $timestamp = date('Y-m-d_H:i:s.u');
        $logFile = storage_path('logs/openai_incoming.log');
        
        $logData = [
            'timestamp' => $timestamp,
            'type' => $type,
            'payload_size' => isset($data['audio']) ? strlen($data['audio']) : (isset($data['text']) ? strlen($data['text']) : 0),
            'has_audio' => isset($data['audio']),
            'has_text' => isset($data['text'])
        ];

        file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND);
    }

    private function logOpenAIResponse(string $type, array $data)
    {
        $timestamp = date('Y-m-d_H:i:s.u');
        $logFile = storage_path('logs/openai_outgoing.log');
        
        $logData = [
            'timestamp' => $timestamp,
            'type' => $type,
            'payload_size' => isset($data['delta']) ? strlen($data['delta']) : 0,
            'has_delta' => isset($data['delta'])
        ];

        file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND);
    }

    private function forwardToOpenAI($message, $type = 'text')
    {
        try {
            if ($type === 'text') {
                $this->logOpenAIMessage('text', ['text' => $message]);
                
                $this->openaiConn->send(json_encode([
                    'type' => 'conversation.item.create',
                    'item' => [
                        'type' => 'text',
                        'text' => $message
                    ]
                ]));
                $this->openaiConn->send(json_encode(['type' => 'response.create']));
             
            } else if ($type === 'audio') {                
                $this->logOpenAIMessage('audio', ['audio' => $message]);
                
                $this->openaiConn->send(json_encode([
                    'type' => 'input_audio_buffer.append',
                    'audio' => $message,                    
                ]));

            } else {
                Log::error("[BARE RELAY] Unknown message type", [
                    'type' => $type,
                    'room' => $this->room,
                    'timestamp' => date('Y-m-d H:i:s.u')
                ]);
            }
          
        } catch (\Exception $e) {
            Log::error("[BARE RELAY] Failed to forward message to OpenAI", [
                'error' => $e->getMessage(),
                'message' => $message,
                'type' => $type,
                'room' => $this->room,
                'timestamp' => date('Y-m-d H:i:s.u')
            ]);
        }
    }

    private function sendHeartbeat()
    {
        try {
            $this->lastHeartbeat = time();
            Log::info("[BARE RELAY] Sending heartbeat", [
                'room' => $this->room,
                'timestamp' => date('Y-m-d H:i:s.u')
            ]);
            
            $this->richbotConn->send(json_encode([
                'type' => 'ping',
                'time' => $this->lastHeartbeat
            ]));
            
          //  $this->openaiConn->send(json_encode(['type' => 'response.create']));
            
            Log::info("[BARE RELAY] Heartbeat sent successfully", [
                'room' => $this->room,
                'timestamp' => date('Y-m-d H:i:s.u')
            ]);
        } catch (\Exception $e) {
            Log::error("[BARE RELAY] Heartbeat error", [
                'error' => $e->getMessage(),
                'room' => $this->room,
                'timestamp' => date('Y-m-d H:i:s.u')
            ]);
        }
    }

    private function getInitialSessionConfig()
    {
        $outputFormat = $this->option('audio-output-pcm16') ? 'pcm16' : 'g711_ulaw';
        $inputFormat = $this->option('audio-output-pcm16') ? 'pcm16' : 'g711_ulaw';


        Log::info("[BARE RELAY] Getting initial session config", [
            'room' => $this->room,
            'timestamp' => date('Y-m-d H:i:s.u'),
            'inputFormat' => $inputFormat,
            'outputFormat' => $outputFormat
        ]);

        return [
            'type' => 'session.update',
            'event_id' => 'init_' . uniqid(),
            'session' => [
                'input_audio_transcription' => [
                    'model' => "whisper-1"
                ],
                'model' => 'gpt-4o-mini-realtime-preview-2024-12-17',
                'input_audio_format' => $inputFormat,
                'output_audio_format' => $outputFormat,
                'modalities' => ['audio','text'],
                'instructions' => $this->getInstructions(),
                'temperature' => 0.8,
                'tools' => $this->assistant->getRealtimeAssistantTools() ?? [],
                'tool_choice' => 'auto',
                'max_response_output_tokens' => 'inf',
                'turn_detection' => [
                   // 'type' => 'semantic_vad',
                   'type' => 'server_vad',
                   // 'eagerness' => 'low',
                   // 'threshold' => 0.5,
                   // 'prefix_padding_ms' => 300,
                    'silence_duration_ms' => 1000,
                    'create_response' => true
                ],
            ]
        ];
    }

   

    /**
     * Update the assistant configuration during runtime
     * 
     * @param Assistant $newAssistant The new assistant model to use
     * @param array $options Additional options for the update
     * @return void
     */
    public function updateAssistant(Assistant $newAssistant, array $options = [])
    {
        Log::info("[OPENAI RELAY] Updating assistant configuration", [
            'old_assistant' => $this->assistant->id,
            'new_assistant' => $newAssistant->id,
            'options' => $options
        ]);

        $this->assistant = $newAssistant;

        $instructions = $this->getInstructions();

        // Prepare the update message
        $updateMessage = [
            'type' => 'session.update',
            'session' => [
                'model' => $newAssistant->model,
                'instructions' => $instructions,
                'tools' => $this->assistant->getRealtimeAssistantTools() ?? [],
            ]
        ];

        // Add optional configurations if provided
        if (isset($options['tool_choice'])) {
            $updateMessage['session']['tool_choice'] = $options['tool_choice'];
        }

        // Send the update message to OpenAI
        if ($this->openaiConn) {
            $this->openaiConn->send(json_encode($updateMessage));
            Log::info("[OPENAI RELAY] Sent assistant update message", [
                'update_message' => $updateMessage
            ]);
        } else {
            Log::error("[OPENAI RELAY] Cannot update assistant - OpenAI connection not available");
        }
    }

    /**
     * Update turn detection settings during runtime
     * 
     * @param array $options Turn detection configuration options:
     *      - type: string (Required) - Type of turn detection ('server_vad' or 'semantic_vad')
     *      - create_response: boolean (Optional) - Whether to automatically generate a response when VAD stop occurs. Defaults to true
     *      - eagerness: string (Optional) - Used only for semantic_vad mode. Response eagerness ('low', 'auto', 'high'). Defaults to 'auto'
     *      - interrupt_response: boolean (Optional) - Whether to interrupt ongoing response on VAD start. Defaults to true
     *      - prefix_padding_ms: integer (Optional) - Server VAD only. Audio to include before VAD detected speech (ms). Defaults to 300ms
     *      - silence_duration_ms: integer (Optional) - Server VAD only. Silence duration to detect speech stop (ms). Defaults to 500ms
     *      - threshold: float (Optional) - Server VAD only. VAD activation threshold (0.0 to 1.0). Defaults to 0.5
     * 
     * Note: Passing an empty array will turn off turn detection completely.
     * 
     * @return void
     */
    public function updateTurnDetection(array $options = [])
    {
        Log::info("[OPENAI RELAY] Updating turn detection settings", [
            'options' => $options
        ]);

        // If options is empty or null, turn off turn detection
        if (empty($options)) {
            $updateMessage = [
                'type' => 'session.update',
                'session' => [
                    'turn_detection' => null
                ]
            ];
        } else {
            $turnDetection = [];

            // Required type parameter
            if (isset($options['type'])) {
                $turnDetection['type'] = $options['type'];
            }

            // Optional parameters
            if (isset($options['create_response'])) {
                $turnDetection['create_response'] = (bool)$options['create_response'];
            }

            if (isset($options['eagerness'])) {
                $turnDetection['eagerness'] = $options['eagerness'];
            }

            if (isset($options['interrupt_response'])) {
                $turnDetection['interrupt_response'] = (bool)$options['interrupt_response'];
            }

            // Server VAD specific parameters
            if ($options['type'] === 'server_vad') {
                if (isset($options['prefix_padding_ms'])) {
                    $turnDetection['prefix_padding_ms'] = (int)$options['prefix_padding_ms'];
                }

                if (isset($options['silence_duration_ms'])) {
                    $turnDetection['silence_duration_ms'] = (int)$options['silence_duration_ms'];
                }

                if (isset($options['threshold'])) {
                    $turnDetection['threshold'] = (float)$options['threshold'];
                }
            }

            $updateMessage = [
                'type' => 'session.update',
                'session' => [
                    'turn_detection' => $turnDetection
                ]
            ];
        }

        // Send the update message to OpenAI
        if ($this->openaiConn) {
            $this->openaiConn->send(json_encode($updateMessage));
            Log::info("[OPENAI RELAY] Sent turn detection update message", [
                'update_message' => $updateMessage
            ]);
        } else {
            Log::error("[OPENAI RELAY] Cannot update turn detection - OpenAI connection not available");
        }
    }

    private function getInstructions()
    {

        $instructions = '';
        if(!empty($this->conversation->system_message)) {
            $instructions = $this->conversation->system_message;
           
        }      
        

        //include path_state with instructions
        $instructions .= "\n\nPATH STATE:\n\n" . json_encode($this->conversation->path_state) . "\n\n:END PATH STATE;\n\n";

        //include conversation_history with instructions
        $instructions .= "\n\nCONVERSATION HISTORY:\n\n" . json_encode($this->conversation->getConversationMessages()) . "\n\n:END CONVERSATION HISTORY;\n\n";

        $instructions .= "\n\nINSTRUCTIONS:\n
        Use the PATH STATE and CONVERSATION HISTORY to for context if needed when following your instructions. 
        ASSISTANT INSTRUCTIONS ARE THE IMPORTANT ONES TO FOLLOW.
        \n:END INSTRUCTIONS;\n\n";

        $instructions .= "\n\nASSISTANT INSTRUCTIONS:\n\n" . $this->assistant->system_message . "\n\n:END ASSISTANT INSTRUCTIONS;\n\n";
     
        if($this->conversation->current_node_index) {


            Log::info("[BARE RELAY] Current node index", [
                'current_node_index' => $this->conversation->current_node_index
            ]);

            Log::info("[BARE RELAY] Conversation path", [
                'conversation_path' => $this->conversation
            ]);

            $conversation_path = ConversationPath::find($this->conversation->conversation_path_id);

            Log::info("[BARE RELAY] Conversation path", [
                'conversation_path' => $conversation_path
            ]);
            if(isset($conversation_path->nodes[$this->conversation->current_node_index]) && isset($conversation_path->nodes[$this->conversation->current_node_index]['content']['prompt'])){

                $node_prompt = $conversation_path->nodes[$this->conversation->current_node_index]['content']['prompt']; 
                $instructions .= "\n\nNODE PROMPT:\n\n" . $node_prompt . "\n\n:END NODE PROMPT;\n\n";
            }else{
                Log::info("[BARE RELAY] No node prompt found NO BIG DEAL", [
                 
                    'current_node_index' => $this->conversation->current_node_index
                ]);
            }
           
        }



        Log::info("[BARE RELAY] Instructions", [
            'instructions' => $instructions
        ]);

        return $instructions;
    }

    /**
     * Play a WAV file through the BareWebsocketServer
     * 
     * @param string $fileId The file ID of the WAV file
     * @param string $format Optional format override ('pcm16', 'g711_ulaw', 'g711_alaw'). If not specified, will try to detect from file.
     * @return bool Success status
     */
    public function playAudioFile(string $fileId, string $format = null)
    {
        try {

            $file = AudioFile::find($fileId);

            if(!$file) {
                Log::error("[BARE RELAY][PLAY AUDIO FILE] Audio file not found", [   
                    'file_id' => $fileId
                ]);
                return false;
            }

            $fullPath = storage_path('app/public/' . $file->file_path);

            if (!file_exists($fullPath)) {
                Log::error("[BARE RELAY][PLAY AUDIO FILE] Audio file not found", [   
                    'file_path' => $fullPath,
                    'relative_path' => $file->file_path
                ]);
                return false;
            }

            // Read the WAV file
            $audioData = file_get_contents($fullPath);
            
            if ($audioData === false) {
                Log::error("[BARE RELAY][PLAY AUDIO FILE] Failed to read audio file", [
                    'file_path' => $fullPath
                ]);
                return false;
            }

            // If format not specified, try to detect from file
            if (!$format) {
                // Check WAV header for format
                $format = $this->detectWavFormat($audioData);
                if (!$format) {
                    Log::error("[BARE RELAY][PLAY AUDIO FILE] Could not detect WAV format", [
                        'file_path' => $fullPath
                    ]);
                    return false;
                }
            }

            // Send the audio data to BareWebsocketServer
            $mediaMessage = [
                'type' => 'media',
                'event' => 'media',
                'streamSid' => false,
                'media' => [
                    'payload' => base64_encode($audioData),
                    'format' => $format
                ]
            ];

            $this->richbotConn->send(json_encode($mediaMessage));
            
            Log::info("[BARE RELAY][PLAY AUDIO FILE] Audio file sent successfully", [
                'file_path' => $fullPath,
                'format' => $format,
                'size' => strlen($audioData)
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error("[BARE RELAY] Error playing audio file", [
                'error' => $e->getMessage(),
                'file_path' => $fullPath ?? 'unknown',
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Detect WAV file format from header
     * 
     * @param string $wavData The WAV file data
     * @return string|null Detected format ('pcm16', 'g711_ulaw', 'g711_alaw') or null if unknown
     */
    private function detectWavFormat(string $wavData)
    {
        // Check for WAV header
        if (substr($wavData, 0, 4) !== 'RIFF' || substr($wavData, 8, 4) !== 'WAVE') {
            return null;
        }

        // Get format code from WAV header (offset 20)
        $formatCode = unpack('v', substr($wavData, 20, 2))[1];

        switch ($formatCode) {
            case 1: // PCM
                return 'pcm16';
            case 6: // A-law
                return 'g711_alaw';
            case 7: // μ-law
                return 'g711_ulaw';
            default:
                return null;
        }
    }

    /**
     * Set server-side VAD (Voice Activity Detection)
     * 
     * @param array $options Optional configuration:
     *      - create_response: boolean - Whether to automatically generate a response when VAD stop occurs (default: true)
     *      - interrupt_response: boolean - Whether to interrupt ongoing response on VAD start (default: true)
     *      - prefix_padding_ms: integer - Audio to include before VAD detected speech in ms (default: 300)
     *      - silence_duration_ms: integer - Silence duration to detect speech stop in ms (default: 500)
     *      - threshold: float - VAD activation threshold 0.0 to 1.0 (default: 0.5)
     * @return void
     */
    public function setServerVad($create_response = true, $interrupt_response = true, $prefix_padding_ms = 300, $silence_duration_ms = 500, $threshold = 0.5)
    {



        $options = [
            'create_response' => $create_response,
            'interrupt_response' => $interrupt_response,
            'prefix_padding_ms' => $prefix_padding_ms,
            'silence_duration_ms' => $silence_duration_ms,
            'threshold' => $threshold
        ];

        $updateMessage = [
            'type' => 'session.update',
            'turn_detection' => [
                'type' => 'server_vad',
                'create_response' => $create_response,
                'interrupt_response' => $interrupt_response,
                'prefix_padding_ms' => $prefix_padding_ms,
                'silence_duration_ms' => $silence_duration_ms,
                'threshold' => $threshold
            ]
        ];

        if ($this->openaiConn) {
            $this->openaiConn->send(json_encode($updateMessage));
            Log::info("[OPENAI RELAY] Set server VAD configuration", [
                'config' => $options
            ]);
        } else {
            Log::error("[OPENAI RELAY] Cannot set server VAD - OpenAI connection not available");
        }
    }

    /**
     * Set semantic VAD (Voice Activity Detection)
     * 
     * @param array $options Optional configuration:
     *      - create_response: boolean - Whether to automatically generate a response when VAD stop occurs (default: true)
     *      - interrupt_response: boolean - Whether to interrupt ongoing response on VAD start (default: true)
     *      - eagerness: string - Response eagerness ('low', 'auto', 'high') (default: 'auto')
     * @return void
     */
    public function setSemanticVad($create_response = true, $interrupt_response = true, $eagerness = 'auto')
    {
        $options = [
            'create_response' => $create_response,
            'interrupt_response' => $interrupt_response,
            'eagerness' => $eagerness
        ];
       
        $updateMessage = [
            'type' => 'session.update',
            'turn_detection' => [
                'type' => 'semantic_vad',
                'create_response' => $create_response,
                'interrupt_response' => $interrupt_response,
                'eagerness' => $eagerness
            ]
        ];

        if ($this->openaiConn) {
            $this->openaiConn->send(json_encode($updateMessage));
            Log::info("[OPENAI RELAY] Set semantic VAD configuration", [
                'config' => $options
            ]);
        } else {
            Log::error("[OPENAI RELAY] Cannot set semantic VAD - OpenAI connection not available");
        }
    }

    /**
     * Generate a speech started message to clear buffers and cancel responses
     * 
     * @return array Message to send to OpenAI
     */
    private function generateSpeechStartedMessage()
    {
        return [
            'type' => 'response.cancel'
        ];
    }

    /**
     * Generate a clear buffer message for the client
     * 
     * @param string $streamSid The stream ID
     * @return array Message to send to client
     */
    private function generateClearBufferMessage($streamSid)
    {
        return [
            'streamSid' => $streamSid,
            'event' => 'clear'
        ];
    }

    /**
     * Generate a complete speech interruption sequence
     * 
     * @param string $streamSid The stream ID
     * @return array Messages to send in sequence
     */
    private function generateSpeechInterruptionSequence($streamSid)
    {
        return [
            'client' => $this->generateClearBufferMessage($streamSid),
            'openai' => $this->generateSpeechStartedMessage()
        ];
    }

    /**
     * Generate a speech stopped message
     * 
     * @return array Message to send to OpenAI
     */
    private function generateSpeechStoppedMessage()
    {
        return [
            'type' => 'response.create'
        ];
    }

    /**
     * Clear the audio queue in both directions
     * This sends messages to stop any ongoing audio playback and cancel responses
     * 
     * @return void
     */
    private function clearAudioQueue()
    {
        try {
            Log::info("[BARE RELAY] Clearing audio queue in both directions", [
                'room' => $this->room,
                'timestamp' => date('Y-m-d H:i:s.u')
            ]);

            $interruptionSequence = $this->generateSpeechInterruptionSequence($this->room);

            // Clear client buffer
            $this->richbotConn->send(json_encode($interruptionSequence['client']));
            Log::info("[BARE RELAY] Cleared client buffer", [
                'room' => $this->room,
                'timestamp' => date('Y-m-d H:i:s.u')
            ]);

            // Cancel OpenAI response
            $this->openaiConn->send(json_encode($interruptionSequence['openai']));
            Log::info("[BARE RELAY] Cancelled OpenAI response", [
                'room' => $this->room,
                'timestamp' => date('Y-m-d H:i:s.u')
            ]);
            
        } catch (\Exception $e) {
            Log::error("[BARE RELAY] Failed to clear audio queue", [
                'error' => $e->getMessage(),
                'room' => $this->room,
                'timestamp' => date('Y-m-d H:i:s.u')
            ]);
        }
    }

    private function handleDtmf($digit) {
        $currentTime = time();
        
        // Initialize window if not started
        if ($this->dtmfWindowStart === null) {
            $this->dtmfWindowStart = $currentTime;
        }
        
        // Add digit to history
        $this->dtmfHistory[] = [
            'digit' => $digit,
            'timestamp' => $currentTime
        ];
        
        // Clean up old entries outside the window
        $this->cleanupDtmfHistory();
        
        // Check for patterns
        $this->checkDtmfPatterns();
        
        Log::info("[BARE RELAY] DTMF tracked", [
            'digit' => $digit,
            'history' => $this->dtmfHistory,
            'window_start' => $this->dtmfWindowStart,
            'room' => $this->room
        ]);
    }
    
    private function cleanupDtmfHistory() {
        $currentTime = time();
        $cutoffTime = $currentTime - $this->dtmfWindowDuration;
        
        // Remove entries older than the window
        $this->dtmfHistory = array_filter($this->dtmfHistory, function($entry) use ($cutoffTime) {
            return $entry['timestamp'] > $cutoffTime;
        });
        
        // Reset window start if history is empty
        if (empty($this->dtmfHistory)) {
            $this->dtmfWindowStart = null;
        }
    }
    
    private function checkDtmfPatterns() {
        // Convert history to string for pattern matching
        $dtmfString = implode('', array_column($this->dtmfHistory, 'digit'));
        
        foreach ($this->dtmfPatterns as $patternName => $patterns) {
            foreach ($patterns as $pattern) {
                if (strpos($dtmfString, $pattern) !== false) {
                    $this->handleDtmfPattern($patternName, $pattern);
                    // Clear history after pattern match
                    $this->dtmfHistory = [];
                    $this->dtmfWindowStart = null;
                    return;
                }
            }
        }
    }
    
    private function handleDtmfPattern($patternName, $pattern) {
        Log::info("[BARE RELAY] DTMF pattern detected", [
            'pattern_name' => $patternName,
            'pattern' => $pattern,
            'room' => $this->room
        ]);
        
        switch ($patternName) {
            case 'emergency':
                $this->handleEmergencyPattern();
                break;
            case 'help':
                $this->handleHelpPattern();
                break;
            case 'transfer':
                $this->handleTransferPattern();
                break;
            case 'restart':
                $this->handleRestartPattern();
                break;
            case 'load_assistant':
                $this->handleLoadAssistantPattern();
                break;
            case 'pause':
                $this->handlePausePattern();
                break;
            case 'resume':
                $this->handleResumePattern();
                break;
            case 'clear_history':
                $this->handleClearHistoryPattern();
                break;
        }
    }
    
    private function handleEmergencyPattern() {
        // Implement emergency handling logic
        // For example, transfer to emergency services or notify supervisor
        $this->sendEndCall();
        // Add additional emergency handling as needed
    }
    
    private function handleHelpPattern() {
        // Implement help handling logic
        // For example, transfer to human operator
        $this->sendEndCall();
        // Add additional help handling as needed
    }
    
    private function handleTransferPattern() {
        // Implement transfer handling logic
        // For example, transfer to another department
        $this->sendEndCall();
        // Add additional transfer handling as needed
    }

    private function handleRestartPattern() {
        Log::info("[BARE RELAY] Restarting conversation", [
            'room' => $this->room
        ]);

        // Clear conversation history
        if ($this->conversation) {
            $this->conversation->clearMessages();
        }

        // Reset session configuration
        $initialConfig = $this->getInitialSessionConfig();
        $this->openaiConn->send(json_encode($initialConfig));
        
        // Create new response
        $this->openaiConn->send(json_encode(['type' => 'response.create']));

        // Play confirmation tone
        $this->playAudioFile(12); // Assuming you have a confirmation tone file
    }

    private function handleLoadAssistantPattern() {
        Log::info("[BARE RELAY] Loading default assistant", [
            'room' => $this->room
        ]);

        // Load the default assistant (you can modify this to load specific assistants)
        $defaultAssistant = Assistant::where('is_default', true)->first();
        
        if ($defaultAssistant) {
            $this->updateAssistant($defaultAssistant);
            $this->playAudioFile(13); // Assuming you have a success tone file
        } else {
            Log::error("[BARE RELAY] Default assistant not found");
            $this->playAudioFile(14); // Assuming you have an error tone file
        }
    }

    private function handlePausePattern() {
        Log::info("[BARE RELAY] Pausing conversation", [
            'room' => $this->room
        ]);

      // Disable turn detection
        $this->updateTurnDetection([]);
        
        // Cancel any ongoing response
        $this->openaiConn->send(json_encode(['type' => 'response.cancel']));
        
        $this->playAudioFile(15); // Assuming you have a pause tone file
    }

    private function handleResumePattern() {
        Log::info("[BARE RELAY] Resuming conversation", [
            'room' => $this->room
        ]);

        // Re-enable turn detection
        $this->updateTurnDetection($this->defaultTurnDetectionSettings);
        
        // Create new response
        $this->openaiConn->send(json_encode(['type' => 'response.create']));
        
        $this->playAudioFile(16); // Assuming you have a resume tone file
    }

    private function handleClearHistoryPattern() {
        Log::info("[BARE RELAY] Clearing conversation history", [
            'room' => $this->room
        ]);

        if ($this->conversation) {
            $this->conversation->clearMessages();
        }

        // Reset session configuration
        $initialConfig = $this->getInitialSessionConfig();
        $this->openaiConn->send(json_encode($initialConfig));
        
        $this->playAudioFile(17); // Assuming you have a clear history tone file
    }
} 