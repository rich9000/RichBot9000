<?php

namespace App\Services;

use OpenSwoole\Coroutine\Http\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class OpenAIRelay
{
    private $connection;
    private $chatId;
    private $clientFd;
    private $server;
    private $isConnected = false;
    private $messageQueue = [];
    private $lastPingTime;
    private $pingInterval = 15;
    private $assistantId;
    private $assistantInfo;

    public function __construct(string $chatId, int $clientFd, $server, string $assistantId)
    {
        $this->chatId = $chatId;
        $this->clientFd = $clientFd;
        $this->server = $server;
        $this->assistantId = $assistantId;
        $this->lastPingTime = time();
    }

    private async function loadAssistantInfo(): Promise
    {
        try {
            // Make API call to get assistant info
            $response = await Http::withToken(config('services.openai.api_key'))
                ->get("/api/ollama_assistants/{$this->assistantId}");
                
            if (!$response->successful()) {
                throw new \Exception("Failed to load assistant info");
            }
            
            $this->assistantInfo = $response->json();
            
            Log::info("Loaded assistant info", [
                'assistant_id' => $this->assistantId,
                'name' => $this->assistantInfo['name'],
                'type' => $this->assistantInfo['type'],
                'interactive' => $this->assistantInfo['interactive']
            ]);
            
            return true;
        } catch (\Exception $e) {
            Log::error("Failed to load assistant info", [
                'assistant_id' => $this->assistantId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function connect(): bool
    {
        try {
            Log::debug("Starting OpenAI connection", [
                'chat_id' => $this->chatId,
                'client_fd' => $this->clientFd,
                'assistant_id' => $this->assistantId
            ]);

            // Load assistant info first
            if (!await $this->loadAssistantInfo()) {
                throw new \Exception("Failed to load assistant information");
            }

            $this->connection = new Client(
                'api.openai.com',
                443,
                true
            );

            $this->connection->setHeaders([
                'Authorization' => 'Bearer ' . config('services.openai.api_key'),
                'OpenAI-Beta' => 'realtime=v1',
                'Content-Type' => 'application/json'
            ]);

            $url = "/v1/realtime?model=gpt-4o-realtime-preview-2024-12-17";
            if (!$this->connection->upgrade($url)) {
                throw new \Exception("WebSocket upgrade failed: " . $this->connection->errMsg);
            }

            $this->isConnected = true;
            $this->configureSession();

            // Start message handling coroutine
            go(function() {
                $this->handleMessages();
            });

            return true;

        } catch (\Exception $e) {
            Log::error("OpenAI connection error", [
                'chat_id' => $this->chatId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    private function configureSession(): void
    {
        // Match the working configuration from StartSwooleServer.php
        $sessionConfig = [
            'type' => 'session.update',
            'session' => [
                'turn_detection' => ['type' => 'server_vad'],
                'input_audio_format' => 'g711_ulaw',
                'output_audio_format' => 'g711_ulaw',
                'voice' => 'alloy',
                'instructions' => 'You are a helpful assistant.',
                'modalities' => ['text', 'audio'],
                'temperature' => 0.8
            ]
        ];

        // Add system message from assistant if available
        if (!empty($this->assistantInfo['system_message'])) {
            $sessionConfig['session']['instructions'] = $this->assistantInfo['system_message'];
        }

        Log::info("Configuring OpenAI session", [
            'chat_id' => $this->chatId,
            'assistant_id' => $this->assistantId,
            'name' => $this->assistantInfo['name'],
            'type' => $this->assistantInfo['type'],
            'interactive' => $this->assistantInfo['interactive'],
            'modalities' => $sessionConfig['session']['modalities']
        ]);

        $this->connection->push(json_encode($sessionConfig));

        // Send response.create to trigger initial greeting
        $responseConfig = [
            'type' => 'response.create',
            'response' => [
                'modalities' => $sessionConfig['session']['modalities'],
                'instructions' => $sessionConfig['session']['instructions'],
                'voice' => $sessionConfig['session']['voice'],
                'output_audio_format' => $sessionConfig['session']['output_audio_format']
            ]
        ];

        Log::info("Sending initial response.create to trigger greeting", [
            'chat_id' => $this->chatId,
            'modalities' => $responseConfig['response']['modalities']
        ]);

        $this->connection->push(json_encode($responseConfig));
    }

    private function handleMessages(): void
    {
        while ($this->isConnected) {
            try {
                // Handle ping
                if (time() - $this->lastPingTime >= $this->pingInterval) {
                    Log::debug("Sending ping to OpenAI", [
                        'chat_id' => $this->chatId
                    ]);
                    $this->connection->push('', WEBSOCKET_OPCODE_PING);
                    $this->lastPingTime = time();
                }

                $frame = $this->connection->recv(1.0);
                
                if ($frame === false) {
                    Log::error("OpenAI connection recv() failed", [
                        'chat_id' => $this->chatId,
                        'errCode' => $this->connection->errCode,
                        'errMsg' => $this->connection->errMsg
                    ]);
                    throw new \Exception("Connection recv() failed: " . $this->connection->errMsg);
                }
                
                if ($frame === '') {
                    continue;
                }

                // Handle ping/pong frames
                if (is_object($frame) && $frame instanceof \OpenSwoole\WebSocket\Frame) {
                    if ($frame->opcode === 9) { // Ping
                        $this->connection->push('', WEBSOCKET_OPCODE_PONG);
                        continue;
                    }
                    if ($frame->opcode === 10) { // Pong
                        continue;
                    }
                }

                // Handle close frame
                if (is_object($frame) && $frame instanceof \OpenSwoole\WebSocket\CloseFrame) {
                    throw new \Exception("OpenAI closed connection: " . ($frame->reason ?? 'No reason given'));
                }

                // Get frame data
                $data = is_object($frame) ? $frame->data : $frame;
                $message = json_decode($data, true);

                if (!$message) {
                    continue;
                }

                // Convert OpenAI messages to client format
                $clientMessage = null;

                switch ($message['type']) {
                    case 'response.text.delta':
                        if (isset($message['delta'])) {
                            $clientMessage = [
                                'type' => 'text',
                                'content' => $message['delta'],
                                'sender' => 'assistant'
                            ];
                        }
                        break;

                    case 'response.audio.delta':
                        if (isset($message['delta'])) {
                            $clientMessage = [
                                'type' => 'audio',
                                'data' => $message['delta']
                            ];
                        }
                        break;

                    case 'conversation.item.input_audio_transcription.completed':
                        if (isset($message['transcript'])) {
                            $clientMessage = [
                                'type' => 'text',
                                'content' => $message['transcript'],
                                'sender' => 'user'
                            ];
                        }
                        break;

                    case 'error':
                        $clientMessage = [
                            'type' => 'error',
                            'message' => $message['error']['message'] ?? 'Unknown error'
                        ];
                        break;
                }

                // Forward converted message to client
                if ($clientMessage && $this->server->exists($this->clientFd)) {
                    Log::debug("OpenAI -> Web Client: Converting and sending message", [
                        'chat_id' => $this->chatId,
                        'original_type' => $message['type'],
                        'converted' => $clientMessage
                    ]);
                    
                    $this->server->push($this->clientFd, json_encode($clientMessage));
                }

            } catch (\Exception $e) {
                Log::error("OpenAI message handling error", [
                    'chat_id' => $this->chatId,
                    'error' => $e->getMessage()
                ]);
                $this->handleDisconnect();
                break;
            }
        }
    }

    public function sendMessage(array $data): bool
    {
        try {
            if (!$this->isConnected) {
                throw new \Exception("Not connected to OpenAI");
            }

            Log::info("Web Client -> OpenAI: Received message", [
                'chat_id' => $this->chatId,
                'type' => $data['type']
            ]);

            // Handle text messages
            if ($data['type'] === 'text') {
                // Convert to OpenAI format
                $responseConfig = [
                    'type' => 'response.create',
                    'response' => [
                        'input' => [
                            [
                                'id' => uniqid('msg_', true),
                                'type' => 'message',
                                'role' => 'user',
                                'content' => [
                                    [
                                        'type' => 'input_text',
                                        'text' => $data['content']
                                    ]
                                ]
                            ]
                        ]
                    ]
                ];

                Log::info("Web Client -> OpenAI: Converting and sending text message", [
                    'chat_id' => $this->chatId,
                    'original' => $data,
                    'converted' => $responseConfig
                ]);

                return $this->connection->push(json_encode($responseConfig));
            }

            // Handle audio messages
            if ($data['type'] === 'audio') {
                // Convert to OpenAI format
                $audioMessage = [
                    'type' => 'input_audio_buffer.append',
                    'event_id' => uniqid('event_', true),
                    'audio' => $data['data']
                ];

                Log::info("Web Client -> OpenAI: Converting and sending audio message", [
                    'chat_id' => $this->chatId,
                    'event_id' => $audioMessage['event_id'],
                    'audio_length' => strlen($data['data'])
                ]);

                return $this->connection->push(json_encode($audioMessage));
            }

            throw new \Exception("Unsupported message type: " . $data['type']);

        } catch (\Exception $e) {
            Log::error("Failed to send message", [
                'chat_id' => $this->chatId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $data
            ]);
            return false;
        }
    }

    private function handleDisconnect(): void
    {
        $this->isConnected = false;
        if ($this->server->exists($this->clientFd)) {
            $this->server->push($this->clientFd, json_encode([
                'event' => 'error',
                'message' => 'OpenAI connection lost',
                'chat_id' => $this->chatId
            ]));
        }
    }

    public function isConnected(): bool
    {
        return $this->isConnected && $this->connection && $this->connection->connected;
    }

    public function disconnect(): void
    {
        $this->isConnected = false;
        if ($this->connection) {
            $this->connection->close();
        }
    }
} 