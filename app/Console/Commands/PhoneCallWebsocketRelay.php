<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use React\EventLoop\Factory;
use Ratchet\Client\Connector;
use React\Socket\Connector as ReactConnector;
use Illuminate\Support\Facades\Log;
use App\Services\Twilio;
use App\Models\Conversation;

class PhoneCallWebsocketRelay extends Command
{
    protected $signature = 'phone:relay {room} {phone_number}';
    protected $description = 'Start a WebSocket relay between Twilio phone call and the bare WebSocket server';

    private $richbotConn;
    private $room;
    private $phoneNumber;
    private $callSid;
    private $twilio;

    public function handle()
    {
        $this->room = $this->argument('room');
        $this->phoneNumber = $this->argument('phone_number');

        // Initialize Twilio client
        $this->twilio = new TwilioClient(config('services.twilio.sid'), config('services.twilio.token'));

        // Log connection details
        Log::info("Phone Call Relay: Starting connection", [
            'room' => $this->room,
            'phone_number' => $this->phoneNumber
        ]);

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
        $bareUrl = "wss://".config('app.domain').":".config('app.ws_port_alt');
        $connector($bareUrl)
            ->then(function($conn) use ($loop) {
                Log::info("Phone Call Relay: Connected to Bare WebSocket", [
                    'room' => $this->room
                ]);

                $this->richbotConn = $conn;
                
                // Join the specified room
                $this->richbotConn->send(json_encode([
                    'type' => 'join',
                    'room' => $this->room
                ]));

                // Initiate phone call
                $this->initiateCall();

                // Handle messages from Bare WebSocket Server
                $this->richbotConn->on('message', function($msg) {
                    $this->handleBareMessage($msg);
                });

                // Handle connection closure
                $this->richbotConn->on('close', function() use ($loop) {
                    Log::error("Phone Call Relay: Bare WebSocket connection closed");
                    // End the call if it's active
                    if ($this->callSid) {
                        $this->endCall();
                    }
                    $loop->stop();
                });

            }, function($e) use ($loop) {
                Log::error("Phone Call Relay: Could not connect to Bare WebSocket", [
                    'error' => $e->getMessage()
                ]);
                $loop->stop();
            });

        $loop->run();
    }

    private function initiateCall()
    {
        try {
            // Make the call using Twilio
            $call = $this->twilio->calls->create(
                $this->phoneNumber,
                config('services.twilio.from'),
                [
                    'url' => route('voice'),
                    'statusCallback' => route('voice.status'),
                    'statusCallbackEvent' => ['initiated', 'ringing', 'answered', 'completed'],
                    'statusCallbackMethod' => 'POST'
                ]
            );

            $this->callSid = $call->sid;
            Log::info("Phone Call Relay: Call initiated", [
                'call_sid' => $this->callSid,
                'room' => $this->room
            ]);

            // Send call status to room
            $this->richbotConn->send(json_encode([
                'type' => 'message',
                'room' => $this->room,
                'message' => "Phone call initiated to {$this->phoneNumber}"
            ]));

            // Create a conversation record
            Conversation::create([
                'id' => $this->callSid,
                'title' => "Phone Call: {$this->phoneNumber}",
                'assistant_type' => 'phone_call',
                'type' => 'phone_call',
                'status' => 'active'
            ]);

        } catch (\Exception $e) {
            Log::error("Phone Call Relay: Failed to initiate call", [
                'error' => $e->getMessage(),
                'phone_number' => $this->phoneNumber
            ]);

            $this->richbotConn->send(json_encode([
                'type' => 'message',
                'room' => $this->room,
                'message' => "Failed to initiate phone call: " . $e->getMessage()
            ]));
        }
    }

    private function endCall()
    {
        if ($this->callSid) {
            try {
                $this->twilio->calls($this->callSid)->update(['status' => 'completed']);
                Log::info("Phone Call Relay: Call ended", ['call_sid' => $this->callSid]);
            } catch (\Exception $e) {
                Log::error("Phone Call Relay: Failed to end call", [
                    'error' => $e->getMessage(),
                    'call_sid' => $this->callSid
                ]);
            }
        }
    }

    private function handleBareMessage($msg)
    {
        try {
            $message = json_decode($msg, true);
            
            Log::debug("Phone Call Relay received message", [
                'room' => $this->room,
                'message' => $message
            ]);

            if (!$message || !isset($message['type'])) {
                return;
            }

            switch ($message['type']) {
                case 'joined':
                    Log::info("Phone Call Relay: Joined room {$message['room']}");
                    break;

                case 'message':
                    // Handle any room messages if needed
                    break;

                case 'left':
                    Log::info("Phone Call Relay: Left room {$message['room']}");
                    if ($this->callSid) {
                        $this->endCall();
                    }
                    break;
            }
        } catch (\Exception $e) {
            Log::error("Error processing Bare relay message", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'original_message' => $msg
            ]);
        }
    }
} 