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
use App\Services\Executors\SurveyExecutor;
use App\Models\User;
use App\Services\Executors\RainbowExecutor;
use App\Services\Executors\RainbowDashboardTicketExecutor;
use Illuminate\Support\Facades\Http;

class BareWebsocketMonitor extends Command
{
    protected $signature = 'bare:monitor {conversation_id} {--save-audio} {--transcribe} {--debug} {--audio-output-pcm16} {--audio-output-g711_alaw} {--user_id=} {--richbot_token=} {--second-delay=}';
    protected $description = 'Monitor audio streams from the WebSocket server';

    private $richbotConn;
    private $openaiConn;
    private $openaiConnected = false;
    private $room;
    private $callSid;
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
    private $saveAudio = false;
    private $transcribe = false;
    private $audioFiles = [];
    private $conversationId;
    private $textLogFile;
    private $activeCalls = [];
    private $callLogs = [];
    private $transcriptionConnections = [];
    private $conversationLogs = [];
    private $webClientLogs = [];
    private $sourceCounters = [
        'twilio' => 0,
        'openai' => 0,
        'webclient' => 0,
        'monitor' => 0,
        'transcription' => 0,
        'assistant' => 0,
        'client' => 0
    ];
    private $pendingAudioQueue = [];
    
    public function handle()
    {
        $this->startTime = time();
        $this->isDebug = $this->option('debug');
        $this->isDebug= true;
        
        // Get conversation ID from argument
        $this->conversationId = $this->argument('conversation_id');
        
        Log::info("[MONITOR] Starting monitor process", [
            'conversation_id' => $this->conversationId,
            'debug_mode' => $this->isDebug,
            'save_audio' => $this->saveAudio,
            'transcribe' => $this->transcribe,
            'working_directory' => getcwd(),
            'storage_path' => storage_path()
        ]);
        
        // Get conversation and its path state
        $conversation = \App\Models\Conversation::find($this->conversationId);
        if (!$conversation) {
            Log::error("[MONITOR] Conversation not found", [
                'conversation_id' => $this->conversationId
            ]);
            $this->error("Conversation not found: {$this->conversationId}");
            return 1;
        }

        // Get settings from path state if available
        $pathState = $conversation->path_state ?? [];
        $monitorSettings = $pathState['monitor_call'] ?? [];

        // Set settings from path state, falling back to command line options
        $this->saveAudio = $monitorSettings['recordAudio'] ?? $this->option('save-audio');
        $this->transcribe = $monitorSettings['transcribeAudio'] ?? $this->option('transcribe');
        $this->userId = $this->option('user_id') ?? null;
        $this->richbotToken = $this->option('richbot_token') ?? null;
        $this->secondDelay = $this->option('second-delay') ?? 0;
        
        // Initialize conversation directory
        $conversationDir = storage_path("app/bare_logs/{$this->conversationId}");
        if (!file_exists($conversationDir)) {
            mkdir($conversationDir, 0755, true);
            Log::info("[MONITOR] Created conversation directory", [
                'path' => $conversationDir
            ]);
        }

        // Initialize conversation metadata
        $this->conversationLogs = [
            'metadata' => "{$conversationDir}/metadata.json",
            'sources' => []
        ];

        Log::info("[MONITOR] Initializing conversation logs", [
            'conversation_dir' => $conversationDir,
            'metadata_file' => $this->conversationLogs['metadata'],
            'conversation_id' => $this->conversationId
        ]);

        // Initialize conversation metadata file
        file_put_contents(
            $this->conversationLogs['metadata'],
            json_encode([
                'conversation_id' => $this->conversationId,
                'room' => $this->room,
                'start_time' => time(),
                'user_id' => $this->userId,
                'save_audio' => $this->saveAudio,
                'transcribe' => $this->transcribe,
                'path_state' => $pathState
            ], JSON_PRETTY_PRINT)
        );

        // Initialize text log file
        $this->textLogFile = storage_path("app/bare_logs/text/{$this->conversationId}.log");
        if (!file_exists(dirname($this->textLogFile))) {
            mkdir(dirname($this->textLogFile), 0755, true);
            Log::info("[MONITOR] Created text log directory", [
                'path' => dirname($this->textLogFile)
            ]);
        }

        // Initialize web client log directory
        $webClientLogDir = storage_path("app/bare_logs/webclient/{$this->conversationId}");
        if (!file_exists($webClientLogDir)) {
            mkdir($webClientLogDir, 0755, true);
            Log::info("[MONITOR] Created web client log directory", [
                'path' => $webClientLogDir
            ]);
        }
        $this->webClientLogs = [
            'text_log' => "{$webClientLogDir}/text.log",
            'audio_log' => "{$webClientLogDir}/audio.raw"
        ];

        if($this->secondDelay) {
            sleep($this->secondDelay);
        }

        $this->outputFormat = $this->option('audio-output-pcm16') ? 'pcm16' : 'g711_ulaw';
        $this->outputFormat = $this->option('audio-output-g711_alaw') ? 'g711_alaw' : 'g711_ulaw';

        $loop = Factory::create();
        $connector = new Connector($loop, new ReactConnector($loop, [
            'tls' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]));

        // Connect to Bare WebSocket Server
        $bareUrl = "wss://richbot9000.local:9502/monitor/{$this->conversationId}";
        Log::info("[MONITOR] Connecting to Bare WebSocket", [
            'url' => $bareUrl,
            'conversation_id' => $this->conversationId
        ]);

        $connector($bareUrl)
            ->then(function($bareConn) use ($loop, $connector) {
                Log::info("[MONITOR] Connected to Bare WebSocket", [
                    'conversation_id' => $this->conversationId,
                    'connection_time' => date('Y-m-d H:i:s.u')
                ]);

                $this->richbotConn = $bareConn;
                
                // Join the specified room
                $this->richbotConn->send(json_encode([
                    'type' => 'join',
                    'conversation_id' => $this->conversationId
                ]));
                      
                // Connect to OpenAI for transcription if enabled
                if ($this->transcribe) {
                    $this->info('Creating OpenAI transcription session...');
                    Log::info("[MONITOR] Initializing OpenAI transcription", [
                        'conversation_id' => $this->conversationId,
                        'output_format' => $this->outputFormat
                    ]);
                    
                    $response = Http::withHeaders([
                        'Authorization' => 'Bearer ' . config('services.openai.api_key'),
                        'Content-Type' => 'application/json',
                        'OpenAI-Beta' => 'realtime=v1'
                    ])->post('https://api.openai.com/v1/realtime/transcription_sessions', [
                        'input_audio_format' => $this->outputFormat,
                        'input_audio_transcription' => [
                            'model' => 'gpt-4o-transcribe',
                            'language' => 'en',
                            'prompt' => ''
                    ]]);

                    if (!$response->successful()) {
                        Log::error("[MONITOR] Failed to create transcription session", [
                            'status' => $response->status(),
                            'response' => $response->json(),
                            'conversation_id' => $this->conversationId
                        ]);
                        return;
                    }

                    $sessionData = $response->json();
                    $clientSecret = $sessionData['client_secret']['value'] ?? null;
                    if (!$clientSecret) {
                        Log::error("[MONITOR] No client secret found in response", [
                            'response' => $sessionData,
                            'conversation_id' => $this->conversationId
                        ]);
                        return;
                    }

                    Log::info("[MONITOR] OpenAI transcription session created", [
                        'session_id' => $sessionData['id'],
                        'conversation_id' => $this->conversationId
                    ]);

                    // Connect to WebSocket using client secret as bearer token
                    $openaiUrl = "wss://api.openai.com/v1/realtime?intent=transcription&session_id=" . $sessionData['id'];
                    $openaiHeaders = [
                        'Authorization' => 'Bearer ' . $clientSecret,
                        'Content-Type' => 'application/json',
                        'OpenAI-Beta' => 'realtime=v1'
                    ];

                    Log::info("[MONITOR] Connecting to OpenAI WebSocket", [
                        'url' => $openaiUrl,
                        'session_id' => $sessionData['id'],
                        'conversation_id' => $this->conversationId
                    ]);

                    try {
                        $connector($openaiUrl, [], $openaiHeaders)
                            ->then(function($openaiConn) use ($loop) {
                                Log::info("[MONITOR] Connected to OpenAI WebSocket", [
                                    'conversation_id' => $this->conversationId,
                                    'connection_time' => date('Y-m-d H:i:s.u')
                                ]);

                                $this->openaiConn = $openaiConn;
                                $this->openaiConnected = true;
                                $this->lastMessageTime = time();

                                // Send initial configuration
                                $configMessage = [
                                    'type' => 'transcription_session.update',
                                    'session' => [
                                        'input_audio_format' => $this->outputFormat,
                                        'input_audio_transcription' => [
                                            'model' => 'gpt-4o-transcribe',
                                            'language' => 'en',
                                            'prompt' => ''
                                        ],
                                        'turn_detection' => [
                                            'type' => 'server_vad',
                                            'threshold' => 0.5,
                                            'prefix_padding_ms' => 300,
                                            'silence_duration_ms' => 200
                                        ]
                                    ]
                                ];

                                Log::info("[MONITOR] Sending OpenAI configuration", [
                                    'config' => $configMessage,
                                    'conversation_id' => $this->conversationId
                                ]);

                                $this->openaiConn->send(json_encode($configMessage));

                                // Process any pending audio messages
                                while (!empty($this->pendingAudioQueue)) {
                                    $audioMessage = array_shift($this->pendingAudioQueue);
                                    $this->openaiConn->send(json_encode($audioMessage));
                                }

                                // Handle messages from OpenAI
                                $this->openaiConn->on('message', function($msg) {
                                    $this->handleOpenAIMessage($msg);
                                });

                                // Handle connection closures
                                $this->openaiConn->on('close', function() use ($loop) {
                                    Log::error("[MONITOR] OpenAI connection closed", [
                                        'conversation_id' => $this->conversationId,
                                        'close_time' => date('Y-m-d H:i:s.u')
                                    ]);
                                    $this->openaiConnected = false;
                                    $loop->stop();
                                });

                            }, function($e) use ($loop) {
                                Log::error("[MONITOR] Could not connect to OpenAI", [
                                    'error' => $e->getMessage(),
                                    'conversation_id' => $this->conversationId,
                                    'error_time' => date('Y-m-d H:i:s.u')
                                ]);
                                $this->openaiConnected = false;
                                $loop->stop();
                            });
                    } catch (\Exception $e) {
                        Log::error("[MONITOR] Exception while connecting to OpenAI", [
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                            'conversation_id' => $this->conversationId,
                            'error_time' => date('Y-m-d H:i:s.u')
                        ]);
                        $this->openaiConnected = false;
                        $loop->stop();
                    }
                }

                // Handle messages from Bare WebSocket Server
                $this->richbotConn->on('message', function($msg) {
                    $this->handleBareMessage($msg);
                });

                // Set up heartbeat
                $loop->addPeriodicTimer(30, function() {
                    $this->sendHeartbeat();
                });

                // Handle connection closures
                $this->richbotConn->on('close', function() use ($loop) {
                    Log::error("[MONITOR] Bare WebSocket connection closed", [
                        'conversation_id' => $this->conversationId,
                        'close_time' => date('Y-m-d H:i:s.u')
                    ]);
                    $loop->stop();
                });

            }, function($e) use ($loop) {
                Log::error("[MONITOR] Could not connect to Bare WebSocket", [
                    'error' => $e->getMessage(),
                    'conversation_id' => $this->conversationId,
                    'error_time' => date('Y-m-d H:i:s.u')
                ]);
                $loop->stop();
            });

        $loop->run();
    }

    private function handleBareMessage($msg)
    {
        try {
            $message = json_decode($msg, true);
            $this->bareMessageCount++;
            $this->lastMessageTime = time();

            // Extract call ID if present, fallback to conversation ID if not
            $callId = $message['call_id'] ?? $message['callSid'] ?? $this->conversationId;
            
            // If we don't have an active call for this ID, initialize it
            if (!isset($this->activeCalls[$callId])) {
                $this->activeCalls[$callId] = [
                    'start_time' => time(),
                    'phone_number' => 'unknown',
                    'status' => 'active',
                    'last_activity' => time()
                ];
                
                // Initialize source log for this call
                $sourceKey = $this->initializeSourceLog('monitor');
            }

            // Handle call lifecycle events
            if (isset($message['type'])) {
                switch ($message['type']) {
                    case 'call.started':
                        $this->handleCallStarted($message);
                        break;
                    case 'call.ended':
                        $this->handleCallEnded($message);
                        break;
                }
            }

            // If we have a call ID, ensure we have a log file for it
            if ($callId && !isset($this->callLogs[$callId])) {
                $this->initializeCallLog($callId);
            }

            // Handle Twilio media messages
            if (isset($message['event']) && $message['event'] === 'media' && isset($message['media']['payload'])) {
                $this->handleAudioData($message['media']['payload'], 'twilio_media', $callId);
                return;
            }

            // Handle other message types
            if (isset($message['type'])) {
                switch ($message['type']) {
                    case 'media_data':
                        if (isset($message['data'])) {
                            $this->handleAudioData($message['data'], 'media_data', $callId);
                        }
                        break;

                    case 'input_audio_buffer.append':
                        if (isset($message['audio'])) {
                            $this->handleAudioData($message['audio'], 'input_audio', $callId);
                        }
                        break;

                    case 'response.audio.delta':
                        if (isset($message['delta'])) {
                            $this->handleAudioData($message['delta'], 'openai_audio', $callId);
                        }
                        break;

                    case 'text':
                    case 'message':
                        if (isset($message['text']) || isset($message['content'])) {
                            $this->handleTextMessage($message, 'client', $callId);
                        }
                        break;

                    case 'response.text':
                    case 'response.message':
                        if (isset($message['text']) || isset($message['content'])) {
                            $this->handleTextMessage($message, 'assistant', $callId);
                        }
                        break;

                    case 'transcript.delta':
                        if (isset($message['delta'])) {
                            $this->handleTextMessage(['text' => $message['delta']], 'transcription', $callId);
                        }
                        break;

                    case 'transcript.complete':
                        if (isset($message['transcript'])) {
                            $this->handleTextMessage(['text' => $message['transcript']], 'transcription_complete', $callId);
                        }
                        break;
                }
            }
        } catch (\Exception $e) {
            Log::error("[MONITOR] Error processing Bare message", [
                'error' => $e->getMessage(),
                'conversation_id' => $this->conversationId
            ]);
        }
    }

    private function handleCallStarted($message)
    {
        $callId = $message['call_id'] ?? $message['callSid'] ?? null;
        if (!$callId) {
            Log::error("[MONITOR] Call started without call ID");
            return;
        }

        $this->activeCalls[$callId] = [
            'start_time' => time(),
            'phone_number' => $message['phone_number'] ?? 'unknown',
            'status' => 'active',
            'last_activity' => time()
        ];

        // Initialize source log for this Twilio call
        $sourceKey = $this->initializeSourceLog('twilio');
        
        // Update call metadata
        file_put_contents(
            $this->conversationLogs['sources'][$sourceKey]['metadata'],
            json_encode([
                'call_id' => $callId,
                'phone_number' => $this->activeCalls[$callId]['phone_number'],
                'start_time' => $this->activeCalls[$callId]['start_time'],
                'status' => 'active'
            ], JSON_PRETTY_PRINT)
        );

        Log::info("[MONITOR] Call started", [
            'call_id' => $callId,
            'phone_number' => $this->activeCalls[$callId]['phone_number']
        ]);

        $this->info("📞 New call started: {$callId} from {$this->activeCalls[$callId]['phone_number']}");
    }

    private function handleCallEnded($message)
    {
        $callId = $message['call_id'] ?? $message['callSid'] ?? null;
        if (!$callId || !isset($this->activeCalls[$callId])) {
            return;
        }

        $duration = time() - $this->activeCalls[$callId]['start_time'];
        $this->activeCalls[$callId]['status'] = 'ended';
        $this->activeCalls[$callId]['end_time'] = time();
        $this->activeCalls[$callId]['duration'] = $duration;

        // Find the source key for this call
        $sourceKey = null;
        foreach ($this->conversationLogs['sources'] as $key => $source) {
            $metadata = json_decode(file_get_contents($source['metadata']), true);
            if (isset($metadata['call_id']) && $metadata['call_id'] === $callId) {
                $sourceKey = $key;
                break;
            }
        }

        if ($sourceKey) {
            // Update call metadata
            file_put_contents(
                $this->conversationLogs['sources'][$sourceKey]['metadata'],
                json_encode([
                    'call_id' => $callId,
                    'phone_number' => $this->activeCalls[$callId]['phone_number'],
                    'start_time' => $this->activeCalls[$callId]['start_time'],
                    'end_time' => $this->activeCalls[$callId]['end_time'],
                    'duration' => $duration,
                    'status' => 'ended'
                ], JSON_PRETTY_PRINT)
            );
        }

        Log::info("[MONITOR] Call ended", [
            'call_id' => $callId,
            'duration' => $duration
        ]);

        $this->info("📞 Call ended: {$callId} (Duration: {$duration}s)");
    }



    public function handleTextDeltaMessage($message, $source, $callId = null)
    {

        $timestamp = date('Y-m-d H:i:s.u');
        $text = $message['delta'] ?? $message['content'] ?? '';

        Log::info("[MONITOR] Text delta message", [
            'source' => $source,
            'text' => $text,
            'call_id' => $callId,
            'timestamp' => $timestamp
        ]);
        
        
    }

    private function handleTextMessage($message, $source, $callId = null)
    {
        $timestamp = date('Y-m-d H:i:s.u');
        $text = $message['text'] ?? $message['content'] ?? '';
        
        // Determine source type and get next index
        $sourceType = 'twilio';
        if ($source === 'webclient') {
            $sourceType = 'webclient';
        } else if ($source === 'openai' || $source === 'assistant') {
            $sourceType = 'openai';
        }
        
        $sourceKey = $this->initializeSourceLog($sourceType);
        
        // Create log entry with metadata
        $logEntry = json_encode([
            'timestamp' => $timestamp,
            'source' => $sourceKey,
            'text' => $text,
            'call_id' => $callId,
            'user_id' => $this->userId
        ]) . "\n";
        
        // Append to unified text log
        file_put_contents(
            $this->callLogs[$callId]['text_log'],
            $logEntry,
            FILE_APPEND
        );
        
        // Log to Laravel log
        Log::info("[MONITOR] Text message", [
            'source' => $sourceKey,
            'text' => $text,
            'call_id' => $callId
        ]);
    }

    private function handleAudioData($audioData, $source, $callId = null)
    {
        // Save audio if enabled
        if ($this->saveAudio) {
            $timestamp = time();
            $sourceType = 'twilio';
            if ($source === 'webclient') {
                $sourceType = 'webclient';
            } else if ($source === 'openai') {
                $sourceType = 'openai';
            }
            
            $sourceKey = $this->initializeSourceLog($sourceType);
            $conversationDir = storage_path("app/bare_logs/{$this->conversationId}");
            
            // Create audio segment filename with timestamp
            $segmentId = uniqid();
            $audioFile = "{$conversationDir}/audio_{$sourceKey}_{$segmentId}.raw";
            
            // Save audio data
            file_put_contents($audioFile, $audioData);
            
            // Get or create audio index file
            $audioIndexFile = $callId ? 
                ($this->callLogs[$callId]['audio_index'] ?? null) : 
                "{$conversationDir}/audio_index.json";
            
            // Initialize audio index if it doesn't exist
            if (!file_exists($audioIndexFile)) {
                file_put_contents($audioIndexFile, json_encode([
                    'conversation_id' => $this->conversationId,
                    'segments' => []
                ], JSON_PRETTY_PRINT));
            }
            
            // Update audio index
            $audioIndex = json_decode(file_get_contents($audioIndexFile), true);
            $audioIndex['segments'][] = [
                'id' => $segmentId,
                'timestamp' => $timestamp,
                'source' => $sourceKey,
                'file' => basename($audioFile),
                'call_id' => $callId,
                'format' => $this->outputFormat,
                'size' => strlen($audioData)
            ];
            
            file_put_contents($audioIndexFile, json_encode($audioIndex, JSON_PRETTY_PRINT));
        }

        // Forward to OpenAI for transcription if enabled
        if ($this->transcribe) {
            // Check if audio data exceeds 15 MiB limit
            $maxSize = 15 * 1024 * 1024; // 15 MiB in bytes
            if (strlen($audioData) > $maxSize) {
                Log::warning("[MONITOR] Audio data exceeds 15 MiB limit", [
                    'size' => strlen($audioData),
                    'max_size' => $maxSize,
                    'source' => $source,
                    'call_id' => $callId,
                    'timestamp' => date('Y-m-d H:i:s.u'),
                    'conversation_id' => $this->conversationId
                ]);
                return;
            }

            $message = [
                'type' => 'input_audio_buffer.append',
                'event_id' => uniqid('event_'),
                'audio' => $this->isBase64Encoded($audioData) ? $audioData : base64_encode($audioData)
            ];

            Log::info("[MONITOR] Sending audio to OpenAI", [
                'source' => $source,
                'message' => $message,
                'timestamp' => date('Y-m-d H:i:s.u'),
                'conversation_id' => $this->conversationId
            ]);

            // Log the message being sent to OpenAI
            Log::info("[MONITOR] Sending audio to OpenAI", [
                'event_id' => $message['event_id'],            
                'source' => $source,
                'call_id' => $callId,
                'audio_size' => strlen($audioData),
                'timestamp' => date('Y-m-d H:i:s.u'),
                'conversation_id' => $this->conversationId
            ]);
            
            // Send to OpenAI for transcription
            if ($this->openaiConnected && $this->openaiConn) {
                $this->openaiConn->send(json_encode($message));
            } else {
                $this->pendingAudioQueue[] = $message;
                Log::info("[MONITOR] Queued audio for OpenAI", [
                    'event_id' => $message['event_id'],
                    'queue_size' => count($this->pendingAudioQueue),
                    'timestamp' => date('Y-m-d H:i:s.u'),
                    'conversation_id' => $this->conversationId
                ]);
            }
        }
    }

    private function handleOpenAIMessage($msg)
    {
        try {
            $message = json_decode($msg->getPayload(), true);
            $this->openaiMessageCount++;
            $this->lastMessageTime = time();

            // Log every message from OpenAI with full details
            Log::info("[MONITOR] OpenAI message received", [
                'type' => $message['type'] ?? 'unknown',
                'timestamp' => date('Y-m-d H:i:s.u'),
                'message_count' => $this->openaiMessageCount,
                'conversation_id' => $this->conversationId,
                'full_message' => $message,
                'raw_payload' => $msg->getPayload()
            ]);

            switch ($message['type']) {
                case 'transcription_session.created':
                    Log::info("[MONITOR] OpenAI transcription session created", [
                        'session_details' => $message,
                        'timestamp' => date('Y-m-d H:i:s.u'),
                        'conversation_id' => $this->conversationId
                    ]);
                    break;

                case 'transcription_session.updated':
                    Log::info("[MONITOR] Transcription session updated", [
                        'update_details' => $message,
                        'timestamp' => date('Y-m-d H:i:s.u'),
                        'conversation_id' => $this->conversationId
                    ]);
                    break;

                case 'transcription_session.status':
                    Log::info("[MONITOR] Transcription session status", [
                        'status' => $message['status'] ?? 'unknown',
                        'full_status' => $message,
                        'timestamp' => date('Y-m-d H:i:s.u'),
                        'conversation_id' => $this->conversationId
                    ]);
                    break;

                case 'transcript.delta':
                case 'conversation.item.input_audio_transcription.delta':
                    if (isset($message['delta'])) {
                        Log::info("[MONITOR] Transcription $$$$$$$$$$$$$$$$$$$ DELTA", [
                            'delta' => $message['delta'],
                            'full_message' => $message,
                            'timestamp' => date('Y-m-d H:i:s.u'),
                            'conversation_id' => $this->conversationId
                        ]);
                        $this->handleTextDeltaMessage(['text' => $message['delta']], 'transcription');
                    }
                    break;

                case 'transcript.complete':
                    if (isset($message['transcript'])) {
                        Log::info("[MONITOR] Transcription $$$$$$$$$$$$$$$$ complete", [
                            'transcript' => $message['transcript'],
                            'full_message' => $message,
                            'timestamp' => date('Y-m-d H:i:s.u'),
                            'conversation_id' => $this->conversationId
                        ]);

                       // $this->conversation->addMessage($message['transcript'], 'transcription_complete');

                        $this->handleTextMessage(['text' => $message['transcript']], 'transcription_complete');
                    }
                    break;

                case 'error':
                    Log::error("[MONITOR] OpenAI transcription error", [
                        'error' => $message['error'] ?? 'Unknown error',
                        'full_message' => $message,
                        'timestamp' => date('Y-m-d H:i:s.u'),
                        'conversation_id' => $this->conversationId
                    ]);
                    break;

                default:
                    Log::info("[MONITOR] Unhandled OpenAI message type", [
                        'type' => $message['type'] ?? 'unknown',
                        'full_message' => $message,
                        'timestamp' => date('Y-m-d H:i:s.u'),
                        'conversation_id' => $this->conversationId
                    ]);
                    break;
            }
        } catch (\Exception $e) {
            Log::error("[MONITOR] Error processing OpenAI message", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'message' => $msg->getPayload() ?? 'null',
                'timestamp' => date('Y-m-d H:i:s.u'),
                'conversation_id' => $this->conversationId
            ]);
        }
    }

    private function sendHeartbeat()
    {
        try {
            $this->lastHeartbeat = time();
            
            $this->richbotConn->send(json_encode([
                'type' => 'ping',
                'time' => $this->lastHeartbeat
            ]));
        } catch (\Exception $e) {
            Log::error("[MONITOR] Heartbeat error", [
                'error' => $e->getMessage()
            ]);
        }
    }

    private function getNextSourceIndex($sourceType)
    {
        $this->sourceCounters[$sourceType]++;
        return $this->sourceCounters[$sourceType];
    }

    private function initializeSourceLog($sourceType, $index = null)
    {
        if ($index === null) {
            $index = $this->getNextSourceIndex($sourceType);
        }
        
        $sourceKey = "{$sourceType}{$index}";
        
        if (!isset($this->conversationLogs['sources'][$sourceKey])) {
            $conversationDir = storage_path("app/bare_logs/{$this->conversationId}");
            
            $this->conversationLogs['sources'][$sourceKey] = [
                'metadata' => "{$conversationDir}/{$sourceKey}_metadata.json"
            ];

            // Initialize source metadata
            file_put_contents(
                $this->conversationLogs['sources'][$sourceKey]['metadata'],
                json_encode([
                    'source_type' => $sourceType,
                    'source_index' => $index,
                    'start_time' => time(),
                    'conversation_id' => $this->conversationId,
                    'room' => $this->room,
                    'user_id' => $this->userId
                ], JSON_PRETTY_PRINT)
            );
        }
        
        return $sourceKey;
    }

    /**
     * Generate transcription session configuration
     * 
     * Available options (not all implemented, see documentation for full list):
     * - input_audio_format: string - Format of input audio (pcm16, g711_ulaw, g711_alaw)
     * - input_audio_transcription: array
     *     - model: string - Model to use (gpt-4o-transcribe, gpt-4o-mini-transcribe, whisper-1)
     *     - language: string - ISO-639-1 language code (e.g. 'en')
     *     - prompt: string - Optional text to guide transcription style
     * - turn_detection: array
     *     - type: string - Type of turn detection ('server_vad')
     *     - threshold: float - VAD activation threshold (0.0 to 1.0)
     *     - prefix_padding_ms: int - Audio to include before VAD detection
     *     - silence_duration_ms: int - Silence duration to detect speech stop
     * - input_audio_noise_reduction: array
     *     - type: string - Type of noise reduction ('near_field', 'far_field')
     */
    private function getTranscriptionSessionConfig(string $model = 'whisper-1', string $inputFormat = 'g711_ulaw', string $prompt = '')
    {
        return [
            'type' => 'transcription_session.update',
            'session' => [
                'input_audio_format' => $inputFormat ?? $this->outputFormat,
                'input_audio_transcription' => [
                    'model' => $model,
                    'prompt' => $prompt,
                    'language' => 'en'
                ],
                'turn_detection' => [
                    'type' => 'server_vad',
                    'threshold' => 0.5,
                    'prefix_padding_ms' => 300,
                    'silence_duration_ms' => 500
                ],
                'input_audio_noise_reduction' => [
                    'type' => 'near_field'
                ]
            ]
        ];
    }

    private function initializeCallLog($callId)
    {
        if (!isset($this->callLogs[$callId])) {
            $conversationDir = storage_path("app/bare_logs/{$this->conversationId}");
            $callDir = "{$conversationDir}/calls/{$callId}";
            
            // Create call directory if it doesn't exist
            if (!file_exists($callDir)) {
                mkdir($callDir, 0755, true);
            }
            
            $this->callLogs[$callId] = [
                'text_log' => "{$callDir}/text.log",
                'audio_index' => "{$callDir}/audio_index.json"
            ];
            
            // Initialize audio index file
            file_put_contents(
                $this->callLogs[$callId]['audio_index'],
                json_encode([
                    'call_id' => $callId,
                    'conversation_id' => $this->conversationId,
                    'segments' => []
                ], JSON_PRETTY_PRINT)
            );
            
            Log::info("[MONITOR] Initialized call log", [
                'call_id' => $callId,
                'conversation_id' => $this->conversationId
            ]);
        }
        
        return $this->callLogs[$callId];
    }

    /**
     * Check if a string is base64 encoded
     */
    private function isBase64Encoded($data) {
        // Check if the string contains only base64 characters
        if (!preg_match('/^[a-zA-Z0-9\/\r\n+]*={0,2}$/', $data)) {
            return false;
        }
        
        // Try to decode and re-encode to verify
        $decoded = base64_decode($data, true);
        if ($decoded === false) {
            return false;
        }
        
        // Check if re-encoding matches original
        return base64_encode($decoded) === $data;
    }
} 