<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\Models\Conversation;
use App\Models\Assistant;
use App\Console\Commands\BareWebsocketServer;
use App\Console\Commands\BareWebsocketRelay;

class ConnectionExecutor
{
    private $auth_user_id = 1;

    public function __construct()
    {
        $this->auth_user_id = 1;
    }

    /**
     * End a conversation and close associated connections
     */
    public function end_conversation($arguments)
    {
        try {
            $conversationId = $arguments['conversation_id'] ?? null;
            $reason = $arguments['reason'] ?? 'User requested end of conversation';

            if (!$conversationId) {
                return [
                    'success' => false,
                    'error' => 'Missing required parameter: conversation_id'
                ];
            }

            $conversation = Conversation::find($conversationId);
            if (!$conversation) {
                return [
                    'success' => false,
                    'error' => 'Conversation not found'
                ];
            }

            // Update conversation status
            $conversation->status = 'completed';
            $conversation->end_reason = $reason;
            $conversation->save();

            // Close WebSocket connections
            $this->close_connections($conversationId);

            Log::info("[CONNECTION EXECUTOR] Conversation ended", [
                'conversation_id' => $conversationId,
                'reason' => $reason
            ]);

            return [
                'success' => true,
                'message' => 'Conversation ended successfully'
            ];

        } catch (\Exception $e) {
            Log::error("[CONNECTION EXECUTOR] Error ending conversation", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => 'Failed to end conversation: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Close all WebSocket connections for a conversation
     */
    public function close_connections($arguments)
    {
        try {
            $conversationId = $arguments['conversation_id'] ?? null;

            if (!$conversationId) {
                return [
                    'success' => false,
                    'error' => 'Missing required parameter: conversation_id'
                ];
            }

            // Check if OpenSwoole is available before trying to use BareWebsocketServer
            if (!class_exists('OpenSwoole\Table')) {
                return [
                    'success' => false,
                    'error' => 'OpenSwoole not available in this context'
                ];
            }

            // Get the WebSocket server instance
            $server = app(BareWebsocketServer::class);
            
            // Find and close all connections for this conversation
            $connections = $server->getConnectionsByConversation($conversationId);
            
            foreach ($connections as $fd) {
                $server->close($fd);
            }

            Log::info("[CONNECTION EXECUTOR] Connections closed", [
                'conversation_id' => $conversationId,
                'connection_count' => count($connections)
            ]);

            return [
                'success' => true,
                'message' => 'Connections closed successfully',
                'closed_connections' => count($connections)
            ];

        } catch (\Exception $e) {
            Log::error("[CONNECTION EXECUTOR] Error closing connections", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => 'Failed to close connections: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get connection status for a conversation
     */
    public function get_connection_status($arguments)
    {
        try {
            $conversationId = $arguments['conversation_id'] ?? null;

            if (!$conversationId) {
                return [
                    'success' => false,
                    'error' => 'Missing required parameter: conversation_id'
                ];
            }

            // Check if OpenSwoole is available before trying to use BareWebsocketServer
            if (!class_exists('OpenSwoole\Table')) {
                return [
                    'success' => false,
                    'error' => 'OpenSwoole not available in this context'
                ];
            }

            $server = app(BareWebsocketServer::class);
            $connections = $server->getConnectionsByConversation($conversationId);

            $status = [
                'active_connections' => count($connections),
                'connection_details' => []
            ];

            foreach ($connections as $fd) {
                $clientInfo = $server->getClientInfo($fd);
                if ($clientInfo) {
                    $status['connection_details'][] = [
                        'fd' => $fd,
                        'type' => $clientInfo['type'] ?? 'unknown',
                        'last_seen' => $clientInfo['last_seen'] ?? null,
                        'status' => $clientInfo['status'] ?? 'unknown'
                    ];
                }
            }

            return [
                'success' => true,
                'data' => $status
            ];

        } catch (\Exception $e) {
            Log::error("[CONNECTION EXECUTOR] Error getting connection status", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => 'Failed to get connection status: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Transfer conversation to a different assistant
     */
    public function transfer_conversation($arguments)
    {
        try {
            $conversationId = $arguments['conversation_id'] ?? null;
            $newAssistantId = $arguments['new_assistant_id'] ?? null;

            if (!$conversationId || !$newAssistantId) {
                return [
                    'success' => false,
                    'error' => 'Missing required parameters: conversation_id and new_assistant_id'
                ];
            }

            $conversation = Conversation::find($conversationId);
            if (!$conversation) {
                return [
                    'success' => false,
                    'error' => 'Conversation not found'
                ];
            }

            $newAssistant = Assistant::find($newAssistantId);
            if (!$newAssistant) {
                return [
                    'success' => false,
                    'error' => 'New assistant not found'
                ];
            }

            // Update conversation with new assistant
            $conversation->assistant_id = $newAssistantId;
            $conversation->save();

            // Restart relay with new assistant
            $this->restart_relay($conversationId, $newAssistantId);

            Log::info("[CONNECTION EXECUTOR] Conversation transferred", [
                'conversation_id' => $conversationId,
                'old_assistant_id' => $conversation->assistant_id,
                'new_assistant_id' => $newAssistantId
            ]);

            return [
                'success' => true,
                'message' => 'Conversation transferred successfully'
            ];

        } catch (\Exception $e) {
            Log::error("[CONNECTION EXECUTOR] Error transferring conversation", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => 'Failed to transfer conversation: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Restart the WebSocket relay with a new assistant
     */
    private function restart_relay($conversationId, $assistantId)
    {
        try {
            // Check if OpenSwoole is available before trying to use BareWebsocketServer
            if (!class_exists('OpenSwoole\Table')) {
                throw new \Exception('OpenSwoole not available in this context');
            }

            // Stop existing relay
            $server = app(BareWebsocketServer::class);
            $server->stopRelay($conversationId);

            // Start new relay
            $relay = new BareWebsocketRelay();
            $relay->start($conversationId, $assistantId);

            Log::info("[CONNECTION EXECUTOR] Relay restarted", [
                'conversation_id' => $conversationId,
                'assistant_id' => $assistantId
            ]);

        } catch (\Exception $e) {
            Log::error("[CONNECTION EXECUTOR] Error restarting relay", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    /**
     * Mute/unmute audio for a conversation
     */
    public function toggle_audio($arguments)
    {
        try {
            $conversationId = $arguments['conversation_id'] ?? null;
            $mute = $arguments['mute'] ?? true;

            if (!$conversationId) {
                return [
                    'success' => false,
                    'error' => 'Missing required parameter: conversation_id'
                ];
            }

            // Check if OpenSwoole is available before trying to use BareWebsocketServer
            if (!class_exists('OpenSwoole\Table')) {
                return [
                    'success' => false,
                    'error' => 'OpenSwoole not available in this context'
                ];
            }

            $server = app(BareWebsocketServer::class);
            $server->toggleAudio($conversationId, $mute);

            Log::info("[CONNECTION EXECUTOR] Audio toggled", [
                'conversation_id' => $conversationId,
                'mute' => $mute
            ]);

            return [
                'success' => true,
                'message' => 'Audio ' . ($mute ? 'muted' : 'unmuted') . ' successfully'
            ];

        } catch (\Exception $e) {
            Log::error("[CONNECTION EXECUTOR] Error toggling audio", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => 'Failed to toggle audio: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get conversation statistics
     */
    public function get_conversation_stats($arguments)
    {
        try {
            $conversationId = $arguments['conversation_id'] ?? null;

            if (!$conversationId) {
                return [
                    'success' => false,
                    'error' => 'Missing required parameter: conversation_id'
                ];
            }

            $conversation = Conversation::find($conversationId);
            if (!$conversation) {
                return [
                    'success' => false,
                    'error' => 'Conversation not found'
                ];
            }

            $stats = [
                'duration' => $conversation->created_at->diffInSeconds(now()),
                'message_count' => $conversation->messages()->count(),
                'status' => $conversation->status,
                'assistant_id' => $conversation->assistant_id,
                'start_time' => $conversation->created_at,
                'last_activity' => $conversation->updated_at
            ];

            return [
                'success' => true,
                'data' => $stats
            ];

        } catch (\Exception $e) {
            Log::error("[CONNECTION EXECUTOR] Error getting conversation stats", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => 'Failed to get conversation stats: ' . $e->getMessage()
            ];
        }
    }
} 