<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use React\EventLoop\Factory;
use Ratchet\Client\Connector;
use React\Socket\Connector as ReactConnector;
use Illuminate\Support\Facades\Log;
use App\Models\Assistant;
use App\Services\Logging\OpenAILogger;
use App\Services\OpenAI\RealtimeMessageHandler;
use App\Services\ToolExecutor;
use App\Services\CodingExecutor;
use App\Models\Conversation;
use App\Models\Message; 
use App\Models\User;
//use App\Services\AudioRecorder;


class RichbotWebsocketTwilioRelay extends Command
{
    protected $signature = 'richbot:websocket-twilio-relay {chat_id} {stream_sid} {assistant_id}';
    protected $description = 'Start a WebSocket relay between OpenAI and Twilio Media Streams';

    private $richbotConn;
    private $openaiConn;
    private $chatId;
    private $assistant;
    private $conversation;
    private $currentResponse = null;
    private $audioRecorder;

    public function handle()
    {
        $this->chatId = $this->argument('chat_id');
        $this->streamSid = $this->argument('stream_sid');
        $assistantId = $this->argument('assistant_id');

        //$this->audioRecorder = new AudioRecorder($this->chatId,$this->streamSid);

        $this->assistant = Assistant::find($assistantId);

        if (!$this->assistant) {
            Log::error("Richbot Twilio Relay: Assistant not found", ['assistant_id' => $assistantId]);
            return;
        }

        $this->conversation = Conversation::where('id',$this->chatId)->first();

        if($this->conversation->system_message){
            $this->assistant->system_message .= "\n\n".$this->conversation->system_message;
        }

        // Log connection details
        Log::info("Richbot Twilio Relay: Starting connection", [
            'chat_id' => $this->chatId,
            'stream_sid' => $this->streamSid,
            'assistant_id' => $assistantId,
            'assistant_name' => $this->assistant->name
        ]);

        $initialConfig = $this->getInitialSessionConfig();

    

        

        $loop = Factory::create();
        $connector = new Connector($loop, new ReactConnector($loop, [
            'tls' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]));

        // Connect to Richbot
        $richbotUrl = "wss://".config('app.domain').":".config('app.ws_port')."/relay/{$this->chatId}/{$assistantId}";
        $connector($richbotUrl)
            ->then(function($richbotConn) use ($loop, $connector, $initialConfig) {

                Log::info("Richbot Twilio Relay: Connected to Richbot WebSocket", [
                    'chat_id' => $this->chatId,
                    'stream_sid' => $this->streamSid,
                    'assistant' => $this->assistant->id
                ]);

                $this->richbotConn = $richbotConn;
                      
                // Connect to OpenAI
                $openaiUrl = "wss://api.openai.com/v1/realtime?model=gpt-4o-realtime-preview-2024-12-17";
                $openaiHeaders = [
                    'Authorization' => 'Bearer ' . config('services.openai.api_key'),
                    'OpenAI-Beta' => 'realtime=v1'
                ];

                Log::info("Richbot Twilio Relay: Connecting to OpenAI WebSocket");

                $connector($openaiUrl, [], $openaiHeaders)
                    ->then(function($openaiConn) use ($loop, $initialConfig) {

                        
                        Log::info("Richbot Twilio Relay: Connected to OpenAI WebSocket");

                        $this->openaiConn = $openaiConn;

                        // Handle messages from Richbot (which will be Twilio media stream messages)
                        $this->richbotConn->on('message', function($msg) {
                            $this->handleTwilioMessage($msg);
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
                            Log::error("Richbot Twilio Relay: Richbot connection closed");
                            $loop->stop();
                        });

                        $this->openaiConn->on('close', function() use ($loop) {
                            Log::error("Richbot Twilio Relay: OpenAI connection closed");
                            $loop->stop();
                        });
                        
                        // Send initial session configuration
                        Log::info("Richbot Twilio Relay-> Sending initial session configuration".json_encode($initialConfig,JSON_PRETTY_PRINT,256));

                        $this->openaiConn->send(json_encode($initialConfig));

                        Log::info("Richbot Twilio Relay-> Sending response.create");

                        $this->openaiConn->send(json_encode(['type' => 'response.create']));


                    }, function($e) use ($loop) {
                        Log::error("Richbot Twilio Relay: Could not connect to OpenAI", [
                            'error' => $e->getMessage()
                        ]);
                        $loop->stop();
                    });

            }, function($e) use ($loop) {
                Log::error("Richbot Twilio Relay: Could not connect to Richbot", [
                    'error' => $e->getMessage()
                ]);
                $loop->stop();
            });

        $loop->run();
    }
    private function handleFunction($message)
    {

        Log::channel('openai_tools')->info("Richbot Twilio Relay: Handling function call", [
            'message' => json_encode($message,JSON_PRETTY_PRINT,256)
        ]);

        $message = $message['item'];



        $callId = $message['call_id'];
        $method_name = $message['name'];
        $method_args = json_decode($message['arguments'], true);
        
        // Add tool usage logging
        Log::channel('openai_tools')->info("Tool called", [
            'tool' => $method_name,
            'arguments' => $method_args,
            'call_id' => $callId,
    
        ]);

     
        $data = null;
        $optional_objects = [

            
            new ToolExecutor(),
            new CodingExecutor()

        ];

        try {
            // First check if method exists in this class
            if (method_exists($this, $method_name)) {
                Log::channel('openai_tools')->info('Executing method TwilioRelay:', [
                    'method' => $method_name,
                    'args' => $method_args
                ]);
                $data = call_user_func([$this, $method_name], $method_args);
            } else {
                // Loop through optional objects
                foreach ($optional_objects as $index => $object) {
                    $class_name = get_class($object);
                    if (method_exists($object, $method_name)) {
                        Log::channel('openai_tools')->info("Executing method on {$class_name}:", [
                            'method' => $method_name,
                            'args' => $method_args
                        ]);
                        $data = call_user_func([$object, $method_name], $method_args);
                        Log::channel('openai_tools')->info("Function call results", [
                            'results' => $data
                        ]);
                        break;
                    }
                }
            }

            if ($data === null) {
                Log::channel('openai_tools')->error("No handler found for method", [
                    'method' => $method_name,
                    'checked_classes' => array_merge(
                        [get_class($this)],
                        array_map(function($obj) { return get_class($obj); }, $optional_objects)
                    )
                ]);
            }

       //     Message::create([
      //          'conversation_id' => $this->streamSid,
      //          'role' => 'tool',
      //          'content' => json_encode($data)
      //      ]);

            // Send function output to OpenAI
            $functionOutput = [
                'type' => 'conversation.item.create',
                'item' => [
                    'type' => 'function_call_output',
                    'call_id' => $callId,
                    'output' => json_encode($data)
                ]
            ];

            Log::channel('openai_tools')->info("Sending function output to OpenAI", [
                'output' => $functionOutput,
                'call_id' => $callId
            ]);

            $this->openaiConn->send(json_encode($functionOutput));
            $this->openaiConn->send(json_encode(['type'=>'response.create']));


        } catch (\Exception $e) {
            Log::channel('openai_tools')->error("Error executing function call", [
                'error' => $e->getMessage(),
                'method' => $method_name,
                'arguments' => $method_args,
                'trace' => $e->getTraceAsString()
            ]);

        }

        unset($this->functionCallBuffer[$callId]);
    }

    private function handleTwilioMessage($msg)
    {
        try {
            $message = json_decode($msg, true);
            
            Log::debug("Twilio Relay received message", [
                'stream_sid' => $this->streamSid,
                'raw_message' => $msg,
                'decoded_message' => $message
            ]);

           

            if($message && isset($message['streamSid'])){

                if($message['streamSid'] !== $this->streamSid){
                    Log::error("Richbot Twilio Relay: Received message with incorrect streamSid", [
                        'expected_stream_sid' => $this->streamSid,
                        'received_stream_sid' => $message['streamSid']
                    ]);
                    $this->streamSid = $message['streamSid'];

                }
            }

            

            // Handle Twilio media messages
            if ($message && isset($message['media']) && isset($message['media']['payload'])) {
                // Decode base64 μ-law audio from Twilio
                $ulawData = base64_decode($message['media']['payload']);
                
                // Save the original μ-law data for input
                $this->saveAudioChunk($ulawData, 'input');  // Save the raw μ-law data
                
                // Convert μ-law to PCM16 and upsample to 24kHz for OpenAI
                $pcm16Data = $this->convertAudioForOpenAI($ulawData);

                // Send to OpenAI
                $audio_append = [
                    'type' => 'input_audio_buffer.append',
                    'audio' => base64_encode($pcm16Data),
                   // 'audio' => $message['media']['payload'],
                ];
                $this->openaiConn->send(json_encode($audio_append));
            }
        

        } catch (\Exception $e) {
            Log::error("Error processing Twilio relay message", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'original_message' => $msg
            ]);
        }
    }

    private function handleOpenAIMessage($msg)
    {
        try {
            $message = json_decode($msg->getPayload(), true);

            // Log inbound message from OpenAI
            OpenAILogger::inbound($message, [
                'stream_sid' => $this->streamSid,
                'client_type' => 'twilio_phone'
            ]);

            $type = $message['type'] ?? '';

            switch ($type) {
                case 'response.audio.delta':
                    $this->handleAudioDelta($message);
                    break;
                case 'response.text.delta':
                    $this->handleTextDelta($message);
                    break;
                case 'error':
                    $this->handleError($message);
                    break;
                case 'response.created':
                    $this->currentResponse = $message['response']['id'];
                    break;
                case 'session.created':
                    
                    Log::info("Richbot Twilio Relay: Session created", [
                        
                        'session_config' => json_encode($message['session'],JSON_PRETTY_PRINT,256)
                    ]);


                    break;
                case 'response.output_item.done':
                    Log::info("Richbot Twilio Relay: Output item done", [
                        'response_id' => $message['response_id'],
                        'output_index' => $message['output_index'],
                        'item' => $message['item']
                    ]);
                    
                    // Check for function calls in the item
                    if (isset($message['item']) && $message['item']['type'] === 'function_call') {

                        $data = $this->handleFunction($message);


                        $functionCall = [
                            'type' => 'function_call',
                            'call_id' => $message['item']['call_id'],
                            'name' => $message['item']['name'],
                            'arguments' => $message['item']['arguments'],
                            'results' => $data,
                        ];

                        Log::info("Richbot Twilio Relay: Forwarding function call to Richbot", [
                            'function_call' => json_encode($functionCall,JSON_PRETTY_PRINT,256)
                        ]);
                        
                        // Forward function call to Richbot connection
                        $this->richbotConn->send(json_encode($functionCall));
                    }
                    break;
                case 'response.done':
                    $this->currentResponse = null;
                    break;
                default:
                    Log::debug("Unhandled OpenAI message type", [
                        'type' => $type,
                        'stream_sid' => $this->streamSid
                    ]);
            }

        } catch (\Exception $e) {
            Log::error("Error processing OpenAI message", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    private function handleAudioDelta($message)
    {
        if (!isset($message['delta'])) return;

        try {
            // Decode base64 PCM from OpenAI
            $pcmData = base64_decode($message['delta']);


       //     $this->audioRecorder->saveAudioChunk($pcmData, 'output', 'pcm16');
            
            // Save OpenAI's response audio
            $this->saveAudioChunk($pcmData, 'output');
            
            // Convert and send to Twilio as before...
            $ulawData = '';
            $samples = unpack('s*', $pcmData);
            $sampleCount = count($samples);
            
            for ($i = 1; $i <= $sampleCount; $i += 3) {
                $ulawData .= chr($this->linearToMulaw($samples[$i]));
            }
            
            $twilioMessage = [
                'event' => 'media',
                'streamSid' => $this->streamSid,
                'media' => [
                    'payload' => base64_encode($ulawData)
                ]
            ];
            
            $this->richbotConn->send(json_encode($twilioMessage));
            
        } catch (\Exception $e) {
            Log::error("Error sending audio to Twilio", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    private function linearToMulaw($sample) 
    {
        $BIAS = 0x84;
        $CLIP = 32635;
        
        // Get the sign and magnitude
        $sign = ($sample < 0) ? 0x80 : 0;
        if ($sign) {
            $sample = -$sample;
        }
        
        // Clip sample to max value
        $sample = min($sample, $CLIP);
        
        // Convert linear to μ-law
        $sample += $BIAS;
        
        $exponent = 7;
        for ($i = 0x4000; $i > 0; $i >>= 1) {
            if ($sample >= $i) {
                break;
            }
            $exponent--;
        }
        
        $mantissa = ($sample >> ($exponent + 3)) & 0x0F;
        $ulawByte = ~($sign | ($exponent << 4) | $mantissa);
        
        return $ulawByte;
    }

    private function handleTextDelta($message)
    {
        Log::debug("Text delta received", [
            'response_id' => $message['response_id'],
            'delta_length' => strlen($message['delta'] ?? '')
        ]);
    }

    private function handleError($message)
    {
        Log::error("OpenAI Error", [
            'error' => $message['error'],
            'stream_sid' => $this->streamSid
        ]);
    }

    private function sendHeartbeat()
    {
        try {
            $this->richbotConn->send(json_encode([
                'type' => 'heartbeat',
                'stream_sid' => $this->streamSid
            ]));
        } catch (\Exception $e) {
            Log::error("Richbot Twilio Relay: Heartbeat error", [
                'error' => $e->getMessage()
            ]);
        }
    }

    private function getInitialSessionConfig()
    {
        return [
            'type' => 'session.update',
            'event_id' => 'init_' . uniqid(),
            'session' => [
                'modalities' => ['text', 'audio'],
                'instructions' => $this->assistant->system_message ?? '',
                'voice' => 'sage',
                'input_audio_format' => 'pcm16',  // Tell OpenAI we're sending mulaw
                'output_audio_format' => 'pcm16', // Request mulaw output
                //'input_audio_sample_rate' => 8000, // Twilio's sample rate
                //'output_audio_sample_rate' => 8000, // Twilio's sample rate
                'input_audio_transcription' => [
                    'model' => 'whisper-1'
                ],
                'turn_detection' => [
                    'type' => 'server_vad',
                    'threshold' => 0.5,
                    'prefix_padding_ms' => 300,
                    'silence_duration_ms' => 500,
                    'create_response' => true
                ],
                'tools' => $this->assistant->getRealtimeAssistantTools() ?? [],
                'tool_choice' => 'auto',
                'temperature' => 0.8,
                'max_response_output_tokens' => 'inf'
            ]
        ];
    }

    private function handleAudioMessage($message)
    {
        try {
            // Decode base64 μ-law audio from Twilio
            $ulawData = base64_decode($message['media']['payload']);
            
            // Save the raw audio
            $this->saveAudioChunk($ulawData, 'input');
            
            // Convert and send to OpenAI as before...
            $pcm16Data = $this->convertAudioForOpenAI($ulawData);
            
            $openaiMessage = [
                'event' => 'message',
                'type' => 'audio',
                'data' => [
                    'audio' => base64_encode($pcm16Data),
                    'format' => 'g711_ulaw'             
                ]
            ];

            /*
  
            $openaiMessage = [
                'event' => 'message',
                'type' => 'audio',
                'data' => [
                    'audio' => base64_encode($pcm16Data),
                    'format' => 'pcm16',
                    'sampleRate' => 24000
                ]
            ];
            */
            
            $this->openaiConn->send(json_encode($openaiMessage));
            
        } catch (\Exception $e) {
            Log::error("Error processing audio message", ['error' => $e->getMessage()]);
        }
    }

    private function convertAudioForOpenAI($ulawData)
    {
        // Create μ-law to linear lookup table
        static $ulaw2linear = null;
        if ($ulaw2linear === null) {
            $ulaw2linear = array_fill(0, 256, 0);
            
            // Standard μ-law to linear conversion table
            for ($i = 0; $i < 256; $i++) {
                $ulawByte = ~$i; // Invert bits
                
                $sign = ($ulawByte & 0x80) ? -1 : 1;
                $exponent = ($ulawByte >> 4) & 0x07;
                $mantissa = ($ulawByte & 0x0F);
                
                // Proper scaling for 16-bit PCM
                if ($exponent == 0) {
                    $sample = (($mantissa << 3) + 132) * $sign;
                } else {
                    $sample = (($mantissa << ($exponent + 3)) + (132 << $exponent)) * $sign;
                }
                
                // Scale to full 16-bit range
                $sample *= 8;
                
                // Ensure we're in 16-bit range
                $sample = max(-32768, min(32767, $sample));
                
                $ulaw2linear[$i] = $sample;
            }
        }

        // Convert using lookup table
        $pcm16 = '';
        for ($i = 0; $i < strlen($ulawData); $i++) {
            $ulawByte = ord($ulawData[$i]);
            $sample = $ulaw2linear[$ulawByte];
            $pcm16 .= pack('s', $sample);
        }
        
        // Upsample from 8kHz to 24kHz using improved interpolation
        return $this->upsampleAudio($pcm16, 8000, 24000);
    }

    private function upsampleAudio($pcmData, $fromRate, $toRate)
    {
        $samples = unpack('s*', $pcmData);
        $ratio = $toRate / $fromRate;
        $result = '';
        $sampleCount = count($samples);
        
        // Moving average window for pre-filtering
        $windowSize = 4;
        $filtered = [];
        for ($i = 1; $i <= $sampleCount; $i++) {
            $sum = 0;
            $count = 0;
            for ($j = max(1, $i - $windowSize); $j <= min($sampleCount, $i + $windowSize); $j++) {
                $sum += $samples[$j];
                $count++;
            }
            $filtered[$i] = (int)($sum / $count);
        }
        
        // Improved upsampling with linear interpolation and post-filtering
        for ($i = 0; $i < $sampleCount * $ratio; $i++) {
            $pos = $i / $ratio;
            $index1 = floor($pos);
            $index2 = min(ceil($pos), $sampleCount - 1);
            $fraction = $pos - $index1;
            
            $sample1 = $filtered[$index1 + 1] ?? 0;
            $sample2 = $filtered[$index2 + 1] ?? $sample1;
            
            // Linear interpolation
            $interpolated = (int)($sample1 * (1 - $fraction) + $sample2 * $fraction);
            
            // Ensure the sample is within 16-bit range
            $interpolated = max(-32768, min(32767, $interpolated));
            
            $result .= pack('s', $interpolated);
        }
        
        return $result;
    }

    private function saveAudioChunk($audioData, $direction)
    {
        try {
            $timestamp = date('Y-m-d_H-i-s');
            $chatId = $this->chatId;
            $streamSid = $this->streamSid;
            
            // Create directory structure if it doesn't exist
            $directory = storage_path("app/audio_recordings/{$streamSid}/{$direction}");
            if (!file_exists($directory)) {
                mkdir($directory, 0755, true);
            }

            // For input (from Twilio), data is μ-law
            // For output (from OpenAI), data is PCM16
            // Save with appropriate format indicator in filename
            $format = ($direction === 'input') ? 'ulaw' : 'pcm16';
            $sampleRate = ($direction === 'input') ? '8000' : '24000';
            
            $filename = "{$directory}/{$timestamp}_{$format}_{$sampleRate}_" . uniqid() . ".raw";
            
            // Save the raw audio data
            if (file_put_contents($filename, $audioData) === false) {
                Log::error("Failed to save audio chunk", [
                    'direction' => $direction,
                    'filename' => $filename,
                    'size' => strlen($audioData)
                ]);
                return;
            }

            Log::debug("Saved audio chunk", [
                'direction' => $direction,
                'filename' => $filename,
                'format' => $format,
                'sample_rate' => $sampleRate,
                'size' => strlen($audioData)
            ]);
            
        } catch (\Exception $e) {
            Log::error("Error saving audio chunk", [
                'error' => $e->getMessage(),
                'direction' => $direction,
                'trace' => $e->getTraceAsString()
            ]);
        }
    }


    
    



} 