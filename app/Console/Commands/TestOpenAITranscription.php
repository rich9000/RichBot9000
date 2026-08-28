<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Ratchet\Client\Connector;
use React\EventLoop\Factory;
use React\Socket\Connector as ReactConnector;
use App\Models\AudioFile;

class TestOpenAITranscription extends Command
{
    protected $signature = 'test:openai-transcription {conversation_id?}';
    protected $description = 'Test OpenAI transcription with audio from a specific conversation. If no ID provided, shows list of recent conversations.';

    public function handle()
    {
        $this->info('Testing OpenAI Transcription with Conversation Audio');
        $this->info('==========================================');

        // Get conversation ID from argument
        $conversationId = $this->argument('conversation_id');
        
        if (!$conversationId) {
            $this->showRecentConversations();
            return 0;
        }

        // Get conversation
        $conversation = \App\Models\Conversation::find($conversationId);
        if (!$conversation) {
            $this->error("Conversation not found: {$conversationId}");
            return 1;
        }

        // Get audio index file
        $audioIndexFile = storage_path("app/bare_logs/{$conversationId}/audio_index.json");
        if (!file_exists($audioIndexFile)) {
            $this->error("No audio index found for conversation: {$conversationId}");
            return 1;
        }

        // Read audio index
        $audioIndex = json_decode(file_get_contents($audioIndexFile), true);
        if (empty($audioIndex['segments'])) {
            $this->error("No audio segments found for conversation: {$conversationId}");
            return 1;
        }

        $totalSegments = count($audioIndex['segments']);
        $this->info("Found {$totalSegments} audio segments");

        try {
            // Create transcription session
            $this->info('Creating transcription session...');
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.openai.api_key'),
                'Content-Type' => 'application/json',
                'OpenAI-Beta' => 'realtime=v1'
            ])->post('https://api.openai.com/v1/realtime/transcription_sessions', [
                'input_audio_format' => 'g711_ulaw',
                'input_audio_transcription' => [
                    'model' => 'gpt-4o-transcribe',
                    'language' => 'en',
                    'prompt' => ''
                ]
            ]);

            if (!$response->successful()) {
                $this->error('Failed to create transcription session');
                $this->error('Status: ' . $response->status());
                $this->error('Response: ' . $response->body());
                return 1;
            }

            $sessionData = $response->json();
            $this->info('Session created successfully!');
            $this->info('Session ID: ' . ($sessionData['id'] ?? 'unknown'));
            
            // Log the full response for debugging
            Log::info('OpenAI Transcription Session Created', [
                'response' => $sessionData
            ]);

            // Get the client secret for WebSocket authentication
            $clientSecret = $sessionData['client_secret']['value'] ?? null;
            if (!$clientSecret) {
                $this->error('No client secret found in response');
                return 1;
            }

            $this->info('Testing WebSocket connection...');
            
            // Create event loop
            $loop = Factory::create();
            $connector = new Connector($loop, new ReactConnector($loop, [
                'tls' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ]));


            $session_url_include = "session_id=" . $sessionData['id'];

            // Connect to WebSocket using client secret as bearer token
            $wsUrl = "wss://api.openai.com/v1/realtime?intent=transcription";
            $wsHeaders = [
                'Authorization' => 'Bearer ' . $clientSecret,
                'Content-Type' => 'application/json',
                'OpenAI-Beta' => 'realtime=v1'
            ];

            Log::info('Connecting to WebSocket...');
            Log::info('URL: ' . $wsUrl);

            $connector($wsUrl, [], $wsHeaders)
                ->then(function($conn) use ($loop, $audioIndex, $conversationId, $totalSegments) {
                    $this->info('WebSocket connection established!');
                    
                    // Send initial configuration
                    $configMessage = [
                        'type' => 'transcription_session.update',
                        'session' => [
                            'input_audio_format' => 'g711_ulaw',
                            'input_audio_transcription' => [
                                'model' => 'gpt-4o-transcribe',
                                'language' => 'en',
                                'prompt' => ''
                            ],
                            'turn_detection' => [
                                'type' => 'server_vad',
                                'threshold' => 0.5,
                                'prefix_padding_ms' => 300,
                                'silence_duration_ms' => 200
                            ]
                        ]
                    ];

                    // Set up message handler
                    $conn->on('message', function($msg) {
                        $data = json_decode($msg->getPayload(), true);
                        if (isset($data['type'])) {
                            switch ($data['type']) {
                                case 'transcript.delta':
                                    $this->info('Transcription Delta: ' . ($data['delta'] ?? ''));
                                    Log::info('Transcription Delta', [
                                        'delta' => $data['delta'] ?? '',
                                        'type' => $data['type'],
                                        'full_data' => $data
                                    ]);
                                    break;
                                case 'transcript.complete':
                                    $this->info('Complete transcription: ' . ($data['transcript'] ?? ''));
                                    $this->info('Transcription complete!');
                                    Log::info('Transcription Complete', [
                                        'transcript' => $data['transcript'] ?? '',
                                        'type' => $data['type'],
                                        'full_data' => $data
                                    ]);
                                    break;
                                case 'error':
                                    $this->error('Error from OpenAI: ' . ($data['error'] ?? 'Unknown error'));
                                    Log::error('OpenAI Error', [
                                        'error' => $data['error'] ?? 'Unknown error',
                                        'type' => $data['type'],
                                        'full_data' => $data
                                    ]);
                                    break;
                                default:
                                    $this->info('Received message type: ' . $data['type']);
                                    $this->info('Message content: ' . json_encode($data));
                                    Log::info('WebSocket Message', [
                                        'type' => $data['type'],
                                        'data' => $data
                                    ]);
                            }
                        } else {
                            $this->info('Received message: ' . json_encode($data));
                            Log::info('WebSocket Message (No Type)', [
                                'data' => $data
                            ]);
                        }
                    });

                    // Set up close handler
                    $conn->on('close', function() {
                       
                        Log::info('WebSocket Connection Closed');
                    });

                    // Add 120-second timer to end the command
                    $loop->addTimer(120, function() use ($loop, $conn) {
                       
                        Log::info('Test Timeout - 120 seconds elapsed');
                        $conn->close();
                        $loop->stop();
                    });

                    $conn->send(json_encode($configMessage));
                  
                    Log::info('Sent Initial Configuration', [
                        'config' => $configMessage
                    ]);

                    sleep(1);

                    // Stream each audio segment
                    foreach ($audioIndex['segments'] as $index => $segment) {
                        $segmentFile = storage_path("app/bare_logs/{$conversationId}/" . $segment['file']);
                        if (!file_exists($segmentFile)) {
                          
                            Log::warning('Segment File Not Found', [
                                'file' => $segment['file'],
                                'index' => $index
                            ]);
                            continue;
                        }

                        $segmentData = file_get_contents($segmentFile);
                        if ($segmentData === false) {
                            $this->warn("Failed to read segment: {$segment['file']}");
                            Log::warning('Failed to Read Segment', [
                                'file' => $segment['file'],
                                'index' => $index
                            ]);
                            continue;
                        }

                        $this->info("Sending segment " . ($index + 1) . "/{$totalSegments}: {$segment['file']} ({$segment['size']} bytes)");
                        Log::info('Sending Audio Segment', [
                            'segment_number' => $index + 1,
                            'total_segments' => $totalSegments,
                            'file' => $segment['file'],
                            'size' => $segment['size']
                        ]);
                        
                        $message = [
                            'type' => 'input_audio_buffer.append',
                            'event_id' => uniqid('event_'),
                            'audio' => base64_encode($segmentData)
                        ];

                        $conn->send(json_encode($message));
                        Log::info('Sent Audio Segment', [
                            'event_id' => $message['event_id'],
                            'segment_number' => $index + 1
                        ]);
                        
                        // Small delay between segments to simulate real-time streaming
                        usleep(100000); // 100ms delay
                    }

                    $this->info('All audio segments sent');
                    Log::info('All Audio Segments Sent');

                }, function($e) use ($loop) {
                    $this->error('Could not connect to WebSocket: ' . $e->getMessage());
                    $loop->stop();
                });

            // Run the event loop
            $loop->run();
            
            $this->info('Test completed successfully!');
            return 0;

        } catch (\Exception $e) {
            $this->error('Error during test: ' . $e->getMessage());
            Log::error('OpenAI Transcription Test Failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
    }

    private function showRecentConversations()
    {
        $this->info('Recent Conversations:');
        $this->info('----------------------------------------');

        // Get the 10 most recent conversations
        $conversations = \App\Models\Conversation::orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        if ($conversations->isEmpty()) {
            $this->error('No conversations found');
            return;
        }

        $this->table(
            ['ID', 'Created', 'Path State', 'Has Audio Files'],
            $conversations->map(function ($conversation) {
                $conversationDir = storage_path("app/bare_logs/{$conversation->id}");
                $hasAudioFiles = false;
                
                if (file_exists($conversationDir)) {
                    $files = scandir($conversationDir);
                    foreach ($files as $file) {
                        if ($file !== '.' && $file !== '..' && 
                            !str_ends_with($file, '.json') && 
                            !str_ends_with($file, '.log')) {
                            $hasAudioFiles = true;
                            break;
                        }
                    }
                }
                
                return [
                    $conversation->id,
                    $conversation->created_at->format('Y-m-d H:i:s'),
                    $conversation->path_state ? 'Yes' : 'No',
                    $hasAudioFiles ? 'Yes' : 'No'
                ];
            })
        );

        $this->info("\nTo transcribe a conversation, run:");
        $this->info('php artisan test:openai-transcription <conversation_id>');
    }
} 