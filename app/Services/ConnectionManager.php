<?php

namespace App\Services;

use OpenSwoole\Table;
use OpenSwoole\WebSocket\Server;
use OpenSwoole\Http\Request;
use OpenSwoole\WebSocket\Frame;
use OpenSwoole\Coroutine\Http\Client;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client as GuzzleClient;

class ConnectionManager
{
    private Table $clientsTable;
    private Table $chatsTable;
    private Table $relayTable;
    private Table $streamSubscriptionsTable;
    private RelayManager $relayManager;
    private $server;
    private Table $openaiTable;
    private Table $openaiConnectionsTable;
    private Table $sessionStateTable;

    const TYPE_CLIENT = 'Client';
    const TYPE_MANAGER = 'Manager';
    const TYPE_ASSISTANT = 'Assistant';

    public function __construct()
    {
        // Connected clients table
        $this->clientsTable = new Table(1024);
        $this->clientsTable->column('user_id', Table::TYPE_INT);
        $this->clientsTable->column('type', Table::TYPE_STRING, 32);
        $this->clientsTable->column('user_name', Table::TYPE_STRING, 64);
        $this->clientsTable->column('status', Table::TYPE_STRING, 32);
        $this->clientsTable->column('chat_id', Table::TYPE_STRING, 64);
        $this->clientsTable->column('assistant_id', Table::TYPE_STRING, 64);
        $this->clientsTable->column('last_activity', Table::TYPE_INT);
        $this->clientsTable->create();

        // Active chats table
        $this->chatsTable = new Table(1024);
        $this->chatsTable->column('type', Table::TYPE_STRING, 32);
        $this->chatsTable->column('status', Table::TYPE_STRING, 32);
        $this->chatsTable->column('participants', Table::TYPE_STRING, 1024);
        $this->chatsTable->column('start_time', Table::TYPE_INT);
        $this->chatsTable->column('last_activity', Table::TYPE_INT);
        $this->chatsTable->create();

        // Relay connections table - stores Ratchet client connections
        $this->relayTable = new Table(1024);
        $this->relayTable->column('chat_id', Table::TYPE_STRING, 64);
        $this->relayTable->column('client_fd', Table::TYPE_INT);
        $this->relayTable->column('type', Table::TYPE_STRING, 32); // 'openai' or 'richbot'
        $this->relayTable->column('status', Table::TYPE_STRING, 32);
        $this->relayTable->column('loop_id', Table::TYPE_STRING, 64); // To track React event loop
        $this->relayTable->column('last_activity', Table::TYPE_INT);
        $this->relayTable->create();

        // Stream subscriptions table
        $this->streamSubscriptionsTable = new Table(1024);
        $this->streamSubscriptionsTable->column('manager_fds', Table::TYPE_STRING, 1024); // JSON array of manager FDs
        $this->streamSubscriptionsTable->create();

        // OpenAI connections table
        $this->openaiConnectionsTable = new Table(1024);
        $this->openaiConnectionsTable->column('connection', Table::TYPE_STRING, 8192); // Serialized connection object
        $this->openaiConnectionsTable->column('status', Table::TYPE_STRING, 32);
        $this->openaiConnectionsTable->column('last_activity', Table::TYPE_INT);
        $this->openaiConnectionsTable->create();

        // Session state table to track conversation context
        $this->sessionStateTable = new Table(1024);
        $this->sessionStateTable->column('chat_id', Table::TYPE_STRING, 64);
        $this->sessionStateTable->column('session_id', Table::TYPE_STRING, 64);
        $this->sessionStateTable->column('conversation_state', Table::TYPE_STRING, 8192); // Store conversation items
        $this->sessionStateTable->column('last_activity', Table::TYPE_INT);
        $this->sessionStateTable->create();
    }

    public function setServer($server): void
    {
        $this->server = $server;
        $this->relayManager = new RelayManager($this->relayTable, $server);
    }

    public function handleNewConnection(Server $server, Request $request): void
    {
        try {
            Log::info('New WebSocket connection attempt', [
                'fd' => $request->fd,
                'ip' => $request->server['remote_addr'],
                'uri' => $request->server['request_uri'],
                'time' => now(),
                'headers' => $request->header
            ]);

            $token = $this->extractToken($request->server['request_uri']);
            Log::info('Token extracted', ['token' => $token]);

            $user = $this->authenticateUser($token);
            if (!$user) {
                Log::warning('Authentication failed', [
                    'fd' => $request->fd,
                    'token' => $token
                ]);
                $server->disconnect($request->fd, 1008, 'Authentication failed');
                return;
            }

            Log::info('User authenticated', [
                'fd' => $request->fd,
                'user_id' => $user->id,
                'user_name' => $user->name
            ]);

            $protocol = $request->get['protocol'] ?? 'Client';
            $userName = $request->get['name'] ?? $user->name;

            $this->registerClient($request->fd, [
                'user_id' => $user->id,
                'type' => $protocol,
                'user_name' => $userName,
                'status' => 'connected'
            ]);

            Log::info('Client registered', [
                'fd' => $request->fd,
                'user_id' => $user->id,
                'protocol' => $protocol,
                'user_name' => $userName
            ]);

            $server->push($request->fd, json_encode([
                'event' => 'connection_established',
                'fd' => $request->fd
            ]));

            $this->broadcastStateUpdate($server);

        } catch (\Exception $e) {
            Log::error("Connection error", [
                'fd' => $request->fd ?? 'unknown',
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $server->disconnect($request->fd, 1011, 'Server error');
        }
    }

    public function handleMessage(Server $server, Frame $frame): void
    {
        try {
            // Log raw frame data immediately
            Log::info("WebSocket message from client", [
                'fd' => $frame->fd,
                'data' => $frame->data,
                'length' => strlen($frame->data),
                'timestamp' => now()
            ]);

            $data = json_decode($frame->data, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error("Invalid JSON from client", [
                    'fd' => $frame->fd,
                    'error' => json_last_error_msg()
                ]);
                return;
            }

            $event = $data['event'] ?? 'unknown';
            $type = $data['type'] ?? 'unknown';

            // Handle different message types
            if ($event === 'message' || $type === 'text') {
                // Get chat ID for this client
                $clientData = $this->clientsTable->get($frame->fd);
                if (!$clientData || empty($clientData['chat_id'])) {
                    Log::error("No active chat found for client", ['fd' => $frame->fd]);
                    return;
                }

                $chatId = $clientData['chat_id'];
                
                Log::info("Text message from client", [
                    'fd' => $frame->fd,
                    'chat_id' => $chatId,
                    'content' => $data['content'] ?? null,
                    'role' => $data['role'] ?? 'user'
                ]);

                // Forward to OpenAI via RelayManager
                $this->relayManager->sendMessageToOpenAI($chatId, [
                    'type' => 'text',
                    'content' => $data['content'] ?? '',
                    'role' => $data['role'] ?? 'user'
                ]);
            }
            // Handle audio messages
            else if ($event === 'media' && $type === 'audio') {
                $clientData = $this->clientsTable->get($frame->fd);
                if (!$clientData || empty($clientData['chat_id'])) {
                    Log::error("No active chat found for client", ['fd' => $frame->fd]);
                    return;
                }

                // Forward audio data to OpenAI
                $this->relayManager->sendAudioToOpenAI($clientData['chat_id'], $data);
            }
            // Handle start_chat event
            else if ($event === 'start_chat') {
                Log::info("Start chat request from client", [
                    'fd' => $frame->fd,
                    'target_type' => $data['target_type'] ?? null,
                    'assistant_id' => $data['assistant_id'] ?? null
                ]);
                $this->handleStartChat($server, $frame->fd, $data);
            }
            // Handle other events
            else {
                Log::info("Other message from client", [
                    'fd' => $frame->fd,
                    'event' => $event,
                    'type' => $type,
                    'data' => $data
                ]);
                
                // Forward other messages to RelayManager
                $clientData = $this->clientsTable->get($frame->fd);
                if ($clientData && !empty($clientData['chat_id'])) {
                    $this->relayManager->handleMessage($clientData['chat_id'], $data);
                }
            }

        } catch (\Exception $e) {
            Log::error("Error handling client message", [
                'fd' => $frame->fd,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    private function handleStartChat(Server $server, int $fd, array $data): void
    {
        $chatId = uniqid('chat_', true);
        $client = $this->clientsTable->get($fd);

        if (!$client) {
            Log::error("Client not found for start chat", ['fd' => $fd]);
            return;
        }

        // Update client with chat ID
        $client['chat_id'] = $chatId;
        $this->updateClient($fd, $client);

        Log::info("Starting chat", [
            'fd' => $fd,
            'chat_id' => $chatId,
            'target_type' => $data['target_type'],
            'client' => $client
        ]);

        if ($data['target_type'] === 'assistant') {
            $this->startAssistantChat($server, $fd, $chatId, $data);
        } else {
            $this->startClientChat($server, $fd, $chatId, $data['target_id']);
        }

        $this->broadcastStateUpdate($server);
    }

    private function startAssistantChat(Server $server, int $fd, string $chatId, array $data): void
    {
        try {
            Log::info('Starting assistant chat', [
                'fd' => $fd,
                'chatId' => $chatId,
                'assistant_id' => $data['assistant_id'] ?? null,
                'timestamp' => now()
            ]);

            if (!isset($data['assistant_id'])) {
                throw new \Exception("Missing assistant_id");
            }

            // Store the chat data
            $this->chatsTable->set($chatId, [
                'type' => 'assistant',
                'status' => 'active',
                'participants' => json_encode([
                    ['fd' => $fd, 'role' => 'user'],
                    ['assistant_id' => $data['assistant_id'], 'role' => 'assistant']
                ]),
                'start_time' => time(),
                'last_activity' => time()
            ]);

            // Update client status
            $this->updateClientStatus($fd, 'in_chat', $chatId);

            // Start relay connections
            $this->relayManager->startRelays($chatId, $fd);

            // Notify client
            $server->push($fd, json_encode([
                'event' => 'chat_started',
                'chatId' => $chatId,
                'chatData' => [
                    'assistant' => [
                        'id' => $data['assistant_id'],
                        'name' => 'AI Assistant'
                    ],
                    'type' => 'assistant'
                ]
            ]));

            Log::info('Assistant chat started successfully', [
                'chatId' => $chatId,
                'fd' => $fd,
                'timestamp' => now()
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to start assistant chat', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'chatId' => $chatId,
                'fd' => $fd
            ]);
            
            // Clean up on failure
            if ($this->relayTable->exists($chatId)) {
                $this->relayTable->del($chatId);
            }
            if ($this->chatsTable->exists($chatId)) {
                $this->chatsTable->del($chatId);
            }
            
            $this->updateClientStatus($fd, 'connected');
            
            $server->push($fd, json_encode([
                'event' => 'error',
                'message' => 'Failed to start AI chat: ' . $e->getMessage(),
                'code' => 'ai_connection_failed'
            ]));
            
            throw $e;
        }
    }

    private function startClientChat(Server $server, int $fd, string $chatId, int $targetFd): void
    {
        $this->registerChat($chatId, [
            'type' => 'client',
            'status' => 'active',
            'participants' => json_encode([
                ['fd' => $fd, 'role' => 'initiator'],
                ['fd' => $targetFd, 'role' => 'participant']
            ])
        ]);

        $this->updateClientStatus($fd, 'in_chat', $chatId);
        $this->updateClientStatus($targetFd, 'in_chat', $chatId);

        // Notify both clients
        $chatData = $this->getChatData($chatId);
        $this->notifyChatParticipants($server, $chatId, [
            'event' => 'chat_started',
            'chatId' => $chatId,
            'chatData' => $chatData
        ]);
    }

    private function handleEndChat(Server $server, int $fd, array $data): void
    {
        $chatId = $data['chat_id'];
        $chat = $this->chatsTable->get($chatId);

        if (!$chat) return;

        // Clean up OpenAI connection if exists
        if ($this->relayTable->exists($chatId)) {
            $clientData = $this->relayTable->get($chatId);
            $client = unserialize($clientData['client']);
            if ($client && $client->connected) {
                $client->close();
            }
            $this->relayTable->del($chatId);
        }

        // Update participants status
        $participants = json_decode($chat['participants'], true);
        foreach ($participants as $participant) {
            if (isset($participant['fd'])) {
                $this->updateClientStatus($participant['fd'], 'connected');
            }
        }

        $this->chatsTable->del($chatId);
        $this->broadcastStateUpdate($server);
    }

    private function handleMediaMessage(Server $server, int $fd, array $data): void
    {
        try {
            Log::debug("Starting media message handling", [
                'fd' => $fd,
                'type' => $data['type'] ?? 'unknown',
                'has_data' => isset($data['data']),
                'data_size' => isset($data['data']) ? strlen($data['data']) : 0
            ]);

            // Get the client's active chat
            $client = $this->getClient($fd);
            if (!$client) {
                throw new \Exception("Client not found");
            }

            Log::info("Processing media for client", [
                'fd' => $fd,
                'client_type' => $client['type'],
                'client_status' => $client['status']
            ]);

            // Forward the media message to the appropriate relay
            if (isset($this->relayTable)) {
                foreach ($this->relayTable as $relay) {
                    if ($relay['client_fd'] == $fd) {
                        Log::info("Forwarding media to relay", [
                            'relay_type' => $relay['type'],
                            'relay_status' => $relay['status']
                        ]);
                        
                        // Forward the message
                        $server->push($relay['client_fd'], json_encode([
                            'event' => 'media',
                            'type' => $data['type'],
                            'data' => $data['data']
                        ]));
                    }
                }
            } else {
                Log::warning("No relay table found for media forwarding");
            }
        } catch (\Exception $e) {
            Log::error("Error in handleMediaMessage", [
                'fd' => $fd,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    private function broadcastMediaToParticipants(Server $server, string $chatId, array $mediaData): void
    {
        // Send to chat participants
        $chat = $this->chatsTable->get($chatId);
        $participants = json_decode($chat['participants'], true);
        
        foreach ($participants as $participant) {
            if (isset($participant['fd'])) {
                $server->push($participant['fd'], json_encode([
                    'event' => 'media',
                    'chatId' => $chatId,
                    'media' => $mediaData
                ]));
            }
        }

        // Send to subscribed managers
        foreach ($this->streamSubscriptions[$chatId] ?? [] as $managerFd) {
            $server->push($managerFd, json_encode([
                'event' => 'media',
                'chatId' => $chatId,
                'media' => $mediaData
            ]));
        }
    }

    private function handleStartStream(Server $server, int $fd, array $data): void
    {
        $client = $this->clientsTable->get($fd);
        if ($client['type'] !== self::TYPE_MANAGER) return;

        $chatId = $data['chat_id'];
        if (!isset($this->streamSubscriptions[$chatId])) {
            $this->streamSubscriptions[$chatId] = [];
        }
        $this->streamSubscriptions[$chatId][$fd] = true;
    }

    private function handleStopStream(Server $server, int $fd, array $data): void
    {
        $chatId = $data['chat_id'];
        if (isset($this->streamSubscriptions[$chatId][$fd])) {
            unset($this->streamSubscriptions[$chatId][$fd]);
        }
    }

    private function broadcastStateUpdate(Server $server): void
    {
        $state = [
            'event' => 'state_update',
            'clients' => $this->getClientsData(),
            'activeChats' => $this->getChatsData()
        ];

        foreach ($server->connections as $fd) {
            if ($server->isEstablished($fd)) {
                $server->push($fd, json_encode($state));
            }
        }
    }

    // Helper methods...
    public function extractToken(string $uri): ?string
    {
        Log::info('Extracting token from URI', ['uri' => $uri]);
        
        // Extract token from /app/{token} or /app/{token}/{assistant_id} format
        if (preg_match('/^\/app\/([^\/]+)(?:\/[^\/]+)?$/', $uri, $matches)) {
            $token = urldecode($matches[1]); // Decode the URL-encoded token
            Log::info('Token extracted and decoded', ['token' => $token]);
            return $token;
        }
        
        Log::warning('No token found in URI');
        return null;
    }

    public function extractAssistantId(string $uri): ?string
    {
        Log::info('Extracting assistant_id from URI', ['uri' => $uri]);
        
        // Extract assistant_id from /app/{token}/{assistant_id} format
        if (preg_match('/^\/app\/[^\/]+\/([^\/]+)$/', $uri, $matches)) {
            $assistantId = urldecode($matches[1]); // Decode the URL-encoded assistant_id
            Log::info('Assistant ID extracted and decoded', ['assistant_id' => $assistantId]);
            return $assistantId;
        }
        
        Log::info('No assistant_id found in URI');
        return null;
    }

    public function authenticateUser(?string $token): ?\App\Models\User
    {
        if (!$token) {
            Log::warning('No token provided for authentication');
            return null;
        }
        
        try {
            Log::info('Attempting to authenticate token', ['token_length' => strlen($token)]);
            $accessToken = PersonalAccessToken::findToken($token);
            
            if (!$accessToken) {
                Log::warning('Token not found in database');
                return null;
            }
            
            $user = $accessToken->tokenable;
            Log::info('User authenticated successfully', [
                'user_id' => $user->id,
                'user_name' => $user->name
            ]);
            
            return $user;
        } catch (\Exception $e) {
            Log::error("Authentication error", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    // Additional helper methods as needed...

    public function registerClient(int $fd, array $data): void
    {
        $this->clientsTable->set($fd, [
            'user_id' => $data['user_id'],
            'type' => $data['type'],
            'user_name' => $data['user_name'],
            'status' => $data['status'],
            'chat_id' => $data['chat_id'],
            'last_activity' => time()
        ]);
    }

    private function registerChat(string $chatId, array $data): void
    {
        $this->chatsTable->set($chatId, [
            'type' => $data['type'],
            'status' => $data['status'],
            'participants' => $data['participants'],
            'start_time' => time(),
            'last_activity' => time()
        ]);
    }

    private function updateClientStatus(int $fd, string $status, string $chatId = ''): void
    {
        if ($this->clientsTable->exists($fd)) {
            $client = $this->clientsTable->get($fd);
            $this->clientsTable->set($fd, [
                'user_id' => $client['user_id'],
                'type' => $client['type'],
                'user_name' => $client['user_name'],
                'status' => $status,
                'chat_id' => $chatId,
                'last_activity' => time()
            ]);
        }
    }

    public function getClientsData(): array
    {
        $clients = [];
        foreach ($this->clientsTable as $fd => $client) {
            $clients[$fd] = [
                'user_id' => $client['user_id'],
                'type' => $client['type'],
                'userName' => $client['user_name'],
                'status' => $client['status'],
                'chatId' => $client['chat_id'],
                'lastActivity' => $client['last_activity']
            ];
        }
        return $clients;
    }

    public function getChatsData(): array
    {
        $chats = [];
        foreach ($this->chatsTable as $chatId => $chat) {
            $chats[$chatId] = [
                'type' => $chat['type'],
                'status' => $chat['status'],
                'participants' => json_decode($chat['participants'], true),
                'startTime' => $chat['start_time'],
                'lastActivity' => $chat['last_activity']
            ];
        }
        return $chats;
    }

    public function getChatData(string $chatId): ?array
    {
        if (!$this->chatsTable->exists($chatId)) {
            return null;
        }
        
        $chat = $this->chatsTable->get($chatId);
        return [
            'type' => $chat['type'],
            'status' => $chat['status'],
            'participants' => json_decode($chat['participants'], true),
            'startTime' => $chat['start_time'],
            'lastActivity' => $chat['last_activity']
        ];
    }

    private function notifyChatParticipants(Server $server, string $chatId, array $message): void
    {
        $chat = $this->chatsTable->get($chatId);
        if (!$chat) return;

        $participants = json_decode($chat['participants'], true);
        foreach ($participants as $participant) {
            if (isset($participant['fd'])) {
                $server->push($participant['fd'], json_encode($message));
            }
        }
    }

    private function configureOpenAIConnection(string $chatId, array $data): bool
    {
        $client = null;
        try {
            Log::info('Configuring OpenAI connection', [
                'chat_id' => $chatId,
                'timestamp' => now()
            ]);

            $client = new Client('api.openai.com', 443, true);
            $client->set([
                'timeout' => 60,
                'websocket_timeout' => 60,
                'keep_alive' => true,
                'websocket_mask' => true,
                'ssl_verify_peer' => true,
                'heartbeat_check_interval' => 30,
                'heartbeat_idle_time' => 120,
                'max_retries' => 3,
                'retry_delay' => 1000
            ]);

            $apiKey = config('services.openai.api_key');
            if (empty($apiKey)) {
                throw new \Exception("OpenAI API key not configured");
            }

            // Set headers for WebSocket connection
            $client->setHeaders([
                'Authorization' => 'Bearer ' . trim($apiKey),
                'Content-Type' => 'application/json',
                'OpenAI-Beta' => 'realtime=v1'
            ]);
            
            // Connect to WebSocket URL with correct path
            $wsUrl = "wss://api.openai.com/v1/realtime?model=gpt-4-turbo-preview";
            Log::info('Attempting OpenAI WebSocket connection', [
                'url' => $wsUrl,
                'timestamp' => now()
            ]);
            
            $success = $client->upgrade($wsUrl);
            
            if (!$success) {
                Log::error('WebSocket upgrade failed', [
                    'status_code' => $client->getStatusCode(),
                    'error_code' => $client->errCode,
                    'error_msg' => $client->errMsg,
                    'timestamp' => now()
                ]);
                throw new \Exception("Failed to establish WebSocket connection: " . $client->errMsg);
            }

            Log::info('OpenAI WebSocket connection established', [
                'status_code' => $client->getStatusCode(),
                'timestamp' => now()
            ]);

            // Set up ping/pong for connection health
            \OpenSwoole\Timer::tick(30000, function() use ($client, $chatId) {
                if ($client->connected) {
                    try {
                        $sent = $client->push('ping');
                        if (!$sent) {
                            $this->handleOpenAIDisconnect($chatId);
                        }
                    } catch (\Exception $e) {
                        Log::error('Ping failed', [
                            'chat_id' => $chatId,
                            'error' => $e->getMessage()
                        ]);
                        $this->handleOpenAIDisconnect($chatId);
                    }
                }
            });

  /*          // Set up message handler for OpenAI responses/*
            $client->on('message', function($cli, $frame) use ($chatId) {
                if ($frame->data === 'pong') {
                    return; // Ignore pong responses
                }
                
                try {
                    $data = json_decode($frame->data, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        throw new \Exception("Invalid JSON response");
                    }

                    // Update last activity
                    $this->openAiTable->set($chatId, [
                        'last_activity' => time(),
                        'status' => 'active'
                    ]);

                    $this->handleOpenAIMessage($data, $chatId);
                } catch (\Exception $e) {
                    Log::error('Error processing OpenAI message', [
                        'chat_id' => $chatId,
                        'error' => $e->getMessage(),
                        'data' => $frame->data
                    ]);
                }
            });
*/
            

            // Store connection in table
            $this->relayTable->set($chatId, [
                'client' => serialize($client),
                'status' => 'connected',
                'last_activity' => time()
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error("OpenAI configuration error", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'chat_id' => $chatId
            ]);

            // Clean up failed connection
            if ($client && $client->connected) {
                $client->close();
            }

            // Update table with error status
            $this->relayTable->set($chatId, [
                'client' => '',
                'status' => 'error',
                'last_activity' => time()
            ]);

            return false;
        }
    }

    public function handleOpenAIMessage(array $data, string $chatId): void
    {
        $clientFd = $this->relayTable->get($chatId)['client_fd'] ?? null;
        if (!$clientFd) {
            Log::error('No client FD found for chat', ['chat_id' => $chatId]);
            return;
        }

        // Only log non-audio-delta messages
        if ($data['type'] !== 'response.audio.delta') {
            Log::info("OpenAI message", [
                'type' => $data['type'],
                'chat_id' => $chatId
            ]);
        }

        // Forward message to client
        $this->server->push($clientFd, json_encode([
            'event' => 'openai_message',
            'data' => $data,
            'chat_id' => $chatId
        ]));
    }

    private function handleOpenAIDisconnect(string $chatId): void
    {
        Log::warning('OpenAI connection lost', ['chat_id' => $chatId]);

        // Update connection status
        $this->relayTable->set($chatId, [
            'status' => 'disconnected',
            'last_activity' => time()
        ]);

        // Attempt to reconnect
        $maxRetries = 3;
        $retryDelay = 1000; // milliseconds
        
        for ($i = 0; $i < $maxRetries; $i++) {
            Log::info("Attempting reconnection", [
                'chat_id' => $chatId,
                'attempt' => $i + 1
            ]);

            usleep($retryDelay * 1000); // Convert to microseconds
            
           // if ($this->configureOpenAIConnection($chatId, [])) {
           //     Log::info("Successfully reconnected", ['chat_id' => $chatId]);
          //      return;
          //  }

            $retryDelay *= 2; // Exponential backoff
        }

        // If all retries failed, notify the client
        $clientFd = $this->relayTable->get($chatId)['client_fd'] ?? null;
        if ($clientFd) {
            $this->server->push($clientFd, json_encode([
                'event' => 'error',
                'message' => 'Lost connection to AI assistant and failed to reconnect',
                'chat_id' => $chatId
            ]));
        }
    }

    public function handleClientDisconnect(Server $server, int $fd): void
    {
        $client = $this->getClient($fd);
        if ($client && !empty($client['chat_id'])) {
            // Close OpenAI connection if exists
            if (isset($this->openaiConnections[$client['chat_id']])) {
                try {
                    $this->openaiConnections[$client['chat_id']]->close();
                } catch (\Exception $e) {
                    // Ignore close errors
                }
                unset($this->openaiConnections[$client['chat_id']]);
                $this->openaiTable->del($client['chat_id']);
            }
        }

        // Remove from clients table
        if ($this->clientsTable->exists($fd)) {
            $this->clientsTable->del($fd);
        }

        Log::info("Client disconnected", [
            'fd' => $fd,
            'chat_id' => $client['chat_id'] ?? null
        ]);
    }

    private function handleTextMessage(Server $server, int $fd, array $data): void
    {
        try {
            Log::info("Handling text message", [
                'fd' => $fd,
                'message' => $data['message'] ?? null,
                'role' => $data['role'] ?? null
            ]);

            // Get client info
            $client = $this->getClient($fd);
            if (!$client) {
                throw new \Exception("Client not found");
            }

            // Forward the message to the appropriate relay
            if (isset($this->relayTable)) {
                foreach ($this->relayTable as $relay) {
                    if ($relay['client_fd'] == $fd) {
                        Log::info("Forwarding text message to relay", [
                            'relay_type' => $relay['type'],
                            'relay_status' => $relay['status']
                        ]);
                        
                        // Forward the message
                        $server->push($relay['client_fd'], json_encode([
                            'event' => 'message',
                            'type' => 'text',
                            'content' => $data['message'],
                            'role' => $data['role']
                        ]));
                    }
                }
            } else {
                Log::warning("No relay table found for text message forwarding");
            }
        } catch (\Exception $e) {
            Log::error("Error in handleTextMessage", [
                'fd' => $fd,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    private function startAIRelay(string $chatId, int $fd, array $data): bool
    {
        try {
            $relay = new AIRelay($chatId, $fd, $data);
            if (!$relay->start()) {
                throw new \Exception("Failed to start AI relay");
            }

            // Store in openAiTable instead of array
            $this->relayTable->set($chatId, [
                'client_fd' => $fd,
                'status' => 'active',
                'last_activity' => time()
            ]);

            return true;

        } catch (\Exception $e) {
            Log::error("Failed to start AI relay", [
                'chat_id' => $chatId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function cleanup(): void
    {
        $now = time();
        
        // Clean up stale clients
        foreach ($this->clientsTable as $fd => $client) {
            if ($now - $client['last_activity'] > 600) { // 10 minutes
                $this->handleClientDisconnect($this->server, $fd);
            }
        }
        
        // Clean up stale chats
        foreach ($this->chatsTable as $chatId => $chat) {
            if ($now - $chat['last_activity'] > 600) {
                $this->handleEndChat($this->server, null, ['chat_id' => $chatId]);
            }
        }

        // Clean up stale relays
        $this->relayManager->cleanup();
    }

    /**
     * Get client information by file descriptor
     * 
     * @param int $fd
     * @return array|null Returns null if client not found, array with client data otherwise
     */
    public function getClient(int $fd): ?array
    {
        $client = $this->clientsTable->get($fd);
        return $client === false ? null : $client;
    }

    public function updateClient(int $fd, array $data): bool
    {
        try {
            // Ensure all required fields are present
            $requiredFields = ['user_id', 'type', 'user_name', 'status'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field])) {
                    Log::error("Missing required field in client data", [
                        'fd' => $fd,
                        'field' => $field
                    ]);
                    return false;
                }
            }

            // Update last activity
            $data['last_activity'] = time();

            // Set optional fields if not present
            $data['chat_id'] = $data['chat_id'] ?? '';
            $data['assistant_id'] = $data['assistant_id'] ?? '';

            $success = $this->clientsTable->set($fd, $data);
            
            if ($success) {
                Log::debug("Updated client data", [
                    'fd' => $fd,
                    'data' => $data
                ]);
            } else {
                Log::error("Failed to update client data", [
                    'fd' => $fd,
                    'data' => $data
                ]);
            }

            return $success;
        } catch (\Exception $e) {
            Log::error("Error updating client", [
                'fd' => $fd,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    public function storeOpenAIConnection(string $chatId, $connection): void
    {
        $this->openaiConnectionsTable->set($chatId, [
            'connection' => serialize($connection),
            'status' => 'connected',
            'last_activity' => time()
        ]);

        Log::info("Stored OpenAI connection", [
            'chat_id' => $chatId
        ]);
    }

    public function getOpenAIConnection(string $chatId)
    {
        $data = $this->openaiConnectionsTable->get($chatId);
        return $data ? unserialize($data['connection']) : null;
    }

    public function removeOpenAIConnection(string $chatId): void
    {
        $this->openaiConnectionsTable->del($chatId);
    }

    public function isOpenAIConnected(string $chatId): bool
    {
        $data = $this->openaiConnectionsTable->get($chatId);
        if (!$data) return false;
        
        $connection = unserialize($data['connection']);
        return $connection && $connection->connected;
    }

    public function hasChatHistory(string $chatId): bool
    {
        $chatData = $this->chatsTable->get($chatId);
        return $chatData !== false && isset($chatData['last_activity']) && $chatData['last_activity'] > 0;
    }

    public function updateChatActivity(string $chatId): void
    {
        $chatData = $this->chatsTable->get($chatId);
        if ($chatData) {
            $chatData['last_activity'] = time();
            $this->chatsTable->set($chatId, $chatData);
        }
    }

    public function storeSessionState(string $chatId, array $state): void
    {
        $this->sessionStateTable->set($chatId, [
            'chat_id' => $chatId,
            'session_id' => $state['session_id'] ?? uniqid('sess_'),
            'conversation_state' => json_encode($state['conversation'] ?? []),
            'last_activity' => time()
        ]);
    }

    public function getSessionState(string $chatId): ?array
    {
        $state = $this->sessionStateTable->get($chatId);
        if (!$state) {
            return null;
        }

        return [
            'session_id' => $state['session_id'],
            'conversation' => json_decode($state['conversation_state'], true) ?? [],
            'last_activity' => $state['last_activity']
        ];
    }

    public function updateSessionState(string $chatId, array $newItems): void
    {
        $state = $this->sessionStateTable->get($chatId);
        if ($state) {
            $conversation = json_decode($state['conversation_state'], true) ?? [];
            $conversation = array_merge($conversation, $newItems);
            
            $this->sessionStateTable->set($chatId, [
                'chat_id' => $state['chat_id'],
                'session_id' => $state['session_id'],
                'conversation_state' => json_encode($conversation),
                'last_activity' => time()
            ]);
        }
    }

    public function clearSessionState(string $chatId): void
    {
        $this->sessionStateTable->del($chatId);
    }
} 