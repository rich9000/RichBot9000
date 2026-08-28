<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use React\EventLoop\Factory;
use Ratchet\Client\Connector;
use React\Socket\Connector as ReactConnector;
use Illuminate\Support\Facades\Log;
use App\Models\Conversation;
use Twilio\Rest\Client as TwilioClient;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Process\Process;

class BareWebsocketCallRelay extends Command
{
    protected $signature = 'bare:call {phone_number} {--conversation_id=} {--assistant_id=} {--room=} {--pipeline_id=} {--debug}';
    protected $description = 'Start a WebSocket relay between a phone call and the bare WebSocket server';

    private $richbotConn;
    private $room;
    private $phoneNumber;
    private $callSid;
    private $currentResponse = null;
    private $messageCount = 0;
    private $lastHeartbeat = null;
    private $startTime;
    private $isDebug = false;
    private $conversationId;
    private $assistantId;
    private $pipelineId;

    public function handle()
    {
        $this->startTime = time();
        $this->isDebug = $this->option('debug');
        
        $this->phoneNumber = $this->argument('phone_number');
        $this->conversationId = $this->option('conversation_id');
        $this->assistantId = $this->option('assistant_id');
        $this->pipelineId = $this->option('pipeline_id');
        $this->room = $this->option('room') ?? uniqid('call_');

        $this->info('Starting Bare Call Relay');
        $this->info('====================');
        
        $this->info("Phone: {$this->phoneNumber}");
        $this->info("Room: {$this->room}");
        if ($this->conversationId) {
            $this->info("Conversation ID: {$this->conversationId}");
        }
        if ($this->assistantId) {
            $this->info("Assistant ID: {$this->assistantId}");
        }
        if ($this->pipelineId) {
            $this->info("Pipeline ID: {$this->pipelineId}");
        }
        $this->info("Debug Mode: " . ($this->isDebug ? 'ON' : 'OFF'));
        $this->info('====================');

        // Log connection details
        Log::info("[Call Relay] Starting connection", [
            'room' => $this->room,
            'phone_number' => $this->phoneNumber,
            'conversation_id' => $this->conversationId,
            'assistant_id' => $this->assistantId,
            'pipeline_id' => $this->pipelineId,
            'debug_mode' => $this->isDebug
        ]);

        // Start the call using Twilio service
        $twilio = new \App\Services\Twilio($this->phoneNumber);
        $call = $twilio->startCall($this->phoneNumber, $this->room);

        if (!$call) {
            $this->error("Failed to start call");
            return 1;
        }

        $this->callSid = $call->sid;
        $this->info("Call SID: {$this->callSid}");

        Cache::put($this->room, $this->callSid);

        if($this->assistantId) {
            while($call->status == 'queued' || $call->status == 'initiated' || $call->status == 'ringing'){
                sleep(1);
                $call = $twilio->client->calls($this->callSid)->fetch();
                $this->info("Call Status: {$call->status}");
            }

            if($call->status == 'in-progress') {
                $this->info("Call Status: {$call->status}");
                sleep(1);

                $call = $twilio->client->calls($this->callSid)->fetch();
                $this->info("Call Status: {$call->status}");
                
                Log::info("[Call Relay] Call Status: {$call->status} - Starting assistant {$this->assistantId} in room {$this->room}");
                
                // Start the assistant process with optional conversation_id
                $assistantCommand = ['php', 'artisan', 'bare:assistant', $this->room, $this->assistantId];
                if ($this->conversationId) {
                    $assistantCommand[] = '--conversation_id=' . $this->conversationId;
                }
                $assistantProcess = new Process($assistantCommand);
                $assistantProcess->start();

                while($call->status == 'in-progress') {
                    sleep(1);
                    $call = $twilio->client->calls($this->callSid)->fetch();
                    $this->info("Call Status: {$call->status}");
                }
            }

            if($call->status == 'completed' || $call->status == 'failed' || $call->status == 'busy' || $call->status == 'no-answer') {
                $this->info("Call Status: {$call->status}");
                sleep(1);

                $call = $twilio->client->calls($this->callSid)->fetch();
                $this->info("Call Status: {$call->status}");
            }
        }
            
        $this->callSid = $call->sid;
        $this->info("Call SID: {$this->callSid}");  
        $this->info("Call Status: {$call->status}");

        sleep(10);

        // get an update on the call status
        $call = $twilio->client->calls($this->callSid)->fetch();
        $this->info("Call Status: {$call->status}");

        while ($call->status == 'in-progress') {
            sleep(1);
            $call = $twilio->client->calls($this->callSid)->fetch();
            $this->info("Call Status: {$call->status}");
        }

        // get an update on the call status
        $call = $twilio->client->calls($this->callSid)->fetch();
        $this->info("Call Status: {$call->status}");

        //dd($call);

        exit;

        // Create loop and connector for WebSocket
        $loop = Factory::create();
        $connector = new Connector($loop, new ReactConnector($loop, [
            'tls' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]));

        // Connect to Bare WebSocket Server
        $bareUrl = "wss://richbot9000.local:9502";
        $connector($bareUrl)
            ->then(function($conn) use ($loop) {
                Log::info("[Call Relay] Connected to Bare WebSocket", [
                    'room' => $this->room
                ]);

                $this->richbotConn = $conn;
                
                // Join the specified room
                $this->richbotConn->send(json_encode([
                    'type' => 'join',
                    'room' => $this->room
                ]));

                // Notify room about call connection
                $this->notifyCallConnection();

                // Handle messages from Bare WebSocket Server
                $this->richbotConn->on('message', function($msg) {
                    $this->handleBareMessage($msg);
                });

                // Set up heartbeat
                $loop->addPeriodicTimer(30, function() {
                    $this->sendHeartbeat();
                });

                // Set up status display timer
                $loop->addPeriodicTimer(5, function() {
                    $this->displayStatus();
                });

                // Handle connection closure
                $this->richbotConn->on('close', function() use ($loop) {
                    Log::error("[Call Relay] Bare WebSocket connection closed");
                    $this->handleDisconnect();
                    $loop->stop();
                });

            }, function($e) use ($loop) {
                Log::error("[Call Relay] Could not connect to Bare WebSocket", [
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
        $this->line("Messages Processed: {$this->messageCount}");
        $this->line("Last Heartbeat: {$lastHeartbeatAgo}s ago");
        $this->line("Current Room: {$this->room}");
        $this->line("Call SID: {$this->callSid}");
        $this->line("Connection: " . ($this->richbotConn ? 'Active' : 'Inactive'));
    }

    private function debugLog($message, $data = [])
    {
        if ($this->isDebug) {
            $this->line("\n[DEBUG] " . $message);
            if (!empty($data)) {
                $this->line(json_encode($data, JSON_PRETTY_PRINT));
            }
        }
    }

    private function handleBareMessage($msg)
    {
        try {
            $message = json_decode($msg, true);
            $this->messageCount++;
            
            $this->debugLog("Received message:", $message);

            Log::debug("[Call Relay] Bare Call Relay received message", [
                'room' => $this->room,
                'message' => $message,
                'message_count' => $this->messageCount
            ]);

            if (!$message || !isset($message['type'])) {
                $this->debugLog("Invalid message format");
                return;
            }

            switch ($message['type']) {
                case 'joined':
                    $this->info("✓ Joined room: {$message['room']}");
                    Log::info("[Call Relay] Joined room {$message['room']}");
                    break;

                case 'message':
                    // Handle incoming messages
                    if (isset($message['message'])) {
                        $this->handleIncomingMessage($message['message']);
                        $this->debugLog("Processed message", [
                            'content' => $message['message']
                        ]);
                    }
                    break;

                case 'left':
                    $this->warn("← Left room: {$message['room']}");
                    Log::info("[Call Relay] Left room {$message['room']}");
                    $this->handleDisconnect();
                    break;

                default:
                    $this->debugLog("Unknown message type", [
                        'type' => $message['type']
                    ]);
            }
        } catch (\Exception $e) {
            $this->error("Error processing message: " . $e->getMessage());
            Log::error("[Call Relay] Error processing Bare relay message", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'original_message' => $msg
            ]);
        }
    }

    private function handleIncomingMessage($message)
    {
        // Log the incoming message
        $this->info("→ Received: " . substr($message, 0, 50) . (strlen($message) > 50 ? '...' : ''));
        
        Log::info("[Call Relay] Received message", [
            'message' => $message,
            'call_sid' => $this->callSid,
            'timestamp' => date('Y-m-d H:i:s')
        ]);

        $this->debugLog("Processing message", [
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s'),
            'call_sid' => $this->callSid
        ]);
    }

    private function sendHeartbeat()
    {
        try {
            $this->lastHeartbeat = time();
            $this->richbotConn->send(json_encode([
                'type' => 'ping',
                'time' => $this->lastHeartbeat
            ]));
            
            $this->debugLog("Heartbeat sent", [
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            $this->error("Heartbeat error: " . $e->getMessage());
            Log::error("[Call Relay] Heartbeat error", [
                'error' => $e->getMessage()
            ]);
        }
    }

    private function notifyCallConnection()
    {
        try {
            // Generate a unique call ID
            $this->callSid = uniqid('call_');

            $this->info("✓ Call connection established");
            $this->line("Call SID: {$this->callSid}");

            Log::info("[Call Relay] Call connection established", [
                'call_sid' => $this->callSid,
                'room' => $this->room
            ]);

            // Send call status to room
            $this->richbotConn->send(json_encode([
                'type' => 'message',
                'room' => $this->room,
                'message' => "Phone connection established with {$this->phoneNumber}"
            ]));

            // Create a conversation record
            Conversation::create([
                'id' => $this->callSid,
                'title' => "Call Connection: {$this->phoneNumber}",
                'type' => 'bare_call',
                'status' => 'active'
            ]);

            $this->debugLog("Connection initialized", [
                'call_sid' => $this->callSid,
                'phone_number' => $this->phoneNumber,
                'room' => $this->room
            ]);

        } catch (\Exception $e) {
            $this->error("Connection failed: " . $e->getMessage());
            Log::error("[Call Relay] Failed to establish connection", [
                'error' => $e->getMessage(),
                'phone_number' => $this->phoneNumber
            ]);

            $this->richbotConn->send(json_encode([
                'type' => 'message',
                'room' => $this->room,
                'message' => "Failed to establish connection: " . $e->getMessage()
            ]));
        }
    }

    private function handleDisconnect()
    {
        if ($this->callSid) {
            try {
                // Update conversation status
                $conversation = Conversation::find($this->callSid);
                if ($conversation) {
                    $conversation->update(['status' => 'completed']);
                }
                
                $this->warn("Call connection ended");
                Log::info("[Call Relay] Connection ended", ['call_sid' => $this->callSid]);
                
                // Notify room about disconnection
                $this->richbotConn->send(json_encode([
                    'type' => 'message',
                    'room' => $this->room,
                    'message' => "Call connection ended"
                ]));

                $this->debugLog("Disconnection handled", [
                    'call_sid' => $this->callSid,
                    'duration' => time() - $this->startTime,
                    'messages_processed' => $this->messageCount
                ]);
            } catch (\Exception $e) {
                $this->error("Disconnect error: " . $e->getMessage());
                Log::error("[Call Relay] Failed to handle disconnection", [
                    'error' => $e->getMessage(),
                    'call_sid' => $this->callSid
                ]);
            }
        }
    }
} 