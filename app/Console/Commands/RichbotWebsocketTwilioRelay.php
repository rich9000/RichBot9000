<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use React\EventLoop\Factory;
use Ratchet\Client\Connector;
use React\Socket\Connector as ReactConnector;
use Illuminate\Support\Facades\Log;
use App\Models\Assistant;
use App\Services\Logging\OpenAILogger;

class RichbotWebsocketTwilioRelay extends Command
{
    protected $signature = 'richbot:websocket-twilio-relay {chat_id} {stream_sid} {assistant_id}';
    protected $description = 'Start a WebSocket relay between OpenAI and Twilio Media Streams';

    private $richbotConn;
    private $openaiConn;
    private $chatId;
    private $assistant;
    private $currentResponse = null;

    public function handle()
    {
        $this->chatId = $this->argument('chat_id');
        $this->streamSid = $this->argument('stream_sid');
        $assistantId = $this->argument('assistant_id');

        $this->assistant = Assistant::find($assistantId);

        if (!$this->assistant) {
            Log::error("Richbot Twilio Relay: Assistant not found", ['assistant_id' => $assistantId]);
            return;
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
        $richbotUrl = "wss://richbot9000.com:9501/relay/{$this->chatId}/{$assistantId}";
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

                        Log::info("Richbot Twilio Relay: Sending initial session configuration");

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
                        $this->openaiConn->send(json_encode($initialConfig));
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

    private function handleTwilioMessage($msg)
    {
        try 
        {
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
            
            // Convert μ-law to PCM16 and upsample to 24kHz
            $pcm16Data = $this->convertAudioForOpenAI($ulawData);
            
          

                // Twilio sends mulaw/8000 audio
                $audio_append = [
                    'type' => 'input_audio_buffer.append',
                    'audio' => base64_encode($pcm16Data),
                    
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
        if (!isset($message['delta'])) {
            return;
        }

        try {
            // Decode base64 PCM from OpenAI
            $pcmData = base64_decode($message['delta']);
            
            // Convert PCM16 to μ-law with downsample from 24kHz to 8kHz
            $ulawData = '';
            $samples = unpack('s*', $pcmData);
            $sampleCount = count($samples);
            
            // Take every third sample (24kHz -> 8kHz)
            for ($i = 1; $i <= $sampleCount; $i += 3) {
                $sample = $samples[$i];
                $ulawData .= chr($this->linearToMulaw($sample));
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
                'input_audio_sample_rate' => 8000, // Twilio's sample rate
                'output_audio_sample_rate' => 8000, // Twilio's sample rate
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
            
            // Convert μ-law to PCM16 and upsample to 24kHz
            $pcm16Data = $this->convertAudioForOpenAI($ulawData);
            
            // Send to OpenAI
            $openaiMessage = [
                'event' => 'message',
                'type' => 'audio',
                'data' => [
                    'audio' => base64_encode($pcm16Data),
                    'format' => 'pcm16',
                    'sampleRate' => 24000
                ]
            ];
            
            $this->openaiConn->send(json_encode($openaiMessage));
            
        } catch (\Exception $e) {
            Log::error("Error processing audio message", [
                'error' => $e->getMessage()
            ]);
        }
    }

    private function convertAudioForOpenAI($ulawData)
    {
        // Convert μ-law to PCM16
        $pcm16 = '';
        for ($i = 0; $i < strlen($ulawData); $i++) {
            $sample = $this->mulawToLinear(ord($ulawData[$i]));
            $pcm16 .= pack('s', $sample); // 's' packs as signed 16-bit
        }
        
        // Upsample from 8kHz to 24kHz using linear interpolation
        return $this->upsampleAudio($pcm16, 8000, 24000);
    }

    private function mulawToLinear($ulawByte)
    {
        $BIAS = 0x84;
        $exp_lut = [0, 132, 396, 924, 1980, 4092, 8316, 16764];
        
        $ulawByte = ~$ulawByte;
        $sign = ($ulawByte & 0x80) ? -1 : 1;
        $exponent = ($ulawByte >> 4) & 0x07;
        $mantissa = $ulawByte & 0x0F;
        $sample = $exp_lut[$exponent] + ($mantissa << ($exponent + 3));
        
        return $sign * ($sample - $BIAS);
    }

    private function upsampleAudio($pcmData, $fromRate, $toRate)
    {
        $samples = unpack('s*', $pcmData); // Unpack as 16-bit signed integers
        $ratio = $toRate / $fromRate;
        $result = '';
        
        // Linear interpolation
        for ($i = 0; $i < count($samples) * $ratio; $i++) {
            $pos = $i / $ratio;
            $index1 = floor($pos);
            $index2 = min(ceil($pos), count($samples));
            $fraction = $pos - $index1;
            
            $sample1 = $samples[$index1 + 1] ?? 0; // +1 because unpack indexes start at 1
            $sample2 = $samples[$index2 + 1] ?? $sample1;
            
            $interpolated = (int)($sample1 * (1 - $fraction) + $sample2 * $fraction);
            $result .= pack('s', $interpolated);
        }
        
        return $result;
    }
} 