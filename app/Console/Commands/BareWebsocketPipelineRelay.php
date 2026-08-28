<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use React\EventLoop\Factory;
use Ratchet\Client\Connector;
use React\Socket\Connector as ReactConnector;
use Illuminate\Support\Facades\Log;
use App\Models\Conversation;
use App\Models\Pipeline;
use App\Models\Stage;
use App\Models\Assistant;
use App\Services\ToolExecutor;
use App\Services\CodingExecutor;
use App\Services\Executors\SurveyExecutor;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use App\Services\Executors\RainbowExecutor;

class BareWebsocketPipelineRelay extends Command
{
    protected $signature = 'bare:pipeline {room} {conversation_id} {--debug} {--audio-output-pcm16} {--audio-output-g711_alaw} {--user_id=} {--richbot_token=} {--second-delay=}';
    protected $description = 'Start a WebSocket relay for pipeline conversations';

    // Pipeline specific properties
    private $conversation;
    private $pipeline;
    private $currentStage;
    private $currentAssistant;
    private $stageSuccessCalled = false;
    private $pipelineComplete = false;

    // Inherited from BareWebsocketRelay
    private $richbotConn;
    private $openaiConn;
    private $room;
    private $callSid;
    private $currentResponse = null;
    private $messageCount = 0;
    private $lastHeartbeat = null;
    private $startTime;
    private $isDebug = false;
    private $openaiMessageCount = 0;
    private $bareMessageCount = 0;
    private $lastMessageTime;
    private $outputFormat = 'g711_ulaw';
    private $userId;
    private $user;
    private $richbotToken;
    private $secondDelay;

    public function handle()
    {
        $this->startTime = time();
        $this->isDebug = $this->option('debug');
        $this->room = $this->argument('room');
        $conversationId = $this->argument('conversation_id');
        $this->userId = $this->option('user_id') ?? null;
        $this->richbotToken = $this->option('richbot_token') ?? null;
        $this->secondDelay = $this->option('second-delay') ?? 0;

        if ($this->richbotToken) {
            $this->info("Richbot Token: {$this->richbotToken}");
        }

        if($this->userId) {
            $user = User::find($this->userId);
            if($user) {
                $this->user = $user;
                $this->info("User: {$user->name}");
            }
        }

        if($this->secondDelay) {
            $this->info("Second Delay: {$this->secondDelay}");
            sleep($this->secondDelay);
        }

        // Load conversation and pipeline
        $this->conversation = Conversation::with(['pipeline', 'stage'])->findOrFail($conversationId);

        $this->pipeline = $this->conversation->pipeline;
        
        $this->currentStage = $this->conversation->stage;

        if (!$this->currentStage) {
            $this->error("No stage found for conversation");
            return;
        }

        // Get the first assistant for the current stage
        $this->currentAssistant = $this->conversation->assistant ?? $this->currentStage->assistants()->first();
        if (!$this->currentAssistant) {
            $this->error("No assistant found for stage");
            return;
        }

        $this->outputFormat = $this->option('audio-output-pcm16') ? 'pcm16' : 'g711_ulaw';
        $this->outputFormat = $this->option('audio-output-g711_alaw') ? 'g711_alaw' : 'g711_ulaw';


        Log::info('[PIPELINE RELAY] Starting Pipeline WebSocket Relay');
        Log::info('[PIPELINE RELAY] ==========================');
        Log::info('[PIPELINE RELAY] Room: ' . $this->room);
        Log::info('[PIPELINE RELAY] Conversation ID: ' . $conversationId);
        Log::info('[PIPELINE RELAY] Pipeline: ' . $this->pipeline->name);
        Log::info('[PIPELINE RELAY] Current Stage: ' . $this->currentStage->name);
        Log::info('[PIPELINE RELAY] Current Assistant: ' . $this->currentAssistant->name);
        Log::info('[PIPELINE RELAY] Debug Mode: ' . ($this->isDebug ? 'ON' : 'OFF'));
        Log::info('[PIPELINE RELAY] ==========================');

        
        $initialConfig = $this->getInitialSessionConfig();

        Log::info('[PIPELINE RELAY] Initial Config: ' . json_encode($initialConfig));

        $loop = Factory::create();
        $connector = new Connector($loop, new ReactConnector($loop, [
            'tls' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]));

        // Connect to Bare WebSocket Server
        $bareUrl = "wss://richbot9000.local:9502/openai-realtime/{$this->room}/{$this->currentAssistant->id}";
        $connector($bareUrl)
            ->then(function($bareConn) use ($loop, $connector, $initialConfig) {
                Log::info("[PIPELINE RELAY] Connected to Bare WebSocket", [
                    'room' => $this->room,
                    'assistant' => $this->currentAssistant->id
                ]);

                $this->richbotConn = $bareConn;
                
                // Join the specified room
                $this->richbotConn->send(json_encode([
                    'type' => 'join',
                    'room' => $this->room
                ]));
                      
                // Connect to OpenAI
                $openaiUrl = "wss://api.openai.com/v1/realtime?model=gpt-4o-mini-realtime-preview";
                $openaiHeaders = [
                    'Authorization' => 'Bearer ' . config('services.openai.api_key'),
                    'OpenAI-Beta' => 'realtime=v1'
                ];

                Log::info("[PIPELINE RELAY] Connecting to OpenAI WebSocket");

                $connector($openaiUrl, [], $openaiHeaders)
                    ->then(function($openaiConn) use ($loop, $initialConfig) {
                        Log::info("[PIPELINE RELAY] Connected to OpenAI WebSocket");

                        $this->openaiConn = $openaiConn;
                        $this->lastMessageTime = time();

                        // Add inactivity checker timer
                        $loop->addPeriodicTimer(2, function() {
                            $this->checkInactivity();
                        });

                        // Handle messages from Bare WebSocket Server
                        $this->richbotConn->on('message', function($msg) {
                            $this->handleBareMessage($msg);
                        });

                        // Handle messages from OpenAI
                        $this->openaiConn->on('message', function($msg) {
                            $this->handleOpenAIMessage($msg);
                        });

                        // Set up heartbeat
                        $loop->addPeriodicTimer(30, function() {
                            $this->sendHeartbeat();
                        });

                        // Handle connection closures
                        $this->richbotConn->on('close', function() use ($loop) {
                            $this->handleConnectionClose();
                            $loop->stop();
                        });

                        $this->openaiConn->on('close', function() use ($loop) {
                            $this->handleConnectionClose();
                            $loop->stop();
                        });
                        
                        // Send initial session configuration
                        Log::info("[PIPELINE RELAY] Sending initial session configuration", [
                            'config' => $initialConfig
                        ]);

                        $this->openaiConn->send(json_encode($initialConfig));
                        $this->openaiConn->send(json_encode(['type' => 'response.create']));

                    }, function($e) use ($loop) {
                        $this->handleConnectionError($e);
                        $loop->stop();
                    });

            }, function($e) use ($loop) {
                $this->handleConnectionError($e);
                $loop->stop();
            });

        $loop->run();
    }

    private function getInitialSessionConfig()
    {
        $outputFormat = $this->option('audio-output-pcm16') ? 'pcm16' : 'g711_ulaw';
        $inputFormat = $this->option('audio-output-pcm16') ? 'pcm16' : 'g711_ulaw';


        Log::info("[PIPELINE RELAY] Getting initial session config", [
            'stage_tools' => $this->currentStage->tools,
            'assistant_tools' => $this->currentAssistant->getRealtimeAssistantTools(),
         
        ]);

        // Combine collection of stage and assistant tools
        

        $stageTools = $this->currentStage->tools;
        $tools = $this->currentAssistant->getRealtimeAssistantTools();

        Log::info("[PIPELINE RELAY] Stage tools", [
            'tools' => $stageTools
        ]);

        Log::info("[PIPELINE RELAY] Assistant tools", [
            'tools' => $tools
        ]);


        $tools = array_merge(
            $this->currentStage->tools->toArray() ?? [],
            $this->currentAssistant->getRealtimeAssistantTools() ?? []
        );

        Log::info("[PIPELINE RELAY] Getting initial session config", [
            'room' => $this->room,
            'inputFormat' => $inputFormat,
            'outputFormat' => $outputFormat,
            'tools_count' => count($tools)
        ]);

        return [
            'type' => 'session.update',
            'event_id' => 'init_' . uniqid(),
            'session' => [
                'model' => 'gpt-4o-mini-realtime-preview-2024-12-17',
                'input_audio_format' => $inputFormat,
                'output_audio_format' => $outputFormat,
                'modalities' => ['audio','text'],
                'instructions' => $this->getSystemInstructions(),
                'temperature' => 0.8,
                'tools' => $tools,
                'tool_choice' => 'auto',
                'max_response_output_tokens' => 'inf',
                'turn_detection' => [
                    'type' => 'semantic_vad',
                    'eagerness' => 'low',
                    'create_response' => true
                ],
            ]
        ];
    }

    private function getSystemInstructions()
    {
        $instructions = [];
        
        // Add pipeline context
        $instructions[] = "You are part of the pipeline: {$this->pipeline->name}";
        $instructions[] = "Current stage: {$this->currentStage->name}";

        if($this->conversation->phone_tree_call_id) {
            Log::info("[PIPELINE RELAY] Phone tree call ID", [
                'phone_tree_call_id' => $this->conversation->phone_tree_call_id,
                'phone_tree_call' => $this->conversation->phoneTreeCall
            ]);
            $instructions[] = "You are part of the phone tree call from: {$this->conversation->phoneTreeCall->from_number} to: {$this->conversation->phoneTreeCall->to_number}";
        }
        
        // Add stage-specific instructions
        if ($this->currentStage->instructions) {

            Log::info("[PIPELINE RELAY] Stage instructions", [
                'instructions' => $this->currentStage->instructions
            ]);
            $instructions[] = $this->currentStage->instructions;
        }
        
        // Add assistant instructions
        if ($this->currentAssistant->system_message) {
            Log::info("[PIPELINE RELAY] Assistant instructions", [
                'instructions' => $this->currentAssistant->system_message
            ]);
            $instructions[] = $this->currentAssistant->system_message;
        }


        Log::info("[PIPELINE RELAY] Stage files", [
            'files' => $this->currentStage->files
        ]);

        foreach($this->currentStage->files as $file)
        {

            $fileContent = Storage::get($file->file_path);
            Log::info("[PIPELINE RELAY] Stage file", [
                'file' => $file,                
                'file_content' => $fileContent
            ]);

            ///storage/app/ 
            // rainbow_info/hours_and_locations.txt <- stored in db
            //get the file content

            Log::info("[PIPELINE RELAY] **************************************** File Directory", [
                'directory' => Storage::files('/storage/app/'),
                'root' => Storage::files('/rainbow_info/')
            ]);
            $fileContent = Storage::get($file->file_path);
            $instructions[] = "Include the following file: {$file->name}: {$fileContent}";
        }


        
        return implode("\n", $instructions);
    }

    private function handleBareMessage($msg)
    {
        try {
            $message = json_decode($msg, true);
            $this->bareMessageCount++;
            $this->lastMessageTime = time();

           // Log::info("[PIPELINE RELAY] Received message", [
          //      'type' => $message['type'] ?? 'unknown',
          //      'timestamp' => date('Y-m-d H:i:s.u'),
          //      'message_size' => strlen($msg),
          //      'has_media' => isset($message['media']) || isset($message['data']) || isset($message['delta']),
          //      'room' => $this->room
          //  ]);

            // Store message in conversation
            $this->conversation->addMessage('user', $message['message'] ?? '');

            switch ($message['type']) {
                case 'message':
                    if (isset($message['message'])) {
                        $this->forwardToOpenAI($message['message'], 'text');
                    }
                    break;

                case 'media_data':
                    if (isset($message['data'])) {
                        $this->forwardToOpenAI($message['data'], 'audio');
                    }
                    break;

                case 'input_audio_buffer.append':
                    if (isset($message['audio'])) {
                        $this->forwardToOpenAI($message['audio'], 'audio');
                    }
                    break;
            }
        } catch (\Exception $e) {
            Log::error("[PIPELINE RELAY] Error processing Bare message", [
                'error' => $e->getMessage(),
                'room' => $this->room
            ]);
        }
    }

    private function handleOpenAIMessage($msg)
    {
        try {
            $message = json_decode($msg->getPayload(), true);
            $this->messageCount++;
            $this->openaiMessageCount++;
            $this->lastMessageTime = time();

            Log::info("[PIPELINE RELAY] Received OpenAI message", [
                'type' => $message['type'] ?? 'unknown',
                'timestamp' => date('Y-m-d H:i:s.u')
            ]);

            $type = $message['type'] ?? '';

            switch ($type) {
                case 'response.audio.delta':
                    if (isset($message['delta'])) {
                        $this->forwardToBare($message, 'audio');
                    }
                    break;

                case 'response.output_item.done':
                    if (isset($message['item']) && $message['item']['type'] === 'function_call') {
                        $this->handleFunctionCall($message);
                    }
                    break;

                case 'response.text.delta':
                    if (isset($message['delta'])) {
                        $this->forwardToBare($message, 'text');
                    }
                    break;
            }

            // Store assistant message in conversation
            if (isset($message['text'])) {
                $this->conversation->addMessage('assistant', $message['text']);
            }

        } catch (\Exception $e) {
            Log::error("[PIPELINE RELAY] Error processing OpenAI message", [
                'error' => $e->getMessage(),
                'room' => $this->room
            ]);
        }
    }

    private function handleFunctionCall($message)
    {
        try {
            $callId = $message['item']['call_id'];
            $method_name = $message['item']['name'];
            $method_args = json_decode($message['item']['arguments'], true);

            Log::info("[PIPELINE RELAY] Handling function call", [
                'method' => $method_name,
                'call_id' => $callId
            ]);

            // Tool executors
            $executors = [
                new ToolExecutor($this->user),
                new CodingExecutor($this->user),
                new SurveyExecutor($this->user),
                new RainbowExecutor($this->user)
            ];

            $data = null;
            foreach ($executors as $executor) {
                if (method_exists($executor, $method_name)) {
                    $data = call_user_func([$executor, $method_name], $method_args);
                    break;
                }
            }

            // Check for stage success tool
            if ($this->currentStage->successTool && 
                $method_name === $this->currentStage->successTool->name) {
                $this->stageSuccessCalled = true;
                $this->moveToNextStage();
            }

            // Send function output back to OpenAI
            $functionOutput = [
                'type' => 'conversation.item.create',
                'item' => [
                    'type' => 'function_call_output',
                    'call_id' => $callId,
                    'output' => json_encode($data)
                ]
            ];

            $this->openaiConn->send(json_encode($functionOutput));
            $this->openaiConn->send(json_encode(['type' => 'response.create']));

        } catch (\Exception $e) {
            Log::error("[PIPELINE RELAY] Error executing function call", [
                'error' => $e->getMessage(),
                'method' => $method_name
            ]);
        }
    }

    private function moveToNextStage()
    {
        try {
            $nextStage = $this->currentStage->getNextStage();
            
            if ($nextStage) {
                $this->currentStage = $nextStage;
                $this->conversation->stage_id = $nextStage->id;
                $this->conversation->save();

                // Get the first assistant for the new stage
                $this->currentAssistant = $this->currentStage->assistants()->first();
                
                if ($this->currentAssistant) {
                    // Update the WebSocket connection with new assistant
                    $this->updateAssistantConnection();
                    
                    // Add system message about stage transition
                    $this->conversation->addMessage('system', 
                        "Transitioning to stage: {$this->currentStage->name}"
                    );

                    Log::info("[PIPELINE RELAY] Moved to next stage", [
                        'stage' => $this->currentStage->name,
                        'assistant' => $this->currentAssistant->name
                    ]);
                } else {
                    $this->pipelineComplete = true;
                    $this->conversation->addMessage('system', 
                        "Pipeline completed: {$this->pipeline->name}"
                    );
                }
            } else {
                $this->pipelineComplete = true;
                $this->conversation->addMessage('system', 
                    "Pipeline completed: {$this->pipeline->name}"
                );
            }
        } catch (\Exception $e) {
            Log::error("[PIPELINE RELAY] Error moving to next stage", [
                'error' => $e->getMessage()
            ]);
        }
    }

    private function updateAssistantConnection()
    {

        //send the new instructions to the assistant
        $this->openaiConn->send(json_encode(['type' => 'session.update', 'event_id' => 'init_' . uniqid(), 'session' => ['instructions' => $this->getSystemInstructions()]]));

        //$this->openaiConn->send(json_encode(['type' => 'response.create']));
        
    }

    private function checkInactivity()
    {
        $timeSinceLastMessage = time() - $this->lastMessageTime;
        if ($timeSinceLastMessage >= 60) {
            try {
                $this->openaiConn->send(json_encode(['type' => 'response.create']));
                Log::debug("[PIPELINE RELAY] Sent response.create due to inactivity", [
                    'seconds_inactive' => $timeSinceLastMessage
                ]);
            } catch (\Exception $e) {
                Log::error("[PIPELINE RELAY] Failed to send inactivity response.create", [
                    'error' => $e->getMessage()
                ]);
            }
            $this->lastMessageTime = time();
        }
    }

    private function sendHeartbeat()
    {
        try {
            $this->lastHeartbeat = time();
            Log::info("[PIPELINE RELAY] Sending heartbeat", [
                'room' => $this->room
            ]);
            
            $this->richbotConn->send(json_encode([
                'type' => 'ping',
                'time' => $this->lastHeartbeat
            ]));
            $this->openaiConn->send(json_encode(['type' => 'response.create']));
        } catch (\Exception $e) {
            Log::error("[PIPELINE RELAY] Heartbeat error", [
                'error' => $e->getMessage()
            ]);
        }
    }

    private function handleConnectionClose()
    {
        Log::error("[PIPELINE RELAY] Connection closed", [
            'room' => $this->room,
            'conversation_id' => $this->conversation->id
        ]);
        
        // Update conversation status
        $this->conversation->status = 'completed';
        $this->conversation->save();
    }

    private function handleConnectionError($e)
    {
        Log::error("[PIPELINE RELAY] Connection error", [
            'error' => $e->getMessage(),
            'room' => $this->room,
            'conversation_id' => $this->conversation->id
        ]);
    }

    private function forwardToOpenAI($message, $type = 'text')
    {
        try {
            if ($type === 'text') {
                Log::info("[PIPELINE RELAY] Forwarding text to OpenAI", [
                    'message_length' => strlen($message),
                    'room' => $this->room
                ]);
                
                $this->openaiConn->send(json_encode([
                    'type' => 'conversation.item.create',
                    'item' => [
                        'type' => 'text',
                        'text' => $message
                    ]
                ]));
                $this->openaiConn->send(json_encode(['type' => 'response.create']));
             
            } else if ($type === 'audio') {                
              //  Log::info("[PIPELINE RELAY] Forwarding audio to OpenAI", [
              //      'audio_length' => strlen($message),
              //      'room' => $this->room
              //  ]);
                
                $this->openaiConn->send(json_encode([
                    'type' => 'input_audio_buffer.append',
                    'audio' => $message,                    
                ]));
            } else {
                Log::error("[PIPELINE RELAY] Unknown message type", [
                    'type' => $type,
                    'room' => $this->room
                ]);
            }
          
        } catch (\Exception $e) {
            Log::error("[PIPELINE RELAY] Failed to forward message to OpenAI", [
                'error' => $e->getMessage(),
                'type' => $type,
                'room' => $this->room
            ]);
        }
    }

    private function forwardToBare($message, $type = 'text')
    {
        try {
            if ($type === 'audio') {
                Log::info("[PIPELINE RELAY] Forwarding audio to Bare", [
                    'delta_length' => strlen($message['delta'] ?? ''),
                    'room' => $this->room
                ]);
                
                $mediaMessage = json_encode([
                    'event' => 'media',
                    'streamSid' => false,
                    'media' => [
                        'payload' => $message['delta']
                    ]
                ]);
                
                $this->richbotConn->send($mediaMessage);
            } else {
                Log::info("[PIPELINE RELAY] Forwarding message to Bare", [
                    'message' => $message,
                    'room' => $this->room
                ]);
                
                $this->richbotConn->send(json_encode($message));
            }
        } catch (\Exception $e) {
            Log::error("[PIPELINE RELAY] Failed to forward message to Bare", [
                'error' => $e->getMessage(),
                'type' => $type,
                'room' => $this->room
            ]);
        }
    }
} 