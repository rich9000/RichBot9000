<?php

namespace App\WebSocket\Handlers;

use App\WebSocket\Handlers\Interfaces\ConnectionHandlerInterface;
use Swoole\WebSocket\Server;
use Illuminate\Support\Facades\Log;

class OpenAIConnectionHandler implements ConnectionHandlerInterface
{
    private $tables;
    private $server;

    public function __construct(array $tables, Server $server)
    {
        $this->tables = $tables;
        $this->server = $server;
    }

    public function handleConnection(Server $server, int $fd, array $params = []): void
    {
        // Get room or create if it doesn't exist
        $room = $this->tables['rooms']->get($params['room']);
        if (!$room) {
            // Create the room with this OpenAI connection as owner
            $this->tables['rooms']->set($params['room'], [
                'owner_fd' => $fd,
                'created_at' => time(),
                'last_activity' => time(),
                'status' => 'active',
                'clients' => '[]'
            ]);
            $room = $this->tables['rooms']->get($params['room']);
            
            Log::info("[OPENAI] Room created", [
                'fd' => $fd,
                'room' => $params['room']
            ]);
        }

        $roomClients = json_decode($room['clients'] ?? '[]', true);
        if (!is_array($roomClients)) {
            $roomClients = [];
        }
        if (!in_array($fd, $roomClients, true)) {
            $roomClients[] = $fd;
        }
        $room['clients'] = json_encode(array_values($roomClients));
        $room['last_activity'] = time();
        $this->tables['rooms']->set($params['room'], $room);

        $this->tables['clients']->set($fd, [
            'type' => 'openai',
            'room' => $params['room'],
            'name' => $params['assistant_id'] ?? $params['room'],
            'user_id' => '',
            'assistant_id' => $params['assistant_id'] ?? '',
            'connected_at' => time(),
            'joined_at' => time(),
            'status' => 'connected',
        ]);

        Log::info("[OPENAI] Assistant connected", [
            'fd' => $fd,
            'room' => $params['room'],
            'assistant_id' => $params['assistant_id'] ?? null,
            'owner_fd' => $room['owner_fd']
        ]);

        // Notify room owner about new assistant (if owner is different from this connection)
        if ($room['owner_fd'] != $fd && $room['owner_fd'] > 0 && $server->isEstablished($room['owner_fd'])) {
            $this->server->push($room['owner_fd'], json_encode([
                'type' => 'system',
                'action' => 'assistant_connected',
                'data' => [
                    'assistant_id' => $params['assistant_id'] ?? null,
                    'fd' => $fd
                ]
            ]));
        }
    }

    public function handleClose(Server $server, int $fd): void
    {
        $client = $this->tables['clients']->get($fd);
        if ($client) {
            $room = $this->tables['rooms']->get($client['room']);
            
            Log::info("[OPENAI] Assistant disconnected", [
                'fd' => $fd,
                'room' => $client['room'],
                'assistant_id' => $client['assistant_id'] ?? null
            ]);

            // Notify room owner about assistant disconnection
            if ($room && $room['owner_fd'] > 0 && $this->server->isEstablished($room['owner_fd'])) {
                $this->server->push($room['owner_fd'], json_encode([
                    'type' => 'system',
                    'action' => 'assistant_disconnected',
                    'data' => [
                        'assistant_id' => $client['assistant_id'] ?? null,
                        'fd' => $fd
                    ]
                ]));
            } else {
                Log::info("[OPENAI] Skipping owner notification - owner_fd invalid or connection closed", [
                    'owner_fd' => $room['owner_fd'] ?? 'no room',
                    'is_established' => $room ? $this->server->isEstablished($room['owner_fd']) : false
                ]);
            }

            if ($room) {
                $roomClients = json_decode($room['clients'] ?? '[]', true);
                if (is_array($roomClients)) {
                    $room['clients'] = json_encode(array_values(array_diff($roomClients, [$fd])));
                    $room['last_activity'] = time();
                    $this->tables['rooms']->set($client['room'], $room);
                }
            }

            $this->tables['clients']->del($fd);
        }
    }

    public function handleMessage(Server $server, int $fd, array $message): void
    {
        if (!isset($message['type'])) {
            Log::error("[OPENAI] Missing message type", ['fd' => $fd]);
            return;
        }

        $handler = match($message['type']) {
            // Connection events
            'join' => fn() => $this->handleJoin($fd, $message),
            
            // Session events
            'session.created' => fn() => $this->handleSessionCreated($fd, $message),
            'session.updated' => fn() => $this->handleSessionUpdated($fd, $message),
            
            // Conversation events
            'conversation.created' => fn() => $this->handleConversationCreated($fd, $message),
            'conversation.item.created' => fn() => $this->handleConversationItemCreated($fd, $message),
            'conversation.item.retrieved' => fn() => $this->handleConversationItemRetrieved($fd, $message),
            'conversation.item.deleted' => fn() => $this->handleConversationItemDeleted($fd, $message),
            'conversation.item.truncated' => fn() => $this->handleConversationItemTruncated($fd, $message),
            
            // Audio transcription events
            'conversation.item.input_audio_transcription.completed' => fn() => $this->handleInputAudioTranscriptionCompleted($fd, $message),
            'conversation.item.input_audio_transcription.delta' => fn() => $this->handleInputAudioTranscriptionDelta($fd, $message),
            'conversation.item.input_audio_transcription.failed' => fn() => $this->handleInputAudioTranscriptionFailed($fd, $message),
            
            // Audio buffer events
            'input_audio_buffer.committed' => fn() => $this->handleInputAudioBufferCommitted($fd, $message),
            'input_audio_buffer.cleared' => fn() => $this->handleInputAudioBufferCleared($fd, $message),
            'input_audio_buffer.speech_started' => fn() => $this->handleInputAudioBufferSpeechStarted($fd, $message),
            'input_audio_buffer.speech_stopped' => fn() => $this->handleInputAudioBufferSpeechStopped($fd, $message),
            
            // Response events
            'response.created' => fn() => $this->handleResponseCreated($fd, $message),
            'response.done' => fn() => $this->handleResponseDone($fd, $message),
            'response.output_item.added' => fn() => $this->handleResponseOutputItemAdded($fd, $message),
            'response.output_item.done' => fn() => $this->handleResponseOutputItemDone($fd, $message),
            
            // Content part events
            'response.content_part.added' => fn() => $this->handleResponseContentPartAdded($fd, $message),
            'response.content_part.done' => fn() => $this->handleResponseContentPartDone($fd, $message),
            
            // Text events
            'response.text.delta' => fn() => $this->handleResponseTextDelta($fd, $message),
            'response.text.done' => fn() => $this->handleResponseTextDone($fd, $message),
            
            // Audio events
            'response.audio.delta' => fn() => $this->handleResponseAudioDelta($fd, $message),
            'response.audio.done' => fn() => $this->handleResponseAudioDone($fd, $message),
            'response.audio_transcript.delta' => fn() => $this->handleResponseAudioTranscriptDelta($fd, $message),
            'response.audio_transcript.done' => fn() => $this->handleResponseAudioTranscriptDone($fd, $message),
            
            // Function call events
            'response.function_call_arguments.delta' => fn() => $this->handleResponseFunctionCallArgumentsDelta($fd, $message),
            'response.function_call_arguments.done' => fn() => $this->handleResponseFunctionCallArgumentsDone($fd, $message),
            
            // Transcription session events
            'transcription_session.updated' => fn() => $this->handleTranscriptionSessionUpdated($fd, $message),
            
            // Rate limit events
            'rate_limits.updated' => fn() => $this->handleRateLimitsUpdated($fd, $message),
            
            // Output audio buffer events (WebRTC)
            'output_audio_buffer.started' => fn() => $this->handleOutputAudioBufferStarted($fd, $message),
            'output_audio_buffer.stopped' => fn() => $this->handleOutputAudioBufferStopped($fd, $message),
            'output_audio_buffer.cleared' => fn() => $this->handleOutputAudioBufferCleared($fd, $message),
            
            // Error events
            'error' => fn() => $this->handleError($fd, $message),
            
            default => fn() => Log::warning("[OPENAI] Unknown message type", [
                'fd' => $fd,
                'type' => $message['type']
            ])
        };

        $handler();
    }

    private function handleJoin(int $fd, array $message): void
    {
        Log::info("[OPENAI] Client joined", [
            'fd' => $fd,
            'room' => $message['room'] ?? 'unknown'
        ]);
    }

    private function handleSessionCreated(int $fd, array $message): void
    {
        Log::info("[OPENAI] Session created", [
            'fd' => $fd,
            'session_id' => $message['session']['id'] ?? null
        ]);
        $this->broadcastToRoom($fd, $message);
    }

    private function handleSessionUpdated(int $fd, array $message): void
    {
        Log::info("[OPENAI] Session updated", [
            'fd' => $fd,
            'session_id' => $message['session']['id'] ?? null
        ]);
        $this->broadcastToRoom($fd, $message);
    }

    private function handleConversationCreated(int $fd, array $message): void
    {
        Log::info("[OPENAI] Conversation created", [
            'fd' => $fd,
            'conversation_id' => $message['conversation']['id'] ?? null
        ]);
        $this->broadcastToRoom($fd, $message);
    }

    private function handleConversationItemCreated(int $fd, array $message): void
    {
        Log::info("[OPENAI] Conversation item created", [
            'fd' => $fd,
            'item_id' => $message['item']['id'] ?? null,
            'type' => $message['item']['type'] ?? null
        ]);
        $this->broadcastToRoom($fd, $message);
    }

    private function handleConversationItemRetrieved(int $fd, array $message): void
    {
        Log::info("[OPENAI] Conversation item retrieved", [
            'fd' => $fd,
            'item_id' => $message['item']['id'] ?? null
        ]);
        $this->broadcastToRoom($fd, $message);
    }

    private function handleConversationItemDeleted(int $fd, array $message): void
    {
        Log::info("[OPENAI] Conversation item deleted", [
            'fd' => $fd,
            'item_id' => $message['item_id'] ?? null
        ]);
        $this->broadcastToRoom($fd, $message);
    }

    private function handleConversationItemTruncated(int $fd, array $message): void
    {
        Log::info("[OPENAI] Conversation item truncated", [
            'fd' => $fd,
            'item_id' => $message['item_id'] ?? null,
            'audio_end_ms' => $message['audio_end_ms'] ?? null
        ]);
        $this->broadcastToRoom($fd, $message);
    }

    private function handleInputAudioTranscriptionCompleted(int $fd, array $message): void
    {
        Log::info("[OPENAI] Input audio transcription completed", [
            'fd' => $fd,
            'item_id' => $message['item_id'] ?? null,
            'transcript' => $message['transcript'] ?? null
        ]);
        $this->broadcastToRoom($fd, $message);
    }

    private function handleInputAudioTranscriptionDelta(int $fd, array $message): void
    {
        Log::info("[OPENAI] Input audio transcription delta", [
            'fd' => $fd,
            'item_id' => $message['item_id'] ?? null,
            'delta' => $message['delta'] ?? null
        ]);
        $this->broadcastToRoom($fd, $message);
    }

    private function handleInputAudioTranscriptionFailed(int $fd, array $message): void
    {
        Log::error("[OPENAI] Input audio transcription failed", [
            'fd' => $fd,
            'item_id' => $message['item_id'] ?? null,
            'error' => $message['error'] ?? null
        ]);
        $this->broadcastToRoom($fd, $message);
    }

    private function handleInputAudioBufferCommitted(int $fd, array $message): void
    {
        Log::info("[OPENAI] Input audio buffer committed", [
            'fd' => $fd,
            'item_id' => $message['item_id'] ?? null
        ]);
        $this->broadcastToRoom($fd, $message);
    }

    private function handleInputAudioBufferCleared(int $fd, array $message): void
    {
        Log::info("[OPENAI] Input audio buffer cleared", ['fd' => $fd]);
        $this->broadcastToRoom($fd, $message);
    }

    private function handleInputAudioBufferSpeechStarted(int $fd, array $message): void
    {
        Log::info("[OPENAI] Input audio buffer speech started", [
            'fd' => $fd,
            'audio_start_ms' => $message['audio_start_ms'] ?? null,
            'item_id' => $message['item_id'] ?? null
        ]);
        $this->broadcastToRoom($fd, $message);
    }

    private function handleInputAudioBufferSpeechStopped(int $fd, array $message): void
    {
        Log::info("[OPENAI] Input audio buffer speech stopped", [
            'fd' => $fd,
            'audio_end_ms' => $message['audio_end_ms'] ?? null,
            'item_id' => $message['item_id'] ?? null
        ]);
        $this->broadcastToRoom($fd, $message);
    }

    private function handleResponseCreated(int $fd, array $message): void
    {
        Log::info("[OPENAI] Response created", [
            'fd' => $fd,
            'response_id' => $message['response']['id'] ?? null
        ]);
        $this->broadcastToRoom($fd, $message);
    }

    private function handleResponseDone(int $fd, array $message): void
    {
        Log::info("[OPENAI] Response done", [
            'fd' => $fd,
            'response_id' => $message['response']['id'] ?? null,
            'status' => $message['response']['status'] ?? null
        ]);
        $this->broadcastToRoom($fd, $message);
    }

    private function handleResponseOutputItemAdded(int $fd, array $message): void
    {
        Log::info("[OPENAI] Response output item added", [
            'fd' => $fd,
            'response_id' => $message['response_id'] ?? null,
            'item_id' => $message['item']['id'] ?? null
        ]);
        $this->broadcastToRoom($fd, $message);
    }

    private function handleResponseOutputItemDone(int $fd, array $message): void
    {
        Log::info("[OPENAI] Response output item done", [
            'fd' => $fd,
            'response_id' => $message['response_id'] ?? null,
            'item_id' => $message['item']['id'] ?? null
        ]);
        $this->broadcastToRoom($fd, $message);
    }

    private function handleResponseContentPartAdded(int $fd, array $message): void
    {
        Log::info("[OPENAI] Response content part added", [
            'fd' => $fd,
            'response_id' => $message['response_id'] ?? null,
            'item_id' => $message['item_id'] ?? null,
            'content_index' => $message['content_index'] ?? null
        ]);
        $this->broadcastToRoom($fd, $message);
    }

    private function handleResponseContentPartDone(int $fd, array $message): void
    {
        Log::info("[OPENAI] Response content part done", [
            'fd' => $fd,
            'response_id' => $message['response_id'] ?? null,
            'item_id' => $message['item_id'] ?? null,
            'content_index' => $message['content_index'] ?? null
        ]);
        $this->broadcastToRoom($fd, $message);
    }

    private function handleResponseTextDelta(int $fd, array $message): void
    {
        Log::info("[OPENAI] Response text delta", [
            'fd' => $fd,
            'response_id' => $message['response_id'] ?? null,
            'item_id' => $message['item_id'] ?? null,
            'delta' => $message['delta'] ?? null
        ]);
        $this->broadcastToRoom($fd, $message);
    }

    private function handleResponseTextDone(int $fd, array $message): void
    {
        Log::info("[OPENAI] Response text done", [
            'fd' => $fd,
            'response_id' => $message['response_id'] ?? null,
            'item_id' => $message['item_id'] ?? null,
            'text' => $message['text'] ?? null
        ]);
        $this->broadcastToRoom($fd, $message);
    }

    private function handleResponseAudioDelta(int $fd, array $message): void
    {
        Log::info("[OPENAI] Response audio delta", [
            'fd' => $fd,
            'size' => isset($message['delta']) ? strlen($message['delta']) : 0
        ]);

        $client = $this->tables['clients']->get($fd);
        if (!$client) return;
        
        $roomName = $client['room'];
        $room = $this->tables['rooms']->get($roomName);
        if (!$room) return;
        
        // Find Twilio client in the room
        $clientsInRoom = json_decode($this->tables['rooms']->get($roomName, 'clients') ?? '[]', true);
        foreach ($clientsInRoom as $clientFd) {
            $destClient = $this->tables['clients']->get($clientFd);
            if ($destClient && $destClient['type'] === 'twilio' && !empty($destClient['stream_sid'])) {
                $twilioMessage = [
                    'event' => 'media',
                    'streamSid' => $destClient['stream_sid'],
                    'media' => [
                        'payload' => $message['delta']
                    ]
                ];

                if ($this->server->isEstablished($clientFd)) {
                    $this->server->push($clientFd, json_encode($twilioMessage));
                }
                return; // Assuming one Twilio client per room for now
            }
        }

        // Fallback to broadcasting if no Twilio client found
        $this->broadcastToRoom($fd, $message);
    }

    private function handleResponseAudioDone(int $fd, array $message): void
    {
        Log::info("[OPENAI] Response audio done", [
            'fd' => $fd,
            'response_id' => $message['response_id'] ?? null,
            'item_id' => $message['item_id'] ?? null
        ]);
        $this->broadcastToRoom($fd, $message);
    }

    private function handleResponseAudioTranscriptDelta(int $fd, array $message): void
    {
        Log::info("[OPENAI] Response audio transcript delta", [
            'fd' => $fd,
            'response_id' => $message['response_id'] ?? null,
            'item_id' => $message['item_id'] ?? null,
            'delta' => $message['delta'] ?? null
        ]);
        $this->broadcastToRoom($fd, $message);
    }

    private function handleResponseAudioTranscriptDone(int $fd, array $message): void
    {
        Log::info("[OPENAI] Response audio transcript done", [
            'fd' => $fd,
            'response_id' => $message['response_id'] ?? null,
            'item_id' => $message['item_id'] ?? null,
            'transcript' => $message['transcript'] ?? null
        ]);
        $this->broadcastToRoom($fd, $message);
    }

    private function handleResponseFunctionCallArgumentsDelta(int $fd, array $message): void
    {
        Log::info("[OPENAI] Response function call arguments delta", [
            'fd' => $fd,
            'response_id' => $message['response_id'] ?? null,
            'item_id' => $message['item_id'] ?? null,
            'call_id' => $message['call_id'] ?? null,
            'delta' => $message['delta'] ?? null
        ]);
        $this->broadcastToRoom($fd, $message);
    }

    private function handleResponseFunctionCallArgumentsDone(int $fd, array $message): void
    {
        Log::info("[OPENAI] Response function call arguments done", [
            'fd' => $fd,
            'response_id' => $message['response_id'] ?? null,
            'item_id' => $message['item_id'] ?? null,
            'call_id' => $message['call_id'] ?? null,
            'arguments' => $message['arguments'] ?? null
        ]);
        $this->broadcastToRoom($fd, $message);
    }

    private function handleTranscriptionSessionUpdated(int $fd, array $message): void
    {
        Log::info("[OPENAI] Transcription session updated", [
            'fd' => $fd,
            'session_id' => $message['session']['id'] ?? null
        ]);
        $this->broadcastToRoom($fd, $message);
    }

    private function handleRateLimitsUpdated(int $fd, array $message): void
    {
        Log::info("[OPENAI] Rate limits updated", [
            'fd' => $fd,
            'rate_limits' => $message['rate_limits'] ?? []
        ]);
        $this->broadcastToRoom($fd, $message);
    }

    private function handleOutputAudioBufferStarted(int $fd, array $message): void
    {
        Log::info("[OPENAI] Output audio buffer started", [
            'fd' => $fd,
            'response_id' => $message['response_id'] ?? null
        ]);
        $this->broadcastToRoom($fd, $message);
    }

    private function handleOutputAudioBufferStopped(int $fd, array $message): void
    {
        Log::info("[OPENAI] Output audio buffer stopped", [
            'fd' => $fd,
            'response_id' => $message['response_id'] ?? null
        ]);
        $this->broadcastToRoom($fd, $message);
    }

    private function handleOutputAudioBufferCleared(int $fd, array $message): void
    {
        Log::info("[OPENAI] Output audio buffer cleared", [
            'fd' => $fd,
            'response_id' => $message['response_id'] ?? null
        ]);
        $this->broadcastToRoom($fd, $message);
    }

    private function handleError(int $fd, array $message): void
    {
        Log::error("[ERROR] Error message handled", [
            'fd' => $fd,
            'error' => $message['error'] ?? 'Unknown error',
            'code' => $message['error']['code'] ?? 'unknown',
            'source' => $message['source'] ?? 'unknown'
        ]);
        // Do not broadcast error messages to avoid loops
    }

    private function broadcastToRoom(int $fd, array $message): void
    {
        $client = $this->tables['clients']->get($fd);
        if (!$client) {
            return;
        }

        $room = $this->tables['rooms']->get($client['room']);
        if (!$room) {
            return;
        }

        // Get all clients in the room
        foreach ($this->tables['clients'] as $clientFd => $clientData) {
            if ($clientData['room'] === $client['room'] && $clientFd !== $fd) {
                // Check if the connection is still valid
                if ($clientFd > 0 && $this->server->isEstablished($clientFd)) {
                    // Don't send audio data to non-owner clients
                    if (isset($message['type']) && strpos($message['type'], 'audio') !== false) {
                        if ($clientFd === $room['owner_fd']) {
                            try {
                                $this->server->push($clientFd, json_encode($message));
                            } catch (\Exception $e) {
                                Log::warning("[OPENAI] Failed to push audio message to owner", [
                                    'client_fd' => $clientFd,
                                    'error' => $e->getMessage()
                                ]);
                            }
                        }
                    } else {
                        try {
                            $this->server->push($clientFd, json_encode($message));
                        } catch (\Exception $e) {
                            Log::warning("[OPENAI] Failed to push message to client", [
                                'client_fd' => $clientFd,
                                'error' => $e->getMessage()
                            ]);
                        }
                    }
                } else {
                    Log::info("[OPENAI] Skipping push to invalid client", [
                        'client_fd' => $clientFd,
                        'is_established' => $this->server->isEstablished($clientFd)
                    ]);
                }
            }
        }
    }
} 