<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use React\EventLoop\Factory;
use Ratchet\Client\Connector;
use React\Socket\Connector as ReactConnector;
use Illuminate\Support\Facades\Log;
use App\Services\OpenAI\RealtimeMessageHandler;
use App\Models\Assistant;

class RichbotWebsocketRelay extends Command
{
    protected $signature = 'richbot:websocket-relay {chat_id} {assistant_id}';
    protected $description = 'Start a WebSocket relay between OpenAI and Richbot';

    public function handle()
    {
        $chatId = $this->argument('chat_id');
        $assistantId = $this->argument('assistant_id');
        $assistant = Assistant::find($assistantId);

        if (!$assistant) {
            Log::error("Richbot Relay: Assistant not found", ['assistant_id' => $assistantId]);
            return;
        }

        // Log assistant details
        Log::info("Richbot Relay: Assistant loaded", [
            'assistant_id' => $assistantId,
            'name' => $assistant->name,
            'tool_count' => $assistant->tools->count()
        ]);

        // Log each tool's configuration
        foreach ($assistant->tools as $tool) {
            Log::info("Tool configuration", [
                'name' => $tool->name,
                'description' => $tool->description ?: 'No description',
                'parameter_count' => $tool->toolParameters->count(),
                'parameters' => $tool->toolParameters->map(function($param) {
                    return [
                        'name' => $param->name,
                        'type' => $param->type,
                        'description' => $param->description,
                        'required' => $param->required
                    ];
                })
            ]);
        }

        $messageHandler = new RealtimeMessageHandler(null, null, null);
        $messageHandler->setAssistant($assistant);
        $initialConfig = $messageHandler->getInitialSessionConfig($assistant);

        //$initialConfigTxt = json_encode($initialConfig,true,256);
        //echo $initialConfigTxt;
        //exit;                                                               
        
        // Log the final configuration
        Log::info("Initial session configuration", [
            'event_id' => $initialConfig['event_id'],
            'tool_count' => count($initialConfig['session']['tools'] ?? []),
            'tools' => array_map(function($tool) {
                return [
                    'name' => $tool['function']['name'] ?? 'unnamed',
                    'parameter_count' => count($tool['function']['parameters']['properties'] ?? []),
                    'required_params' => $tool['function']['parameters']['required'] ?? []
                ];
            }, $initialConfig['session']['tools'] ?? [])
        ]);

        Log::info("Richbot Relay: Starting WebSocket relay " . $assistant->name, [
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
            ->then(function($richbotConn) use ($loop, $chatId, $assistantId, $connector, $assistant, $initialConfig, $messageHandler) {
                Log::info("Richbot Relay: Connected to Richbot WebSocket");

                $messageHandler = new RealtimeMessageHandler($richbotConn, null, $chatId);
                $messageHandler->setRichbotConnection($richbotConn);
                $messageHandler->setChatId($chatId);
                $messageHandler->setAssistant($assistant);
               
                // Connect to OpenAI
                $openaiUrl = "wss://api.openai.com/v1/realtime?model=gpt-4o-realtime-preview-2024-12-17";
                $openaiHeaders = [
                    'Authorization' => 'Bearer ' . config('services.openai.api_key'),
                    'OpenAI-Beta' => 'realtime=v1'
                ];

                Log::info("Richbot Relay: Connecting to OpenAI WebSocket");

                $connector($openaiUrl, [], $openaiHeaders)
                    ->then(function($openaiConn) use ($richbotConn, $chatId, $loop, $messageHandler, $initialConfig) {
                        Log::info("Richbot Relay: Connected to OpenAI WebSocket");

                        // Update the message handler with both connections
                        $messageHandler->setOpenAIConnection($openaiConn);

                        Log::info("Richbot Relay: Sending initial session configuration", ['config' => $initialConfig]);

                        // Send initial session configuration
                        $openaiConn->send(json_encode($initialConfig,JSON_PRETTY_PRINT,256));
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


//missing_required_parameter","message":"Missing required parameter: 'session.tools[0].name'.","param":"session.tools[0].name","event_id":"init_676ca0d4f264b"},"chat_id":"chat_676ca0d4aeda63.94430337"}
