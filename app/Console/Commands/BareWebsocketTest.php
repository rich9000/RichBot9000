<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use React\EventLoop\Factory;
use Ratchet\Client\Connector;
use React\Socket\Connector as ReactConnector;
use Illuminate\Support\Facades\Log;
use App\Models\Assistant;
use App\Models\User;

class BareWebsocketTest extends Command
{
    protected $signature = 'bare:test {--room=test_room} {--assistant_id=} {--user_id=} {--debug}';
    protected $description = 'Test the Bare WebSocket server functionality';

    private $richbotConn;
    private $room;
    private $assistant;
    private $user;
    private $isDebug = false;
    private $messageCount = 0;
    private $startTime;
    private $lastMessageTime;

    public function handle()
    {
        $this->startTime = time();
        $this->isDebug = $this->option('debug');
        $this->room = $this->option('room');
        $assistantId = $this->option('assistant_id');
        $userId = $this->option('user_id');

        $this->info("Starting Bare WebSocket Test");
        $this->info("===========================");
        $this->info("Room: {$this->room}");
        $this->info("Debug Mode: " . ($this->isDebug ? 'ON' : 'OFF'));

        if ($assistantId) {
            $this->assistant = Assistant::find($assistantId);
            if (!$this->assistant) {
                $this->error("Assistant not found: {$assistantId}");
                return;
            }
            $this->info("Assistant: {$this->assistant->name}");
        }

        if ($userId) {
            $this->user = User::find($userId);
            if ($this->user) {
                $this->info("User: {$this->user->name}");
            }
        }

        // Log test details
        Log::info("[TEST] Starting Bare WebSocket test", [
            'room' => $this->room,
            'assistant_id' => $assistantId,
            'user_id' => $userId,
            'debug_mode' => $this->isDebug
        ]);

        $loop = Factory::create();
        $connector = new Connector($loop, new ReactConnector($loop, [
            'tls' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ],
            'timeout' => 5 // 5 second timeout
        ]));

        // Try multiple connection URLs
        $connectionUrls = [
            "wss://richbot9000.local:9502/webclient",
            "ws://richbot9000.local:9502/webclient",
            "wss://localhost:9502/webclient",
            "ws://localhost:9502/webclient",
            "wss://".config('app.domain').":".config('app.ws_port_alt')."/webclient",
            "wss://richbot9000.local:9502/openai-realtime/{$this->room}",
            "wss://richbot9000.local:9502/monitor/{$this->room}"
        ];

        $this->info("Attempting to connect to WebSocket server...");
        
        $connectAttempt = function($urlIndex = 0) use ($connector, $loop, $connectionUrls) {
            if ($urlIndex >= count($connectionUrls)) {
                $this->error("All connection attempts failed");
                Log::error("[TEST] All connection attempts failed", [
                    'attempted_urls' => $connectionUrls
                ]);
                $loop->stop();
                    return;
            }

            $bareUrl = $connectionUrls[$urlIndex];
            $this->info("Attempting connection to: {$bareUrl}");

            $connector($bareUrl)
                ->then(function($conn) use ($loop) {
                    $this->richbotConn = $conn;
                    $this->info("Connected to Bare WebSocket server");

                    // Join the specified room
                    $this->richbotConn->send(json_encode([
                        'type' => 'join',
                        'room' => $this->room
                    ]));

                    // Set up message handler
                    $this->richbotConn->on('message', function($msg) {
                        $this->handleMessage($msg);
                    });

                    // Set up periodic test messages
                    $loop->addPeriodicTimer(5, function() {
                        $this->sendTestMessage();
                    });

                    // Set up heartbeat
                    $loop->addPeriodicTimer(30, function() {
                        $this->sendHeartbeat();
                    });

                    // Handle connection closure
                    $this->richbotConn->on('close', function() use ($loop) {
                        $this->info("Connection closed");
                        $loop->stop();
                    });

                }, function($e) use ($loop, $urlIndex, $connectionUrls) {
                    $this->error("Connection failed: " . $e->getMessage());
                    Log::error("[TEST] Connection attempt failed", [
                        'url' => $connectionUrls[$urlIndex],
                        'error' => $e->getMessage()
                    ]);
                    
                    // Try next URL
                    $connectAttempt($urlIndex + 1);
                });
        };

        // Start connection attempts
        $connectAttempt();

        $loop->run();
    }

    private function handleMessage($msg)
    {
        try {
            $message = json_decode($msg, true);
            $this->messageCount++;
            $this->lastMessageTime = time();

            if ($this->isDebug) {
                Log::info("[TEST] Received message", [
                    'type' => $message['type'] ?? 'unknown',
                    'timestamp' => date('Y-m-d H:i:s.u'),
                    'message' => $message
                ]);
            }

            switch ($message['type']) {
                case 'joined':
                    $this->info("Joined room: {$message['room']}");
                    break;

                case 'user_joined':
                    $this->info("User joined: {$message['member_name']}");
                    break;

                case 'pong':
                    $this->info("Received heartbeat response");
                    break;

                default:
                    if ($this->isDebug) {
                        $this->info("Received message: " . json_encode($message));
                    }
            }
        } catch (\Exception $e) {
            Log::error("[TEST] Error handling message", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    private function sendTestMessage()
    {
        try {
            $message = [
                'type' => 'message',
                'content' => 'Test message ' . date('Y-m-d H:i:s')
            ];

            $this->richbotConn->send(json_encode($message));
            
            if ($this->isDebug) {
                Log::info("[TEST] Sent test message", [
                    'message' => $message,
                    'timestamp' => date('Y-m-d H:i:s.u')
                ]);
            }
        } catch (\Exception $e) {
            Log::error("[TEST] Error sending test message", [
                'error' => $e->getMessage()
            ]);
        }
    }

    private function sendHeartbeat()
    {
        try {
            $this->richbotConn->send(json_encode([
                'type' => 'ping',
            'time' => time()
        ]));
            
            if ($this->isDebug) {
                Log::info("[TEST] Sent heartbeat", [
                    'timestamp' => date('Y-m-d H:i:s.u')
                ]);
            }
        } catch (\Exception $e) {
            Log::error("[TEST] Error sending heartbeat", [
                'error' => $e->getMessage()
            ]);
        }
    }
} 