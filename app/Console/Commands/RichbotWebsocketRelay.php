<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use React\EventLoop\Factory;
use Ratchet\Client\Connector;
use React\Socket\Connector as ReactConnector;
use Illuminate\Support\Facades\Log;
use App\Services\OpenAI\RealtimeMessageHandler;

class RichbotWebsocketRelay extends Command
{
    protected $signature = 'richbot:websocket-relay {chat_id} {assistant_id}';
    protected $description = 'Start a WebSocket relay between OpenAI and Richbot';

    public function handle()
    {
        $chatId = $this->argument('chat_id');
        $assistantId = $this->argument('assistant_id');

        Log::info("Richbot Relay: Starting WebSocket relay", [
            'chat_id' => $chatId,
            'assistant_id' => $assistantId
        ]);

        $loop = Factory::create();
        $connector = new Connector($loop, new ReactConnector($loop, [
            'tls' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]));

        // Connect to Richbot
        $richbotUrl = "wss://richbot9000.local:9501/relay/{$chatId}/{$assistantId}";
        $connector($richbotUrl)
            ->then(function($richbotConn) use ($loop, $chatId, $assistantId, $connector) {
                Log::info("Richbot Relay: Connected to Richbot WebSocket");

                // Create message handler first
                $messageHandler = new RealtimeMessageHandler($richbotConn, null, $chatId);
               
                // Connect to OpenAI
                $openaiUrl = "wss://api.openai.com/v1/realtime?model=gpt-4o-realtime-preview-2024-12-17";
                $openaiHeaders = [
                    'Authorization' => 'Bearer ' . config('services.openai.api_key'),
                    'OpenAI-Beta' => 'realtime=v1'
                ];

                $connector($openaiUrl, [], $openaiHeaders)
                    ->then(function($openaiConn) use ($richbotConn, $chatId, $loop, $messageHandler) {
                        Log::info("Richbot Relay: Connected to OpenAI WebSocket");

                        // Set the OpenAI connection in the message handler
                        $messageHandler->setOpenAIConnection($openaiConn);

                        // Send initial session configuration
                        $openaiConn->send(json_encode($messageHandler->getInitialSessionConfig()));
                        $openaiConn->send(json_encode(['type' => 'response.create']));

                        $richbotConn->on('message', function($msg) use ($openaiConn, $messageHandler) {
                            try {
                                $message = json_decode($msg, true);
                                $clientMessage = $messageHandler->createClientMessage(
                                    $message['type'] ?? '',
                                    $message['data'] ?? null
                                );
                                
                                if ($clientMessage) {
                                    $openaiConn->send(json_encode($clientMessage));
                                }
                            } catch (\Exception $e) {
                                Log::error("Richbot Relay: Error processing Richbot message", [
                                    'error' => $e->getMessage()
                                ]);
                            }
                        });

                        // Handle messages from OpenAI to Richbot
                        $openaiConn->on('message', function($msg) use ($messageHandler) {
                            try {
                                $msg = json_decode($msg->getPayload(), true);

                                if($msg['type'] === "response.audio.delta"){
                                    Log::info("OpenAI audio delta", [
                                        'response_id' => $msg['response_id'],
                                        'size' => strlen($msg['delta'] ?? '')
                                    ]);
                                } else {
                                    Log::info("OpenAI message", [
                                        'type' => $msg['type'],
                                        'event_id' => $msg['event_id'] ?? null,
                                        'response_id' => $msg['response_id'] ?? null,
                                        'status' => $msg['response']['status'] ?? null
                                    ]);
                                }

                                $messageHandler->handleServerEvent($msg);
                            } catch (\Exception $e) {
                                Log::error("Richbot Relay: Error processing OpenAI message", [
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