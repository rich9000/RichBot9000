<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use OpenSwoole\WebSocket\Server;
use OpenSwoole\WebSocket\Frame;
use OpenSwoole\Constant;
use OpenSwoole\Table;

class StartTwilioWebsocket extends Command
{
    protected $signature = 'twilio:websocket';
    protected $description = 'Start WebSocket server for Twilio audio streaming with OpenAI integration';

    private Table $activeCallsTable;
    private array $openAiConnections = [];

    public function __construct()
    {
        parent::__construct();
        
        // Initialize active calls table
        $this->activeCallsTable = new Table(1024);
        $this->activeCallsTable->column('twilioFd', Table::TYPE_INT);
        $this->activeCallsTable->column('openAiFd', Table::TYPE_INT);
        $this->activeCallsTable->column('callSid', Table::TYPE_STRING, 64);
        $this->activeCallsTable->column('status', Table::TYPE_STRING, 32);
        $this->activeCallsTable->create();
    }

    public function handle()
    {
        $this->info("Starting Twilio WebSocket Server...");
        $server = new Server('0.0.0.0', 9501, SWOOLE_PROCESS, SWOOLE_SOCK_TCP | SWOOLE_SSL);

        $this->configureSSL($server);
        $this->configureServer($server);

        $server->start();
    }

    private function configureSSL(Server $server)
    {
        $this->info("Configuring SSL...");
        $server->set([
            'ssl_cert_file' => '/etc/letsencrypt/live/boxcrossranch.com/fullchain.pem',
            'ssl_key_file' => '/etc/letsencrypt/live/boxcrossranch.com/privkey.pem',
            'ssl_verify_peer' => false,
        ]);
    }

    private function configureServer(Server $server)
    {
        $server->on(Constant::EVENT_START, function() {
            $this->info("[SERVER] Twilio WebSocket server started on wss://richbot9000.com:9501");
        });

        $server->on('handshake', function($request, $response) {
            // Validate Twilio request if needed
            $secWebSocketKey = $request->header['sec-websocket-key'];
            $key = base64_encode(sha1($secWebSocketKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
            
            $headers = [
                'Upgrade' => 'websocket',
                'Connection' => 'Upgrade',
                'Sec-WebSocket-Accept' => $key,
                'Sec-WebSocket-Version' => '13',
            ];
            
            foreach ($headers as $key => $val) {
                $response->header($key, $val);
            }
            
            $response->status(101);
            $response->end();
            return true;
        });

        $server->on(Constant::EVENT_CONNECT, function($server, $fd) {
            $this->info("[CONNECT] New Twilio connection (fd: {$fd})");
        });

        $server->on(Constant::EVENT_MESSAGE, function($server, Frame $frame) {
            try {
                $data = json_decode($frame->data, true);
                if (!$data || !isset($data['event'])) {
                    throw new \Exception("Invalid message format");
                }

                switch ($data['event']) {
                    case 'start':
                        $this->handleCallStart($server, $frame->fd, $data);
                        break;
                        
                    case 'media':
                        $this->handleMediaRelay($server, $frame->fd, $data);
                        break;

                    case 'stop':
                        $this->handleCallEnd($server, $frame->fd, $data);
                        break;
                }
            } catch (\Exception $e) {
                $this->error("[ERROR] " . $e->getMessage());
            }
        });

        $server->on(Constant::EVENT_CLOSE, function($server, $fd) {
            $callSid = $this->findCallSidByFd($fd);
            if ($callSid) {
                $this->cleanupCall($callSid);
            }
        });
    }

    private function handleCallStart($server, $fd, $data)
    {
        $callSid = $data['start']['streamSid'] ?? null;
        if (!$callSid) {
            $this->error("[TWILIO] Missing Call SID");
            return;
        }

        // Create OpenAI WebSocket connection
        $openAiClient = $this->createOpenAIWebSocketClient($server, $fd, $callSid);
        
        // Store call information
        $this->activeCallsTable->set($callSid, [
            'twilioFd' => $fd,
            'openAiFd' => $openAiClient->id ?? 0,
            'callSid' => $callSid,
            'status' => 'active'
        ]);

        $this->info("[CALL] Started new call: {$callSid}");
    }

    private function handleMediaRelay($server, $fd, $data)
    {
        try {
            $callSid = $this->findCallSidByFd($fd);
            if (!$callSid) {
                $this->error("[TWILIO] Cannot find callSid for fd: {$fd}");
                return;
            }

            // Check connections before processing
            $this->checkConnections($callSid);

            if (!isset($this->openAiConnections[$callSid]) || 
                $this->openAiConnections[$callSid]['status'] !== 'connected') {
                $this->error("[OPENAI] Connection not active for call {$callSid}");
                // Attempt to reconnect
                $this->attemptReconnection($callSid);
                return;
            }

            // Save incoming Twilio audio
            $timestamp = time();
            $incomingPath = storage_path("logs/audio/twilio_{$callSid}_{$timestamp}.ulaw");
            if (!file_exists(dirname($incomingPath))) {
                mkdir(dirname($incomingPath), 0755, true);
            }
            file_put_contents($incomingPath, base64_decode($data['media']['payload']));
            $this->info("[TWILIO] Saved incoming audio to: {$incomingPath}");

            $connection = $this->openAiConnections[$callSid]['connection'] ?? null;
            if (!$connection) {
                $this->error("[OPENAI] No active connection for call {$callSid}");
                return;
            }

            // Debug incoming audio from Twilio
            $this->info("[TWILIO] Received audio payload size: " . strlen($data['media']['payload'] ?? '') . " bytes");

            // Send audio data to OpenAI
            $audioMessage = [
                'type' => 'input_audio_buffer.append',
                'audio' => $data['media']['payload'],
                'sequence_id' => uniqid(),
                'timestamp' => time() * 1000,
                'format' => [
                    'type' => 'mulaw',
                    'sample_rate' => 8000,
                    'channels' => 1
                ]
            ];
            
            $success = $connection->send(json_encode($audioMessage));
            
            if ($success) {
                $this->info("[OPENAI] Audio sent to OpenAI successfully");
                $this->info("[OPENAI] Audio message size: " . strlen(json_encode($audioMessage)) . " bytes");
            } else {
                $this->error("[OPENAI] Failed to send audio to OpenAI");
            }

        } catch (\Exception $e) {
            $this->error("[RELAY] Error: {$e->getMessage()}");
        }
    }

    private function handleClose($server, $fd)
    {
        $callSid = $this->findCallSidByFd($fd);
        if ($callSid) {
            $this->cleanupCall($callSid);
        }
    }

    private function cleanupCall($callSid)
    {
        try {
            if (isset($this->openAiConnections[$callSid])) {
                $connection = $this->openAiConnections[$callSid]['connection'] ?? null;
                if ($connection) {
                    $connection->close();
                }
                
                $loop = $this->openAiConnections[$callSid]['loop'] ?? null;
                if ($loop) {
                    $loop->stop();
                }

                unset($this->openAiConnections[$callSid]);
            }

            $this->activeCallsTable->del($callSid);
            $this->info("[CALL] Cleaned up call: {$callSid}");

        } catch (\Exception $e) {
            $this->error("[CLEANUP] Error cleaning up call {$callSid}: {$e->getMessage()}");
        }
    }

    private function findCallSidByFd($fd)
    {
        foreach ($this->activeCallsTable as $callSid => $data) {
            if ($data['twilioFd'] === $fd) {
                return $callSid;
            }
        }
        return null;
    }

    private function createOpenAIWebSocketClient($server, $fd, $callSid)
    {
        try {
            $loop = \React\EventLoop\Factory::create();
            $connector = new \Ratchet\Client\Connector($loop, new \React\Socket\Connector($loop));
            
            // Use the correct WebSocket URL with model parameter
            $url = "wss://api.openai.com/v1/realtime?model=gpt-4o-realtime-preview-2024-10-01";
            $headers = [
                'Authorization' => 'Bearer ' . env('OPENAI_API_KEY'),
                'OpenAI-Beta' => 'realtime=v1',
            ];

            // Store promise in the connections array
            $this->openAiConnections[$callSid] = [
                'loop' => $loop,
                'connection' => null
            ];

            $connector($url, [], $headers)->then(
                function(\Ratchet\Client\WebSocket $conn) use ($server, $fd, $callSid, $loop) {
                    $this->info("[OPENAI] WebSocket connection established for call {$callSid}");
                    $this->openAiConnections[$callSid]['connection'] = $conn;
                    $this->openAiConnections[$callSid]['status'] = 'connected';
                    
                    // Add ping/pong to keep connection alive
                    $pingTimer = $loop->addPeriodicTimer(30, function() use ($conn, $callSid) {
                        if ($conn->isConnected()) {
                            $this->info("[OPENAI] Sending ping for call {$callSid}");
                            $conn->send(json_encode(['type' => 'ping']));
                        }
                    });

                    $conn->on('close', function($code = null, $reason = null) use ($callSid, $pingTimer, $loop) {
                        $this->warn("[OPENAI] Connection closed for call {$callSid}");
                        $this->warn("[OPENAI] Close code: {$code}, reason: {$reason}");
                        $this->openAiConnections[$callSid]['status'] = 'disconnected';
                        $loop->cancelTimer($pingTimer);
                        
                        // Attempt reconnection
                        $this->attemptReconnection($callSid);
                    });

                    // Send the response.create event
                    $createResponse = [
                        'type' => 'response.create',
                        'response' => [
                            'modalities' => ['text', 'audio'],
                            'instructions' => 'You are a helpful AI assistant. Please assist the user.',
                            'voice' => 'alloy',
                            'temperature' => 0.7,
                            'max_output_tokens' => 150,
                            
                        ]
                    ];

                    $conn->send(json_encode($createResponse));
                    $this->info("[OPENAI] Response creation request sent");
                    $this->info("[OPENAI] Request payload: " . json_encode($createResponse, JSON_PRETTY_PRINT));

                    // Add buffer for audio chunks
                    $audioBuffer = '';

                    // Handle incoming messages from OpenAI
                    $conn->on('message', function($msg) use ($server, $fd, $callSid, &$audioBuffer) {
                        $data = json_decode($msg, true);
                        
                        if (!$data) {
                            $this->error("[OPENAI] Invalid JSON received");
                            return;
                        }

                        $type = $data['type'] ?? 'unknown';
                        $this->info("[OPENAI] Received message type: {$type}");

                        switch ($type) {
                            case 'session.created':
                                $this->info("[OPENAI] Session initialized successfully");
                                break;

                            case 'response.created':
                                $this->info("[OPENAI] New response started");
                                break;

                            case 'rate_limits.updated':
                                $limits = $data['rate_limits'] ?? [];
                                $this->info("[OPENAI] Rate limits updated: " . json_encode($limits));
                                break;

                            case 'response.output_item.added':
                                $this->info("[OPENAI] New output item added");
                                break;

                            case 'conversation.item.created':
                                $this->info("[OPENAI] New conversation item created");
                                break;

                            case 'response.content_part.added':
                                $this->info("[OPENAI] New content part added");
                                break;

                            case 'response.audio_transcript.delta':
                                $delta = $data['delta'] ?? '';
                                $this->info("[OPENAI] Transcript delta: {$delta}");
                                break;

                            case 'response.audio.delta':
                                if (isset($data['delta'])) {
                                    // Add to buffer instead of sending immediately
                                    $audioBuffer .= $data['delta'];
                                    $this->info("[OPENAI] Audio delta buffered, current size: " . strlen($audioBuffer) . " bytes");
                                }
                                break;

                            case 'response.audio.done':
                                $this->info("[OPENAI] Audio response completed, sending full audio");
                                
                                // Save outgoing OpenAI audio
                                $timestamp = time();
                                $outgoingPath = storage_path("logs/audio/openai_{$callSid}_{$timestamp}.ulaw");
                                if (!file_exists(dirname($outgoingPath))) {
                                    mkdir(dirname($outgoingPath), 0755, true);
                                }
                                file_put_contents($outgoingPath, $audioBuffer);
                                $this->info("[OPENAI] Saved outgoing audio to: {$outgoingPath}");
                                
                                // Send to Twilio as before
                                $twilioMessage = [
                                    'event' => 'media',
                                    'streamSid' => $callSid,
                                    'media' => [
                                        'payload' => $audioBuffer,
                                        'track' => 1,
                                        'chunk' => 1,
                                        'timestamp' => time() * 1000,
                                        'encoding' => 'audio/x-mulaw',
                                        'sample_rate' => 8000,
                                        'channels' => 1
                                    ]
                                ];
                                
                                $success = $server->push($fd, json_encode($twilioMessage));
                                if ($success) {
                                    $this->info("[OPENAI] Complete audio sent to Twilio successfully");
                                    $this->info("[OPENAI] Total audio size: " . strlen($audioBuffer) . " bytes");
                                } else {
                                    $this->error("[OPENAI] Failed to send complete audio to Twilio");
                                }
                                
                                // Clear the buffer for the next response
                                $audioBuffer = '';
                                break;

                            case 'response.audio_transcript.done':
                                $transcript = $data['transcript'] ?? '';
                                $this->info("[OPENAI] Final transcript: {$transcript}");
                                break;

                            case 'response.content_part.done':
                                $part = $data['part'] ?? [];
                                $this->info("[OPENAI] Content part completed: " . json_encode($part));
                                break;

                            case 'response.output_item.done':
                                $this->info("[OPENAI] Output item completed");
                                break;

                            case 'response.done':
                                $this->info("[OPENAI] Full response completed");
                                break;

                            case 'input_audio_buffer.speech_started':
                                $this->info("[OPENAI] Speech started");
                                break;

                            case 'input_audio_buffer.speech_stopped':
                                $this->info("[OPENAI] Speech stopped");
                                break;

                            case 'error':
                                $this->error("[OPENAI] Error received: " . ($data['message'] ?? 'Unknown error'));
                                $this->error("[OPENAI] Stack trace: " . json_encode($data, JSON_PRETTY_PRINT)
                        );
                                break;

                            default:
                                $this->info("[OPENAI] Unhandled message type: {$type}");
                                $this->info("[OPENAI] Message content: " . json_encode($data));
                                break;
                        }
                    });

                    // Handle connection close
                    $conn->on('close', function($code = null, $reason = null) use ($callSid) {
                        $this->warn("[OPENAI] Connection closed for call {$callSid}: {$code} - {$reason}");
                        unset($this->openAiConnections[$callSid]);
                    });

                },
                function(\Exception $e) use ($callSid) {
                    $this->error("[OPENAI] Could not connect: {$e->getMessage()}");
                    unset($this->openAiConnections[$callSid]);
                }
            );

            // Start the event loop
            go(function() use ($loop, $callSid) {
                try {
                    $loop->run();
                } catch (\Exception $e) {
                    $this->error("[OPENAI] Loop error for call {$callSid}: {$e->getMessage()}");
                }
            });

            return true;

        } catch (\Exception $e) {
            $this->error("[OPENAI] Setup error: {$e->getMessage()}");
            return false;
        }
    }

    private function attemptReconnection($callSid)
    {
        $this->info("[OPENAI] Attempting to reconnect for call {$callSid}");
        
        // Only attempt reconnection if the call is still active
        if ($this->activeCallsTable->exists($callSid)) {
            $fd = $this->activeCallsTable->get($callSid)['twilioFd'];
            $this->createOpenAIWebSocketClient($this->server, $fd, $callSid);
        } else {
            $this->info("[OPENAI] Call {$callSid} no longer active, skipping reconnection");
        }
    }

    // Add a method to check connection status
    private function checkConnections($callSid)
    {
        $this->info("\n=== Connection Status for {$callSid} ===");
        
        // Check Twilio connection
        $twilioFd = $this->activeCallsTable->get($callSid)['twilioFd'] ?? null;
        $twilioConnected = $twilioFd && $this->server->isEstablished($twilioFd);
        $this->info("Twilio Connection: " . ($twilioConnected ? 'Connected' : 'Disconnected'));
        
        // Check OpenAI connection
        $openAiConnection = $this->openAiConnections[$callSid] ?? null;
        $openAiStatus = $openAiConnection['status'] ?? 'unknown';
        $this->info("OpenAI Connection: {$openAiStatus}");
        
        if ($openAiConnection && isset($openAiConnection['connection'])) {
            $this->info("OpenAI WebSocket State: " . 
                ($openAiConnection['connection']->isConnected() ? 'Connected' : 'Disconnected'));
        }
        
        $this->info("===============================\n");
    }

    private function logState()
    {
        $this->info("\n=== Server State ===");
        
        $this->info("\nActive Calls:");
        foreach ($this->activeCallsTable as $callSid => $callData) {
            $this->info("- Call SID: {$callSid}");
            $this->info("  Twilio FD: {$callData['twilioFd']}");
            $this->info("  OpenAI FD: {$callData['openAiFd']}");
            $this->info("  Status: {$callData['status']}");
        }
        
        $this->info("\nOpenAI Connections: " . count($this->openAiConnections));
        $this->info("================\n");
    }
}