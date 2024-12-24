<?php
namespace App\Console\Commands;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\Command;
use Illuminate\Console\Command as ConsoleCommand;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use React\EventLoop\Factory;
use Ratchet\Client\Connector;
use React\Socket\Connector as ReactConnector;

class RealtimeTest extends ConsoleCommand
{


    protected $signature = 'realtime:test';

    protected $description = 'Command description';

    public function handle()
    {


        $key = env('OPENAI_API_KEY');

        $conversation = false;
        echo "$key\n";
        $loop = Factory::create();

// Path to the WAV file
        $wavFilePath = '/var/www/html/richbot9000.com/storage/app/remote_richbot/combined_audio.wav';

// WebSocket connection details
        $url = "wss://api.openai.com/v1/realtime?model=gpt-4o-realtime-preview-2024-10-01";
        $key = getenv('OPENAI_API_KEY');
        $headers = [
            'Authorization' => 'Bearer ' . $key,
            'OpenAI-Beta' => 'realtime=v1',
        ];

        $truncate = fopen($wavFilePath, 'w');
        if ($truncate) {
            fclose($truncate);
            echo "Truncated the WAV file.\n";
        } else {
            echo "Failed to truncate the WAV file.\n";
        }


        $chunkSize = 16000 * 2 * 4;  // 4 seconds of 16kHz, 16-bit mono audio

        $connector = new Connector($loop, new ReactConnector($loop));

        // Initialize variables to store assistant responses
        $assistantResponse = '';
        $assistantAudioData = '';
        $fileIndex = 0;


        $connector($url, [], $headers)
            ->then(function ($conn) use ($loop, $wavFilePath, $chunkSize) {
                echo "Connected to WebSocket server.\n";





                /*
                // Prepare and send the initial request
                $request = [
                    'type' => 'response.create',
                    'response' => [
                        'modalities' => ['text'],
                        'instructions' => 'Please assist the user.',
                    ],
                ];
                $conn->send(json_encode($request));

                */



                $loop->addPeriodicTimer(5, function () use ($conn, $wavFilePath, $chunkSize) {
                    // Open the WAV file for reading
                    if (file_exists($wavFilePath)) {
                        // Read the audio data from the WAV file
                        $audioData = file_get_contents($wavFilePath);
                        // Truncate the WAV file
                        $truncate = fopen($wavFilePath, 'w');
                        if ($truncate) {
                            fclose($truncate);
                            echo "Truncated the WAV file.\n";
                        } else {
                            echo "Failed to truncate the WAV file.\n";
                        }

                        if ($audioData !== false && !empty($audioData)) {
                            // Encode the chunk to base64
                            $base64Audio = base64_encode($audioData);

                            // Create and send the 'input_audio_buffer.append' event
                            $audioEvent = [
                                'event_id' => 'event_' . uniqid(),
                                'type' => 'input_audio_buffer.append',
                                'audio' => $base64Audio,
                            ];

                            $conn->send(json_encode($audioEvent));
                            echo "Sent audio chunk to server.\n";
                        }
                    } else {
                        echo "Failed to open WAV file.\n";
                    }
                });

                $conn->on('message', function ($msg) use (&$assistantResponse, &$assistantAudioData, &$fileIndex, &$conversation) {
                    // Decode the incoming message
                    $message = json_decode($msg, true);

                    // Check if the message is valid JSON
                    if (!$message) {
                        echo "Invalid JSON message received: $msg\n";
                        return;
                    }

                    // Handle different message types
                    switch ($message['type'] ?? '') {
                        case 'response.text.delta':
                            // Append delta to assistant response
                            $delta = $message['delta'] ?? '';
                            $assistantResponse .= $delta;
                            break;

                        case 'response.text.done':
                            // Assistant's text response is completed
                            // Create a Message record
                            $messageRecord = Message::create([
                                'conversation_id' => $conversation->id,
                                'role' => 'assistant',
                                'content' => $assistantResponse,
                                'audio_file' => null,
                            ]);

                            // Create a Command for the remote richbot to speak or play
                            $command = Command::create([
                                'richbot_id' => 1,
                                'user_id' => null,  // Set the user ID if needed
                                'remote_richbot_id' => 1,  // Assuming 1
                                'command' => 'speak_text',
                                'parameters' => json_encode(['text' => $assistantResponse]),
                                'status' => 'pending',
                            ]);

                            // Reset the assistant response
                            $assistantResponse = '';
                            break;

                        case 'response.audio.delta':
                            // Process base64-encoded audio delta
                            $base64Audio = $message['delta'] ?? '';
                            if (!empty($base64Audio)) {
                                // Decode the base64 audio data and append
                                $audioData = base64_decode($base64Audio);
                                if ($audioData !== false) {
                                    $assistantAudioData .= $audioData;
                                } else {
                                    echo "Failed to decode base64 audio data.\n";
                                }
                            }
                            break;

                        case 'response.audio.done':
                            // Handle completion of audio response
                            echo "Audio response completed.\n";

                            // Save the audio data to a file
                            $audioFileName = 'assistant_response_' . time() . '.wav';
                            $audioFilePath = storage_path('app/public/remote_richbot/' . $audioFileName);
                            $audioUrl = url('storage/remote_richbot/' . $audioFileName);

                            if (!empty($assistantAudioData)) {
                                $result = file_put_contents($audioFilePath, $assistantAudioData);
                                if ($result !== false) {
                                    echo "Saved assistant audio response to $audioFilePath\n";

                                    // Create a Message record
                                    $messageRecord = Message::create([
                                        'conversation_id' => $conversation->id,
                                        'role' => 'assistant',
                                        'content' => 'audio',
                                        'audio_file' => $audioFileName,
                                    ]);

                                    // Create a Command for the remote richbot to play the audio
                                    $command = Command::create([
                                        'richbot_id' => 1,
                                        'user_id' => null,  // Set the user ID if needed
                                        'remote_richbot_id' => 1,
                                        'command' => 'play_url',
                                        'parameters' => json_encode(['url' => $audioUrl]),
                                        'status' => 'pending',
                                    ]);
                                } else {
                                    echo "Failed to save assistant audio response to $audioFilePath\n";
                                }

                                // Reset the assistant audio data
                                $assistantAudioData = '';
                            } else {
                                echo "No audio data to save.\n";
                            }
                            break;

                        case 'response.audio_transcript.done':
                            // Handle completion of audio transcription
                            $transcript = $message['transcript'] ?? '';
                            echo "Transcript completed: $transcript\n";

                            // Create a Message record for user's message
                            $messageRecord = Message::create([
                                'conversation_id' => $conversation->id,
                                'role' => 'user',
                                'content' => $transcript,
                                'audio_file' => null,
                            ]);

                            break;

                        case 'response.content_part.done':
                            // Handle a completed content part
                            $part = $message['part'] ?? [];
                            if ($part['type'] === 'audio') {
                                $transcript = $part['transcript'] ?? '';
                                echo "Audio content part done. Transcript: $transcript\n";

                                // Create a Message record for user's message
                                $messageRecord = Message::create([
                                    'conversation_id' => $conversation->id,
                                    'role' => 'user',
                                    'content' => $transcript,
                                    'audio_file' => null,
                                ]);
                            }
                            break;

                        case 'response.done':
                            // Handle response completion
                            echo "Response completed.\n";
                            break;

                        case 'input_audio_buffer.speech_started':
                            // Handle speech start event
                            echo "Speech started at " . ($message['audio_start_ms'] ?? 'unknown') . " ms.\n";
                            break;

                        case 'input_audio_buffer.speech_stopped':
                            // Handle speech stop event
                            echo "Speech stopped at " . ($message['audio_end_ms'] ?? 'unknown') . " ms.\n";
                            break;

                        default:
                            // Log unhandled message types
                            echo "Unhandled message type: {$message['type']}\n";
                            dump($message);
                            break;
                    }

                });

                // Handle connection closure
                $conn->on('close', function ($code = null, $reason = null) use ($loop) {
                    echo "Connection closed ({$code} - {$reason})\n";
                    $loop->stop();
                });
            }, function (\Exception $e) use ($loop) {
                echo "Could not connect: {$e->getMessage()}\n";
                $loop->stop();
            });

        $loop->run();
    }
}
