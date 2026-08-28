<?php

namespace App\WebSocket\Messages;

use App\WebSocket\Messages\Interfaces\MessageHandlerInterface;
use Swoole\WebSocket\Server;
use Illuminate\Support\Facades\Log;

class MediaMessageHandler implements MessageHandlerInterface
{
    private $tables;

    public function __construct(array $tables = [])
    {
        $this->tables = $tables;
    }

    public function handle(Server $server, int $fd, array $message): void
    {
        $client = null;
        if (!empty($this->tables) && isset($this->tables['clients'])) {
            $client = $this->tables['clients']->get($fd);
        }
        
        if (!$client && !empty($this->tables)) {
            Log::error("[MEDIA] Unknown client", ['fd' => $fd]);
            return;
        }

        // Handle Twilio media format
        if (isset($message['media']['payload']) && isset($message['streamSid'])) {
            $this->handleTwilioMedia($server, $fd, $message, $client);
            return;
        }

        // Handle standard media format
        if (!isset($message['data'])) {
            Log::error("[MEDIA] Missing media data", ['fd' => $fd]);
            return;
        }

        // Broadcast media to room
        $server->push($fd, json_encode([
            'type' => 'media',
            'data' => $message['data'],
            'format' => $message['format'] ?? 'g711_ulaw'
        ]));

        Log::info("[MEDIA] Media message handled", [
            'fd' => $fd,
            'format' => $message['format'] ?? 'g711_ulaw',
            'data_size' => strlen($message['data'])
        ]);
    }

    private function handleTwilioMedia(Server $server, int $fd, array $message, array $client): void
    {
        $room = $client['room'] ?? null;
        if (!$room) {
            Log::error("[MEDIA] No room found for Twilio client", ['fd' => $fd]);
            return;
        }

        $streamSid = $message['streamSid'];
        $payload = $message['media']['payload'];

        // Only log every 10th media message to avoid spam
        static $logCounter = 0;
        $logCounter++;
        if ($logCounter % 10 === 1) {
            Log::info("[MEDIA] Handling Twilio media", [
                'fd' => $fd,
                'room' => $room,
                'stream_sid' => $streamSid,
                'payload_size' => strlen($payload),
                'count' => $logCounter
            ]);
        }

        // First, try to route to other Twilio clients in the same room
        $twilioClientFound = false;
        foreach ($this->tables['clients'] as $clientFd => $clientInfo) {
            if ($clientFd != $fd && 
                $clientInfo['type'] === 'twilio' && 
                $clientInfo['room'] === $room) {
                
                $mediaMessage = [
                    'event' => 'media',
                    'streamSid' => $streamSid,
                    'media' => [
                        'payload' => $payload
                    ]
                ];
                
                if ($clientFd > 0 && $server->isEstablished($clientFd)) {
                    try {
                        $server->push($clientFd, json_encode($mediaMessage));
                        $twilioClientFound = true;
                        Log::info("[MEDIA] Routed to Twilio client", [
                            'from_fd' => $fd,
                            'to_fd' => $clientFd,
                            'room' => $room
                        ]);
                    } catch (\Exception $e) {
                        Log::warning("[MEDIA] Failed to push to Twilio client", [
                            'client_fd' => $clientFd,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
        }
        
        // If no Twilio clients found, route to OpenAI client
        if (!$twilioClientFound) {
            foreach ($this->tables['clients'] as $clientFd => $clientInfo) {
                if ($clientInfo['type'] === 'openai' && $clientInfo['room'] === $room) {
                    $mediaMessage = [
                        'type' => 'input_audio_buffer.append',
                        'audio' => $payload
                    ];
                    
                    if ($clientFd > 0 && $server->isEstablished($clientFd)) {
                        try {
                            $success = $server->push($clientFd, json_encode($mediaMessage));
                            if ($success) {
                                Log::info("[MEDIA] Routed to OpenAI client", [
                                    'from_fd' => $fd,
                                    'to_fd' => $clientFd,
                                    'room' => $room,
                                    'payload_size' => strlen($payload)
                                ]);
                            } else {
                                Log::error("[MEDIA] Failed to push audio to OpenAI client", [
                                    'fd' => $clientFd,
                                    'room' => $room
                                ]);
                            }
                        } catch (\Exception $e) {
                            Log::error("[MEDIA] Error pushing to OpenAI client", [
                                'fd' => $clientFd,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                    break;
                }
            }
        }
    }
} 