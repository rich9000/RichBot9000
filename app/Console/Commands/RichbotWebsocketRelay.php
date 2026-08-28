<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use React\EventLoop\Factory;
use Ratchet\Client\Connector;
use React\Socket\Connector as ReactConnector;
use Illuminate\Support\Facades\Log;
use App\Services\OpenAI\RealtimeMessageHandler;
use App\Models\Assistant;
use App\Services\Logging\OpenAILogger;

class RichbotWebsocketRelay extends Command
{
    protected $signature = 'richbot:websocket-relay {chat_id} {assistant_id} {--client_type=webclient} {--stream_sid=null}';
    protected $description = 'Start a WebSocket relay between OpenAI and Richbot';

    // Add this property to track last message time
    private $lastMessageTime;

    public function handle()
    {
        $chatId = $this->argument('chat_id');
        $assistantId = $this->argument('assistant_id');
        $clientType = $this->option('client_type'); // Will default to 'webclient'
        $streamSid = $this->option('stream_sid');

        
        $assistant = Assistant::find($assistantId);

        if (!$assistant) {
            Log::error("Richbot Relay: Assistant not found", ['assistant_id' => $assistantId]);
            return;
        }

        // Log connection details including client type
        Log::info("Richbot Relay: Starting connection", [
            'chat_id' => $chatId,
            'assistant_id' => $assistantId,
            'client_type' => $clientType,
            'assistant_name' => $assistant->name,
            'stream_sid' => $streamSid
        ]);

        // Pass client type to message handler
        $messageHandler = new RealtimeMessageHandler(null, null, $chatId, $clientType);
        $messageHandler->setAssistant($assistant);
        $messageHandler->setStreamSid($streamSid);

        $initialConfig = $messageHandler->getInitialSessionConfig($assistant);

        $loop = Factory::create();
        $connector = new Connector($loop, new ReactConnector($loop, [
            'tls' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]));

        // Connect to Richbot
        $richbotUrl = "wss://".config('app.domain').":".config('app.ws_port')."/relay/{$chatId}/{$assistantId}";
        // Connect to Richbot
        //$richbotUrl = "wss://richbot9000.local:9501/relay/{$chatId}/{$assistantId}";
        $connector($richbotUrl)
            ->then(function($richbotConn) use ($loop, $chatId, $assistantId, $connector, $assistant, $initialConfig, $messageHandler, $clientType) {
                Log::info("Richbot Relay: Connected to Richbot WebSocket", [
                    'client_type' => $clientType,
                    'chat_id' => $chatId
                ]);

               // $messageHandler = new RealtimeMessageHandler($richbotConn, null, $chatId, $clientType);
                $messageHandler->setRichbotConnection($richbotConn);
                      
                // Connect to OpenAI
                $openaiUrl = "wss://api.openai.com/v1/realtime?model=gpt-4o-realtime-preview-2024-12-17";
                $openaiHeaders = [
                    'Authorization' => 'Bearer ' . config('services.openai.api_key'),
                    'OpenAI-Beta' => 'realtime=v1'
                ];

                Log::info("Richbot Relay: Connecting to OpenAI WebSocket");

                $connector($openaiUrl, [], $openaiHeaders)
                    ->then(function($openaiConn) use ($richbotConn, $chatId, $loop, $messageHandler, $initialConfig, $clientType) {
                        // Initialize last message time
                        $this->lastMessageTime = time();

                        // Add inactivity checker timer
                        $loop->addPeriodicTimer(2, function() use ($openaiConn) {
                            $timeSinceLastMessage = time() - $this->lastMessageTime;
                            if ($timeSinceLastMessage >= 10) {
                                try {
                                    $openaiConn->send(json_encode(['type' => 'response.create']));
                                    Log::debug("Sent response.create due to inactivity", [
                                        'seconds_inactive' => $timeSinceLastMessage
                                    ]);
                                } catch (\Exception $e) {
                                    Log::error("Failed to send inactivity response.create", [
                                        'error' => $e->getMessage()
                                    ]);
                                }
                                // Reset timer after sending
                                $this->lastMessageTime = time();
                            }
                        });

                        Log::info("Richbot Relay: Connected to OpenAI WebSocket");

                        // Update the message handler with both connections
                        $messageHandler->setOpenAIConnection($openaiConn);

                        Log::info("Richbot Relay: Sending initial session configuration");

                        
                        $richbotConn->on('message', function($msg) use ($openaiConn, $messageHandler, $clientType, $chatId) {
                            $this->lastMessageTime = time();
                            try {
                                $message = json_decode($msg, true);
                                
                                // Add detailed message structure logging
                                Log::debug("Raw message received", [
                                    'chat_id' => $chatId,
                                    'type' => $message['type'] ?? 'unknown',
                                    'data_structure' => json_encode($message),
                                    'client_type' => $clientType
                                ]);

                                if ($message && $message['type'] === 'audio') {
                                    Log::debug("Audio message details", [
                                        'chat_id' => $chatId,
                                        'data_type' => gettype($message['data']),
                                        'data_keys' => is_array($message['data']) ? array_keys($message['data']) : 'not_array',
                                        'data_preview' => substr(json_encode($message['data']), 0, 100) . '...'
                                    ]);
                                }

                                if (!$message) {
                                    Log::warning("Received invalid message format", [
                                        'chat_id' => $chatId,
                                        'raw_message' => $msg
                                    ]);
                                    return;
                                }

                                // Add specific handling for audio messages
                                if ($message['type'] === 'audio') {
                                    Log::debug("Processing audio message", [
                                        'chat_id' => $chatId,
                                        'data_type' => gettype($message['data']),
                                        'data_structure' => is_array($message['data']) ? array_keys($message['data']) : 'not_array',
                                        'data_length' => is_array($message['data']) && isset($message['data']['data']) ? 
                                            strlen($message['data']['data']) : 
                                            (is_string($message['data']) ? strlen($message['data']) : 0)
                                    ]);
                                }

                                $clientMessage = $messageHandler->createClientMessage(
                                    $message['type'] ?? '',
                                    $message['data'] ?? null
                                );
                                
                                // Log the created message before sending
                                Log::debug("Created client message", [
                                    'chat_id' => $chatId,
                                    'original_type' => $message['type'] ?? '',
                                    'created_type' => $clientMessage['type'] ?? 'none',
                                    'has_audio' => isset($clientMessage['audio'])
                                ]);

                                if ($clientMessage) {
                                    $openaiConn->send(json_encode($clientMessage));
                                }
                            } catch (\Exception $e) {
                                Log::error("Error processing relay message", [
                                    'error' => $e->getMessage(),
                                    'trace' => $e->getTraceAsString(),
                                    'original_message' => $msg,
                                    'chat_id' => $chatId
                                ]);
                            }
                        });

                        // Handle messages from OpenAI
                        $openaiConn->on('message', function($msg) use ($messageHandler, $chatId, $clientType) {
                            $this->lastMessageTime = time();
                            try {
                                $message = json_decode($msg->getPayload(), true);

                                // Log inbound message from OpenAI
                                OpenAILogger::inbound($message, [
                                    'chat_id' => $chatId,
                                    'client_type' => $clientType
                                ]);

                               
                                $messageHandler->handleServerEvent($message);

                            } catch (\Exception $e) {
                                Log::error("Error processing OpenAI message", [
                                    'error' => $e->getMessage(),
                                    'trace' => $e->getTraceAsString()
                                ]);
                            }
                        });

                        // Set up heartbeat
                        $loop->addPeriodicTimer(30, function() use ($richbotConn, $chatId) {
                            try {
                                $richbotConn->send(json_encode([
                                    'type' => 'heartbeat',
                                    'chat_id' => $chatId
                                ]));


                                $openaiConn->send(json_encode(['type' => 'response.create']));




                            } catch (\Exception $e) {
                                Log::error("Richbot Relay: Heartbeat error", [
                                    'error' => $e->getMessage()
                                ]);
                            }
                        });

                        // Handle connection closures
                        $richbotConn->on('close', function() use ($loop) {
                            Log::error("Richbot Relay: Richbot connection closed");
                            $loop->stop();
                        });

                        $openaiConn->on('close', function() use ($loop) {
                            Log::error("Richbot Relay: OpenAI connection closed");
                            $loop->stop();
                        });

                        Log::info("Richbot Relay -> Sending initial session configuration", [
                            'config' => $initialConfig,
                            'chat_id' => $chatId,
                            'client_type' => $clientType
                        ]);

                        // Send initial session configuration
                        $openaiConn->send(json_encode($initialConfig));
                        $openaiConn->send(json_encode(['type' => 'response.create']));

                        // Add verification log after sending
                        Log::info("Initial configuration sent to OpenAI", [
                            'chat_id' => $chatId,
                            'client_type' => $clientType
                        ]);

                    }, function($e) use ($loop) {
                        Log::error("Richbot Relay: Could not connect to OpenAI", [
                            'error' => $e->getMessage()
                        ]);
                        $loop->stop();
                    });

            }, function($e) use ($loop) {
                Log::error("Richbot Relay: Could not connect to Richbot", [
                    'error' => $e->getMessage()
                ]);
                $loop->stop();
            });

        $loop->run();
    }
} 


//missing_required_parameter","message":"Missing required parameter: 'session.tools[0].name'.","param":"session.tools[0].name","event_id":"init_676ca0d4f264b"},"chat_id":"chat_676ca0d4aeda63.94430337"}
