<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use OpenSwoole\WebSocket\Server;
use OpenSwoole\WebSocket\Frame;
use OpenSwoole\Constant;
use OpenSwoole\Table;

class RealtimeWebsocketOld extends Command
{
    protected $signature = 'realtime2:websocket2';
    protected $description = 'Start WebSocket server for OpenAI realtime chat';

    private Table $clientsTable;
    private array $openAiConnections = [];

    public function __construct()
    {
        parent::__construct();
        
        // Initialize clients table
        $this->clientsTable = new Table(1024);
        $this->clientsTable->column('openAiFd', Table::TYPE_INT);
        $this->clientsTable->column('sessionId', Table::TYPE_STRING, 64);
        $this->clientsTable->column('status', Table::TYPE_STRING, 32);
        $this->clientsTable->column('instructions', Table::TYPE_STRING, 1024);
        $this->clientsTable->column('openAiStatus', Table::TYPE_STRING, 32);
        $this->clientsTable->create();
    }

    public function handle()
    {
        $this->info("Starting OpenAI Realtime WebSocket Server...");
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
            $this->info("[SERVER] OpenAI Realtime WebSocket server started on wss://richbot9000.com:9501");
        });

        $server->on('handshake', function($request, $response) {
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
            $this->info("[CONNECT] New client connection (fd: {$fd})");
        });

        $server->on(Constant::EVENT_MESSAGE, function($server, Frame $frame) {
            try {
                $data = json_decode($frame->data, true);
                
                // Log incoming frame
                if (!$data) {
                    $this->error("[ERROR] Invalid JSON received");
                    return;
                }

                // Log differently based on message type
                if (isset($data['type']) && $data['type'] === 'audio') {
                    $this->logInfo("=== INCOMING FRAME ===", [
                        'fd' => $frame->fd,
                        'type' => 'audio',
                        'length' => strlen($data['audio'] ?? ''),
                        'event' => $data['event'] ?? 'unknown'
                    ]);
                } else {
                    $this->logInfo("=== INCOMING FRAME ===", [
                        'fd' => $frame->fd,
                        'data' => $data
                    ]);
                }
                
                // Process the message based on type
                if (!isset($data['event'])) {
                    throw new \Exception("Invalid message format - missing event");
                }

                switch ($data['event']) {
                    case 'message':
                        if ($data['type'] === 'audio') {
                            $this->handleAudioMessage($server, $frame->fd, $data);
                        } else {
                            $this->handleTextMessage($server, $frame->fd, $data);
                        }
                        break;

                    case 'start':
                        echo "[START] Processing start event from client\n";
                        $this->handleStart($server, $frame->fd, $data);
                        break;

                    default:
                        echo "[EVENT] Unknown event type: " . $data['event'] . "\n";
                        break;
                }

            } catch (\Exception $e) {
                $this->error("[ERROR] Message handling error: " . $e->getMessage());
            }
        });

        $server->on(Constant::EVENT_CLOSE, function($server, $fd) {
            $this->cleanupClient($fd);
        });
    }

    private function handleStart($server, $fd, $data)
    {
        try {
            $sessionId = $data['sessionId'] ?? uniqid();
            $instructions = $data['instructions'] ?? 'You are a helpful AI assistant.';
            $voice = $data['voice'] ?? 'alloy';

            // Store initial client information
            $this->clientsTable->set($fd, [
                'sessionId' => $sessionId,
                'status' => 'active',
                'instructions' => $instructions,
                'openAiStatus' => 'connecting'
            ]);

            // Notify client that connection is being established
            $server->push($fd, json_encode([
                'event' => 'status_update',
                'status' => 'connecting',
                'message' => 'Establishing OpenAI connection...'
            ]));

            // Create OpenAI WebSocket connection
            $success = $this->createOpenAIWebSocketClient($server, $fd, $sessionId, $instructions, $voice);
            
            if (!$success) {
                $server->push($fd, json_encode([
                    'event' => 'error',
                    'message' => 'Failed to establish OpenAI connection'
                ]));
            }
        } catch (\Exception $e) {
            $this->error("[START] Error: {$e->getMessage()}");
            $server->push($fd, json_encode([
                'event' => 'error',
                'message' => 'Failed to start session'
            ]));
        }
    }

    private function handleAudioMessage($server, $fd, $data)
    {
        go(function() use ($server, $fd, $data) {
            try {
                $clientData = $this->clientsTable->get($fd);
                if (!$clientData || !isset($data['audio'])) {
                    return;
                }

                $sessionId = $clientData['sessionId'];
                $connection = $this->openAiConnections[$sessionId]['connection'] ?? null;

                if (!$connection || !$connection->connected) {
                    return;
                }

                // Rate limit check
                $now = microtime(true);
                $lastSend = $this->clientsTable->get($fd)['lastAudioSend'] ?? 0;
                if ($now - $lastSend < 0.1) { // 100ms minimum interval
                    return;
                }

                // Forward audio
                $audioMessage = [
                    'type' => 'input_audio_buffer.append',
                    'event_id' => uniqid('audio_'),
                    'audio' => $data['audio']
                ];
                
                $connection->push(json_encode($audioMessage));
                $this->clientsTable->set($fd, ['lastAudioSend' => $now]);

                // Log less frequently
                if (rand(1, 100) <= 1) { // 1% chance to log
                    $this->logInfo("[AUDIO] Audio forwarded", [
                        'sessionId' => $sessionId,
                        'size' => strlen($data['audio'])
                    ]);
                }

            } catch (\Exception $e) {
                $this->error("[AUDIO] Error: " . $e->getMessage());
            }
        });
    }

    private function handleTextMessage($server, $fd, $data)
    {
        $this->info("[TEXT] Processing text message");
        try {
            $clientData = $this->clientsTable->get($fd);
            if (!$clientData) {
                $this->error("[TEXT] No client data found");
                return;
            }

            $sessionId = $clientData['sessionId'];
            $connection = $this->openAiConnections[$sessionId]['connection'] ?? null;

            if (!$connection) {
                $this->error("[TEXT] OpenAI connection not ready");
                return;
            }

            // First create a new response
            $createResponse = [
                'type' => 'response.create',
                'response' => [
                    'modalities' => ['text', 'audio']
                ]
            ];
            $connection->push(json_encode($createResponse));

            // Then send the text content
            $textMessage = [
                'type' => 'input_content.append',
                'content' => [
                    'type' => 'text',
                    'text' => $data['message']
                ]
            ];
            $connection->push(json_encode($textMessage));

            $this->info("[TEXT] Message sent to OpenAI");
        } catch (\Exception $e) {
            $this->error("[TEXT] Error: " . $e->getMessage());
        }
    }

    private function handleStop($server, $fd, $data)
    {
        $this->cleanupClient($fd);
    }

    private function cleanupClient($fd)
    {
        try {
            $clientData = $this->clientsTable->get($fd);
            if ($clientData) {
                $sessionId = $clientData['sessionId'];
                
                if (isset($this->openAiConnections[$sessionId])) {
                    $connection = $this->openAiConnections[$sessionId]['connection'];
                    if ($connection) {
                        $connection->close();
                    }
                    
                    $loop = $this->openAiConnections[$sessionId]['loop'];
                    if ($loop) {
                        $loop->stop();
                    }

                    unset($this->openAiConnections[$sessionId]);
                }

                $this->clientsTable->del($fd);
            }
        } catch (\Exception $e) {
            $this->error("[CLEANUP] Error: {$e->getMessage()}");
        }
    }

    private function createOpenAIWebSocketClient($server, $fd, $sessionId, $instructions, $voice)
    {
        try {
            $apiKey = env('OPENAI_API_KEY');
            $this->info("[OPENAI] Starting connection process for session {$sessionId}");
            $this->info("[OPENAI] API Key present: " . (!empty($apiKey) ? 'Yes' : 'No'));
            
            // Update client status
            $this->clientsTable->set($fd, [
                'openAiStatus' => 'connecting',
                'sessionId' => $sessionId,
                'instructions' => $instructions
            ]);

            // Notify client
            $server->push($fd, json_encode([
                'event' => 'status_update',
                'status' => 'connecting',
                'message' => 'Establishing OpenAI connection...'
            ]));

            // Create OpenAI WebSocket connection using Swoole coroutine
            go(function() use ($server, $fd, $sessionId, $instructions, $voice, $apiKey) {
                $openAiClient = new \Swoole\Coroutine\Http\Client(
                    'api.openai.com',
                    443,
                    true
                );

                // Set headers before upgrade
                $openAiClient->setHeaders([
                    'Authorization' => 'Bearer ' . $apiKey,
                    'OpenAI-Beta' => 'realtime=v1',
                    'Content-Type' => 'application/json'
                ]);

                $this->info("[OPENAI] Headers being sent:");
                foreach ($openAiClient->requestHeaders as $key => $value) {
                    $this->info("  {$key}: " . ($key === 'authorization' ? 'Bearer ' . substr($value, 7, 5) . '...' : $value));
                }

                $this->info("[OPENAI] Attempting upgrade to WebSocket...");
                $success = $openAiClient->upgrade('/v1/realtime?model=gpt-4o-realtime-preview-2024-10-01');

                if (!$success) {
                    $this->error("[OPENAI] Connection failed for session {$sessionId}");
                    $this->error("[OPENAI] Error Code: " . $openAiClient->statusCode);
                    $this->error("[OPENAI] Error Body: " . $openAiClient->body);
                    $this->error("[OPENAI] Headers sent: " . json_encode($openAiClient->requestHeaders));
                    
                    $server->push($fd, json_encode([
                        'event' => 'error',
                        'message' => 'Failed to connect to OpenAI: ' . $openAiClient->statusCode . ' - ' . $openAiClient->body
                    ]));
                    return;
                }

                $this->info("[OPENAI] Connection established for session {$sessionId}");
                
                // Store connection in connections table
                $this->openAiConnections[$sessionId] = [
                    'connection' => $openAiClient,
                    'status' => 'connected'
                ];

                // Send initial configuration
                $this->sendOpenAIConfig($openAiClient, $instructions, $voice);

                // Start message relay coroutines
                $this->startMessageRelays($server, $openAiClient, $fd, $sessionId);
            });

            return true;
        } catch (\Exception $e) {
            $this->error("[OPENAI] Setup error: " . $e->getMessage());
            return false;
        }
    }

    private function startMessageRelays($server, $openAiClient, $fd, $sessionId)
    {
        go(function() use ($server, $openAiClient, $fd, $sessionId) {
            while ($openAiClient->connected) {
                $frame = $openAiClient->recv();
                if ($frame && $frame->data) {
                    try {
                        $data = json_decode($frame->data, true);
                        $this->info("[OPENAI->CLIENT] Forwarding message:");
                        $this->info("  Type: " . ($data['type'] ?? 'unknown'));
                        $this->info("  To Client FD: " . $fd);
                        $this->info("  Session: " . $sessionId);
                        
                        $success = $server->push($fd, json_encode([
                            'event' => 'openai_message',
                            'data' => $data
                        ]));
                        
                        $this->info("  Push Success: " . ($success ? 'Yes' : 'No'));
                        
                    } catch (\Exception $e) {
                        $this->error("[RELAY] Error: " . $e->getMessage());
                    }
                }
            }
            
            $this->error("[OPENAI] Connection closed for session {$sessionId} - attempting reconnect");
            // Attempt to reconnect
            $this->createOpenAIWebSocketClient($server, $fd, $sessionId, 
                $this->clientsTable->get($fd)['instructions'] ?? '',
                'alloy'
            );
        });
    }

    private function sendOpenAIConfig($openAiClient, $instructions, $voice)
    {
        // Send session configuration
        $sessionConfig = [
            'type' => 'session.update',
            'session' => [
                'modalities' => ['text', 'audio'],
                'instructions' => $instructions,
                'voice' => $voice,
                'input_audio_format' => 'pcm16',
                'output_audio_format' => 'pcm16',
                'input_audio_transcription' => [
                    'model' => 'whisper-1'
                ],
                'turn_detection' => [
                    'type' => 'server_vad',
                    'threshold' => 0.5,
                    'prefix_padding_ms' => 300,
                    'silence_duration_ms' => 500
                ],
                'temperature' => 0.7,
                'max_response_output_tokens' => 150
            ]
        ];

        $openAiClient->push(json_encode($sessionConfig));

        // Create initial response
        $createResponse = [
            'type' => 'response.create',
            'response' => [
                'modalities' => ['text', 'audio'],
                'instructions' => $instructions,
                'voice' => $voice,
                'temperature' => 0.7,
                'max_output_tokens' => 150
            ]
        ];

        $openAiClient->push(json_encode($createResponse));
    }

    private function handleStatusCheck($server, $fd, $data)
    {
        try {
            if ($data['target'] === 'openai') {
                $sessionId = $this->clientsTable->get($fd)['sessionId'] ?? null;
                if (!$sessionId) {
                    $server->push($fd, json_encode([
                        'event' => 'status_response',
                        'target' => 'openai',
                        'status' => 'error',
                        'message' => 'No session found'
                    ]));
                    return;
                }

                $connection = $this->openAiConnections[$sessionId]['connection'] ?? null;
                $status = [
                    'event' => 'status_response',
                    'target' => 'openai',
                    'status' => $connection ? 'connected' : 'disconnected',
                    'sessionId' => $sessionId,
                    'details' => [
                        'hasConnection' => !empty($connection),
                        'hasLoop' => !empty($this->openAiConnections[$sessionId]['loop']),
                        'clientTableEntry' => $this->clientsTable->get($fd)
                    ]
                ];

                $this->info("[STATUS] OpenAI connection status: " . json_encode($status));
                $server->push($fd, json_encode($status));
            }
        } catch (\Exception $e) {
            $this->error("[STATUS] Error checking status: " . $e->getMessage());
            $server->push($fd, json_encode([
                'event' => 'status_response',
                'target' => 'openai',
                'status' => 'error',
                'message' => $e->getMessage()
            ]));
        }
    }

    // Add this helper method to safely log data
    private function logInfo($message, $data = [])
    {
        $logMessage = $message;
        if (!empty($data)) {
            $logMessage .= ' ' . json_encode($data);
        }
        $this->info($logMessage);
    }
} 