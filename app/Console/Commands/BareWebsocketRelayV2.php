<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use React\EventLoop\Factory;
use Ratchet\Client\Connector;
use React\Socket\Connector as ReactConnector;
use Illuminate\Support\Facades\Log;
use App\Models\Assistant;
use App\Models\Conversation;
use App\Models\User;

class BareWebsocketRelayV2 extends Command
{
    protected $signature = 'bare:assistant-v2 {room} {assistant_id} {--conversation_id=} {--debug} {--user_id=} {--richbot_token=} {--second-delay=}';
    protected $description = 'Start a modular WebSocket relay (V2) between OpenAI and the bare WebSocket server';

    private $room;
    private $assistant;
    private $conversation;
    private $user;
    private $isDebug = false;
    private $secondDelay = 0;
    private $richbotToken;
    private $userId;
    private $openaiConn;
    private $bareConn;
    private $messageCount = 0;
    private $openaiMessageCount = 0;
    private $bareMessageCount = 0;
    private $lastMessageTime;

    public function handle()
    {
        $this->initializeParameters();
        $this->validateAndLoadData();
        $this->displayStartupInfo();

        $loop = Factory::create();
        
        // Connect to Bare WebSocket Server
        $bareUrl = "wss://".config('app.domain').":".config('app.ws_port_alt')."/openai/{$this->room}/{$this->assistant->id}";
        $this->connectToBareServer($loop, $bareUrl);
        
        // Connect to OpenAI
        $this->connectToOpenAI($loop);
        
        $loop->run();
    }

    private function initializeParameters()
    {
        $this->room = $this->argument('room');
        $assistantId = $this->argument('assistant_id');
        $this->conversation_id = $this->option('conversation_id');
        $this->isDebug = $this->option('debug');
        $this->userId = $this->option('user_id');
        $this->richbotToken = $this->option('richbot_token');
        $this->secondDelay = $this->option('second-delay') ?? 0;

        if ($this->secondDelay > 0) {
            $this->info("Second Delay: {$this->secondDelay}");
            sleep($this->secondDelay);
        }
    }

    private function validateAndLoadData()
    {
        $assistantId = $this->argument('assistant_id');
        $this->assistant = Assistant::find($assistantId);

        if (!$this->assistant) {
            $this->error("Assistant not found: {$assistantId}");
            Log::error("[OPENAI RELAY V2] Assistant not found", ['assistant_id' => $assistantId]);
            return;
        }

        if ($this->conversation_id) {
            $this->conversation = Conversation::find($this->conversation_id);
        } else {
            $this->conversation = Conversation::create([
                'room' => $this->room,
                'assistant_id' => $assistantId,
                'assistant_type' => 'assistant',
            ]);
        }

        if ($this->userId) {
            $this->user = User::find($this->userId);
        }
    }

    private function displayStartupInfo()
    {
        $this->info("Starting Bare WebSocket Relay V2");
        $this->info("===========================");
        $this->info("Room: {$this->room}");
        $this->info("Assistant ID: {$this->assistant->id}");
        $this->info("Assistant: {$this->assistant->name}");
        $this->info("Model: {$this->assistant->model}");
        $this->info("Debug Mode: " . ($this->isDebug ? 'ON' : 'OFF'));

        if ($this->user) {
            $this->info("User: {$this->user->name}");
        }

        if ($this->richbotToken) {
            $this->info("Richbot Token: {$this->richbotToken}");
        }

        Log::info("[OPENAI RELAY V2] Starting connection", [
            'room' => $this->room,
            'assistant_id' => $this->assistant->id,
            'assistant_name' => $this->assistant->name,
            'debug_mode' => $this->isDebug,
            'user_id' => $this->userId,
            'has_richbot_token' => !empty($this->richbotToken)
        ]);
    }

    private function registerHandlers(ConversationOrchestrator $orchestrator)
    {
        $orchestrator->registerHandler('join', function ($data) {
            $this->info("Joined room: " . ($data['room'] ?? 'unknown'));
            Log::info("[OPENAI RELAY V2] Joined room", ['room' => $data['room'] ?? 'unknown']);
        });

        $orchestrator->registerHandler('message', function ($data) {
            $this->info("Received message: " . json_encode($data));
            Log::info("[OPENAI RELAY V2] Received message", ['data' => $data]);
        });

        $orchestrator->registerHandler('error', function ($data) {
            $this->error("Error: " . json_encode($data));
            Log::error("[OPENAI RELAY V2] Error", ['data' => $data]);
        });

        // Add more handlers as needed for specific message types
        $orchestrator->registerHandler('heartbeat', function ($data) {
            if ($this->isDebug) {
                $this->info("Heartbeat received");
            }
        });
    }

    private function connectToBareServer($loop, $bareUrl)
    {
        $connector = new Connector($loop, new ReactConnector($loop, [
            'tls' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]));

        $connector($bareUrl)->then(function($conn) use ($loop) {
            Log::info("[OPENAI RELAY V2] Connected to Bare WebSocket", [
                'room' => $this->room,
                'assistant' => $this->assistant->id
            ]);

            $this->bareConn = $conn;
            
            // Join the specified room
            $this->bareConn->send(json_encode([
                'type' => 'join',
                'room' => $this->room
            ]));
            
            // Handle messages from Bare WebSocket Server
            $this->bareConn->on('message', function($msg) {
                $this->handleBareMessage($msg);
            });

            // Handle connection closure
            $this->bareConn->on('close', function() use ($loop) {
                Log::error("[OPENAI RELAY V2] Bare WebSocket connection closed");
                $loop->stop();
            });

        }, function($e) use ($loop) {
            Log::error("[OPENAI RELAY V2] Could not connect to Bare WebSocket", [
                'error' => $e->getMessage()
            ]);
            $loop->stop();
        });
    }

    private function connectToOpenAI($loop)
    {
        $connector = new Connector($loop, new ReactConnector($loop, [
            'tls' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]));

        $openaiUrl = "wss://api.openai.com/v1/realtime?model=gpt-4o-mini-realtime-preview";
        $openaiHeaders = [
            'Authorization' => 'Bearer ' . config('services.openai.api_key'),
            'OpenAI-Beta' => 'realtime=v1',
            'Content-Type' => 'application/json'
        ];

        $connector($openaiUrl, [], $openaiHeaders)->then(function($conn) use ($loop) {
            Log::info("[OPENAI RELAY V2] Connected to OpenAI", [
                'room' => $this->room
            ]);

            $this->openaiConn = $conn;
            $this->lastMessageTime = time();
            
            // Configure OpenAI session
            $this->configureOpenAISession();
            
            // Add inactivity checker timer
            $loop->addPeriodicTimer(2, function() {
                $timeSinceLastMessage = time() - $this->lastMessageTime;
                if ($timeSinceLastMessage >= 60) {
                    try {
                        $this->openaiConn->send(json_encode(['type' => 'response.create']));
                        Log::debug("[OPENAI RELAY V2] Sent response.create due to inactivity", [
                            'seconds_inactive' => $timeSinceLastMessage
                        ]);
                    } catch (\Exception $e) {
                        Log::error("[OPENAI RELAY V2] Failed to send inactivity response.create", [
                            'error' => $e->getMessage()
                        ]);
                    }
                    // Reset timer after sending
                    $this->lastMessageTime = time();
                }
            });
            
            // Handle messages from OpenAI
            $this->openaiConn->on('message', function($msg) {
                $this->handleOpenAIMessage($msg);
            });

            // Handle connection closure
            $this->openaiConn->on('close', function() use ($loop) {
                Log::error("[OPENAI RELAY V2] OpenAI connection closed");
                $loop->stop();
            });

        }, function($e) use ($loop) {
            Log::error("[OPENAI RELAY V2] Could not connect to OpenAI", [
                'error' => $e->getMessage()
            ]);
            $loop->stop();
        });
    }

    private function configureOpenAISession()
    {
        try {
            // Configure session
            $sessionConfig = [
                'type' => 'session.update',
                'event_id' => uniqid('evt_'),
                'session' => [
                    'turn_detection' => [
                        'type' => 'server_vad',
                        'threshold' => 0.5,
                        'prefix_padding_ms' => 300,
                        'silence_duration_ms' => 500,
                        'create_response' => true
                    ],
                    'input_audio_format' => 'g711_ulaw',
                    'output_audio_format' => 'g711_ulaw',
                    'voice' => 'alloy',
                    'instructions' => $this->assistant->instructions ?? 'You are a helpful assistant.',
                    'modalities' => ['text', 'audio'],
                    'temperature' => 0.8,
                    'max_response_output_tokens' => 'inf'
                ]
            ];

            $this->openaiConn->send(json_encode($sessionConfig));
            Log::info("[OPENAI RELAY V2] Session configured", [
                'room' => $this->room,
                'assistant_id' => $this->assistant->id
            ]);

            // Create initial response
            $responseConfig = [
                'type' => 'response.create',
                'event_id' => uniqid('evt_'),
                'response' => [
                    'modalities' => ['text', 'audio'],
                    'temperature' => 0.8
                ]
            ];

            $this->openaiConn->send(json_encode($responseConfig));
            Log::info("[OPENAI RELAY V2] Initial response created", [
                'room' => $this->room
            ]);

        } catch (\Exception $e) {
            Log::error("[OPENAI RELAY V2] Failed to configure OpenAI session", [
                'error' => $e->getMessage(),
                'room' => $this->room
            ]);
        }
    }

    private function handleBareMessage($msg)
    {
        try {
            $message = json_decode($msg, true);
            $this->bareMessageCount++;
            $this->lastMessageTime = time();
            
            // Only log every 10th message to avoid spam
            if ($this->bareMessageCount % 10 === 1) {
                Log::info("[OPENAI RELAY V2] Received Bare message", [
                    'type' => $message['type'] ?? 'unknown',
                    'room' => $this->room,
                    'count' => $this->bareMessageCount
                ]);
            }

            // Forward to OpenAI
            if ($this->openaiConn) {
                $this->openaiConn->send($msg);
            }
        } catch (\Exception $e) {
            Log::error("[OPENAI RELAY V2] Error processing Bare message", [
                'error' => $e->getMessage(),
                'room' => $this->room
            ]);
        }
    }

    private function handleOpenAIMessage($msg)
    {
        try {
            $message = json_decode($msg, true);
            $this->openaiMessageCount++;
            $this->lastMessageTime = time();
            
            // Only log every 10th message to avoid spam
            if ($this->openaiMessageCount % 10 === 1) {
                Log::info("[OPENAI RELAY V2] Received OpenAI message", [
                    'type' => $message['type'] ?? 'unknown',
                    'room' => $this->room,
                    'count' => $this->openaiMessageCount
                ]);
            }

            // Forward to Bare WebSocket Server
            if ($this->bareConn) {
                $this->bareConn->send($msg);
            }
        } catch (\Exception $e) {
            Log::error("[OPENAI RELAY V2] Error processing OpenAI message", [
                'error' => $e->getMessage(),
                'room' => $this->room
            ]);
        }
    }
} 