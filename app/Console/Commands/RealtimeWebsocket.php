<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use OpenSwoole\WebSocket\Server;
use App\Services\ConnectionManager;
use Illuminate\Support\Facades\Log;

class RealtimeWebsocket extends Command
{
    protected $signature = 'websocket:serve';
    protected $description = 'Start the WebSocket server for realtime communication';

    private ConnectionManager $connectionManager;
    private Server $server;

    public function __construct(ConnectionManager $connectionManager)
    {
        parent::__construct();
        $this->connectionManager = $connectionManager;
    }

    public function handle()
    {
        $this->info("Starting WebSocket server...");
        
        $this->server = new Server('0.0.0.0', 9501, SWOOLE_PROCESS, SWOOLE_SOCK_TCP | SWOOLE_SSL);
        
        // SSL Configuration
        $this->server->set([
            'ssl_cert_file' => '/etc/ssl/certs/richbot9000.crt',
            'ssl_key_file' => '/etc/ssl/private/richbot9000.key',
            'worker_num' => 1,
            'daemonize' => false,
            'log_level' => SWOOLE_LOG_DEBUG,
            'log_file' => storage_path('logs/websocket.log'),
            'enable_coroutine' => true,
            'task_worker_num' => 4,
            'task_enable_coroutine' => true,  // Enable coroutines in task workers
            'task_use_object' => true,        // Enable object-oriented style tasks
            'task_ipc_mode' => 1,            // Use Unix socket for task IPC
            'message_queue_key' => ftok(public_path('index.php'), 1)
        ]);

        // Initialize ConnectionManager
        $this->connectionManager->setServer($this->server);

        // Handle new WebSocket connections
        $this->server->on('Open', function (Server $server, $request) {
            try {
                Log::info("New WebSocket connection", [
                    'fd' => $request->fd,
                    'uri' => $request->server['request_uri']
                ]);

                // Authenticate user
                $token = $this->connectionManager->extractToken($request->server['request_uri']);
                $user = $this->connectionManager->authenticateUser($token);
                if (!$user) {
                    $server->disconnect($request->fd, 1008, 'Authentication failed');
                    return;
                }

                // Register client
                $this->connectionManager->registerClient($request->fd, [
                    'user_id' => $user->id,
                    'type' => 'Client',
                    'user_name' => $user->name,
                    'status' => 'connected'
                ]);

                $server->push($request->fd, json_encode([
                    'type' => 'status',
                    'status' => 'connected'
                ]));

            } catch (\Exception $e) {
                Log::error("Connection error", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $server->disconnect($request->fd, 1011, 'Server error');
            }
        });

        // Handle messages
        $this->server->on('Message', function (Server $server, $frame) {
            try {
                $data = json_decode($frame->data, true);

                Log::info("Received message", [
                    'fd' => $frame->fd,
                    'data' => $data,
                    'raw_data' => $frame->data
                ]);

                if (!$data) {
                    Log::error("Invalid JSON received", ['fd' => $frame->fd]);
                    return;
                }

                // Handle start_chat event
                if ($data['type'] === 'start_chat') {
                    $chatId = uniqid('chat_', true);
                    
                    // Update client's chat context
                    $client = $this->connectionManager->getClient($frame->fd);
                    if ($client) {
                        $client['chat_id'] = $chatId;
                        $client['assistant_id'] = $data['assistant_id'];
                        $client['status'] = 'in_chat';
                        $this->connectionManager->updateClient($frame->fd, $client);
                        
                        Log::info("Updated client chat context", [
                            'fd' => $frame->fd,
                            'chat_id' => $chatId,
                            'assistant_id' => $data['assistant_id'],
                            'client' => $client
                        ]);
                    }
                    
                    // Notify client that chat is ready
                    $server->push($frame->fd, json_encode([
                        'type' => 'status',
                        'status' => 'chat_ready'
                    ]));
                    
                    // Start OpenAI connection in task worker
                    $taskId = $server->task([
                        'type' => 'start_chat',
                        'chat_id' => $chatId,
                        'client_fd' => $frame->fd,
                        'data' => $data
                    ]);

                    Log::info("Chat task queued", [
                        'chat_id' => $chatId,
                        'client_fd' => $frame->fd,
                        'task_id' => $taskId,
                        'assistant_id' => $data['assistant_id']
                    ]);
                }
                // Forward other messages to appropriate task worker
                else {
                    $client = $this->connectionManager->getClient($frame->fd);
                    if ($client && !empty($client['chat_id'])) {
                        
                        Log::info("Creating relay task for message", [
                            'chat_id' => $client['chat_id'],
                            'data' => $data,
                            'client_info' => $client
                        ]);

                        $taskData = [
                            'type' => 'relay_message',
                            'chat_id' => $client['chat_id'],
                            'client_fd' => $frame->fd,
                            'data' => $data,
                            'assistant_id' => $client['assistant_id']
                        ];

                        $taskId = $server->task($taskData);

                        Log::info("Message task queued", [
                            'chat_id' => $client['chat_id'],
                            'client_fd' => $frame->fd,
                            'task_id' => $taskId,
                            'task_data' => $taskData
                        ]);
                    } else {
                        Log::error("No chat context found for client", [
                            'fd' => $frame->fd,
                            'client' => $client
                        ]);
                    }
                }

            } catch (\Exception $e) {
                Log::error("Message handling error", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        });

        // Handle tasks (OpenAI connections)
        $this->server->on('Task', function ($server, $task) {
            try {
                $data = $task->data;
                Log::info("Processing task", [
                    'task_id' => $task->id,
                    'type' => $data['type'],
                    'chat_id' => $data['chat_id'],
                    'message_type' => $data['data']['type'] ?? 'none',
                    'data_length' => isset($data['data']['data']) ? strlen($data['data']['data']) : 0
                ]);

                if ($data['type'] === 'start_chat') {
                    $this->handleOpenAIConnection($server, $data['chat_id'], $data['client_fd'], $data['data']['assistant_id']);
                }
                else if ($data['type'] === 'relay_message') {
                    Log::info("Relaying message to OpenAI", [
                        'chat_id' => $data['chat_id'],
                        'client_fd' => $data['client_fd'],
                        'type' => $data['data']['type'] ?? 'none',
                        'data_length' => isset($data['data']['data']) ? strlen($data['data']['data']) : 0
                    ]);

                    $this->relayMessageToOpenAI($server, $data['chat_id'], $data['client_fd'], $data['data']);
                }

                return true;
            } catch (\Exception $e) {
                Log::error("Task error", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                return false;
            }
        });

        // Handle task finish
        $this->server->on('Finish', function (Server $server, $task_id, $data) {
            Log::info("Task finished", [
                'task_id' => $task_id,
                'result' => $data
            ]);
        });

        // Handle disconnection
        $this->server->on('Close', function (Server $server, $fd) {
            try {
                Log::info("Connection closed", ['fd' => $fd]);
                $this->connectionManager->handleClientDisconnect($server, $fd);
            } catch (\Exception $e) {
                Log::error("Disconnect error", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        });

        $this->info("WebSocket server starting on wss://richbot9000.local:9501");
        $this->server->start();
    }

    private function cleanConversationItem(array $item): ?array
    {
        try {
            // Skip items without required fields
            if (!isset($item['type']) || !isset($item['role'])) {
                Log::warning("Skipping invalid conversation item - missing type or role", [
                    'item' => $item
                ]);
                return null;
            }

            // Remove invalid fields
            unset($item['object']);
            unset($item['status']);
            unset($item['id']); // Remove id field as it's not needed
            
            // For assistant messages with empty content, create a default text content
            if ($item['role'] === 'assistant' && (empty($item['content']) || !is_array($item['content']))) {
                Log::debug("Creating default content for assistant message", [
                    'item_type' => $item['type']
                ]);
                $item['content'] = [
                    [
                        'type' => 'text',
                        'text' => ''
                    ]
                ];
                return $item;
            }
            
            // Clean and validate content array
            if (!isset($item['content']) || !is_array($item['content']) || empty($item['content'])) {
                Log::warning("Skipping invalid conversation item - invalid content array", [
                    'item' => $item
                ]);
                return null;
            }

            $validContent = [];
            foreach ($item['content'] as $content) {
                if (!isset($content['type'])) {
                    continue;
                }

                // Clean content item
                unset($content['object']);

                // Ensure text content has text field
                if ($content['type'] === 'input_text' || $content['type'] === 'text') {
                    if (!isset($content['text'])) {
                        $content['text'] = ''; // Set empty string for missing text
                    }
                    $validContent[] = $content;
                    continue;
                }

                // Ensure audio content has audio field
                if ($content['type'] === 'input_audio') {
                    if (!isset($content['audio']) || empty($content['audio'])) {
                        continue;
                    }
                    $validContent[] = $content;
                }
            }

            // Skip if no valid content items
            if (empty($validContent)) {
                Log::warning("Skipping conversation item - no valid content items", [
                    'item' => $item
                ]);
                return null;
            }

            $item['content'] = $validContent;
            return $item;
        } catch (\Exception $e) {
            Log::error("Error cleaning conversation item", [
                'error' => $e->getMessage(),
                'item' => $item
            ]);
            return null;
        }
    }

    private function handleOpenAIConnection(Server $server, string $chatId, int $clientFd, string $assistantId): void
    {
        try {
            Log::debug("Starting OpenAI connection", [
                'chat_id' => $chatId,
                'client_fd' => $clientFd,
                'assistant_id' => $assistantId
            ]);

            $maxRetries = 3;
            $retryCount = 0;
            $connected = false;

            // Get existing session state if any
            $sessionState = $this->connectionManager->getSessionState($chatId);
            $isNewSession = !$sessionState;

            while ($retryCount < $maxRetries && !$connected) {
                try {
                    // Check if there's an existing connection
                    $existingClient = $this->connectionManager->getOpenAIConnection($chatId);
                    if ($existingClient && $existingClient->connected) {
                        Log::warning("Found existing OpenAI connection, closing it first", [
                            'chat_id' => $chatId
                        ]);
                        $existingClient->close();
                        $this->connectionManager->removeOpenAIConnection($chatId);
                    }

                    $openaiClient = new \OpenSwoole\Coroutine\Http\Client(
                        'api.openai.com',
                        443,
                        true
                    );

                    Log::debug("Setting up OpenAI client headers");
                    $openaiClient->setHeaders([
                        'Authorization' => 'Bearer ' . config('services.openai.api_key'),
                        'OpenAI-Beta' => 'realtime=v1',
                        'Content-Type' => 'application/json'
                    ]);

                    $url = "/v1/realtime?model=gpt-4o-realtime-preview-2024-12-17";
                    if ($sessionState && isset($sessionState['session_id'])) {
                        $url .= "&session_id=" . $sessionState['session_id'];
                    }
                    
                    Log::debug("Attempting WebSocket upgrade", ['url' => $url]);
                    
                    if (!$openaiClient->upgrade($url)) {
                        throw new \Exception("WebSocket upgrade failed: " . $openaiClient->errMsg);
                    }

                    Log::info("Connected to OpenAI", [
                        'chat_id' => $chatId,
                        'is_new_session' => $isNewSession
                    ]);
                    $connected = true;

                    // Store connection before configuration
                    $this->connectionManager->storeOpenAIConnection($chatId, $openaiClient);

                    // Configure session
                    $sessionConfig = [
                        'type' => 'session.update',
                        'event_id' => uniqid('evt_'),
                        'session' => [
                            'turn_detection' => [
                                'type' => 'server_vad',
                                'threshold' => 0.5,
                                'prefix_padding_ms' => 300,
                                'silence_duration_ms' => 500,
                                'create_response' => true
                            ],
                            'input_audio_format' => 'g711_ulaw',
                            'output_audio_format' => 'g711_ulaw',
                            'voice' => 'alloy',
                            'instructions' => 'You are a helpful assistant.',
                            'modalities' => ['text', 'audio'],
                            'temperature' => 0.8,
                            'max_response_output_tokens' => 'inf'
                        ]
                    ];

                    Log::debug("Sending session config to OpenAI", [
                        'chat_id' => $chatId,
                        'config' => $sessionConfig
                    ]);

                    $result = $openaiClient->push(json_encode($sessionConfig));
                    if (!$result) {
                        throw new \Exception("Failed to send session config: " . $openaiClient->errMsg);
                    }

                    // Wait briefly for session to be configured
                    usleep(500000); // 500ms

                    // Restore conversation state if it exists
                    if ($sessionState && !empty($sessionState['conversation'])) {
                        foreach ($sessionState['conversation'] as $item) {
                            // Clean the item before sending
                            $cleanedItem = $this->cleanConversationItem($item);
                            
                            // Skip invalid items
                            if (!$cleanedItem) {
                                continue;
                            }
                            
                            $createItemEvent = [
                                'type' => 'conversation.item.create',
                                'event_id' => uniqid('evt_'),
                                'item' => $cleanedItem
                            ];
                            
                            Log::debug("Restoring conversation item", [
                                'chat_id' => $chatId,
                                'item_type' => $cleanedItem['type'] ?? 'unknown'
                            ]);

                            $result = $openaiClient->push(json_encode($createItemEvent));
                            if (!$result) {
                                throw new \Exception("Failed to restore conversation item: " . $openaiClient->errMsg);
                            }
                            usleep(100000); // 100ms delay between items
                        }
                    }

                    // Only send initial response.create if this is a new session
                    if ($isNewSession) {
                        $responseConfig = [
                            'type' => 'response.create',
                            'event_id' => uniqid('evt_'),
                            'response' => [
                                'modalities' => ['text', 'audio'],
                                'temperature' => 0.8
                            ]
                        ];

                        Log::debug("Sending initial response.create to OpenAI", [
                            'chat_id' => $chatId,
                            'is_new_session' => true
                        ]);

                        $result = $openaiClient->push(json_encode($responseConfig));
                        if (!$result) {
                            throw new \Exception("Failed to send initial response.create: " . $openaiClient->errMsg);
                        }

                        // Initialize session state for new sessions
                        $this->connectionManager->storeSessionState($chatId, [
                            'session_id' => uniqid('sess_'),
                            'conversation' => []
                        ]);
                    }

                    // Handle messages directly instead of spawning a coroutine
                    $this->handleOpenAIMessages($server, $chatId, $clientFd, $openaiClient);
                    return;

                } catch (\Exception $e) {
                    Log::error("OpenAI connection attempt failed", [
                        'chat_id' => $chatId,
                        'retry' => $retryCount + 1,
                        'error' => $e->getMessage()
                    ]);
                    
                    $retryCount++;
                    if ($retryCount < $maxRetries) {
                        usleep(1000000 * pow(2, $retryCount));
                    }
                }
            }

            if (!$connected) {
                throw new \Exception("Failed to establish OpenAI connection after {$maxRetries} attempts");
            }

        } catch (\Exception $e) {
            Log::error("OpenAI connection error", [
                'chat_id' => $chatId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function handleOpenAIMessages(Server $server, string $chatId, int $clientFd, $openaiClient): void
    {
        $lastPingTime = time();
        $pingInterval = 15;  // Keep 15 second ping interval
        $maxTimeouts = 5;    // Increase max timeouts since they're normal
        $timeoutCount = 0;
        $reconnectAttempts = 0;
        $maxReconnectAttempts = 3;
        $lastMessageTime = time();
        $idleTimeout = 60;   // Only consider reconnecting after 60 seconds of no messages

        while (true) {
            try {
                // Check web client connection first
                if (!$server->exists($clientFd)) {
                    Log::warning("Web client disconnected", [
                        'chat_id' => $chatId,
                        'client_fd' => $clientFd
                    ]);
                    throw new \Exception("Web client disconnected");
                }

                // Check if we need to send a ping
                if (time() - $lastPingTime >= $pingInterval) {
                    if (!$openaiClient->connected) {
                        Log::error("OpenAI connection lost before ping", [
                            'chat_id' => $chatId,
                            'openai_error' => $openaiClient->errCode,
                            'openai_error_msg' => $openaiClient->errMsg,
                            'openai_status' => $openaiClient->getStatusCode()
                        ]);
                        throw new \Exception("Connection lost, attempting to reconnect");
                    }
                    
                    $openaiClient->push('', WEBSOCKET_OPCODE_PING);
                    $lastPingTime = time();
                }

                $frame = $openaiClient->recv(1.0);
                
                if ($frame === false) {
                    $error = $openaiClient->errCode;
                    if ($error === 110) { // Connection timeout
                        $timeoutCount++;
                        $idleTime = time() - $lastMessageTime;
                        
                        if ($idleTime > $idleTimeout && $timeoutCount >= $maxTimeouts) {
                            throw new \Exception("Connection timed out after extended idle period");
                        }
                        
                        $openaiClient->push('', WEBSOCKET_OPCODE_PING);
                        $lastPingTime = time();
                        continue;
                    }
                    
                    throw new \Exception("Connection error: " . $openaiClient->errMsg);
                }

                // Reset counters on successful message
                $timeoutCount = 0;
                $reconnectAttempts = 0;
                $lastMessageTime = time();

                if ($frame === '') {
                    continue;
                }

                // Handle different frame types
                if (is_object($frame)) {
                    switch ($frame->opcode) {
                        case WEBSOCKET_OPCODE_PING:
                            $openaiClient->push('', WEBSOCKET_OPCODE_PONG);
                            continue 2;

                        case WEBSOCKET_OPCODE_PONG:
                            continue 2;

                        case WEBSOCKET_OPCODE_CLOSE:
                            if ($frame instanceof \OpenSwoole\WebSocket\CloseFrame) {
                                throw new \Exception("OpenAI closed connection: " . ($frame->reason ?? 'No reason given'));
                            }
                            continue 2;

                        case WEBSOCKET_OPCODE_TEXT:
                            $data = json_decode($frame->data, true);
                            if (!$data) {
                                continue 2;
                            }

                            // Update session state based on message type
                            switch ($data['type']) {
                                case 'conversation.item.created':
                                    if (isset($data['item'])) {
                                        $this->connectionManager->updateSessionState($chatId, [$data['item']]);
                                    }
                                    break;

                                case 'response.text.delta':
                                    if (isset($data['delta'])) {
                                        $clientMessage = [
                                            'type' => 'text',
                                            'content' => $data['delta'],
                                            'sender' => 'assistant'
                                        ];
                                        $server->push($clientFd, json_encode($clientMessage));
                                    }
                                    break;

                                case 'response.audio.delta':
                                    if (isset($data['delta'])) {
                                        $clientMessage = [
                                            'type' => 'audio',
                                            'data' => $data['delta'],
                                            'event_id' => $data['event_id'],
                                            'response_id' => $data['response_id'],
                                            'item_id' => $data['item_id']
                                        ];
                                        $server->push($clientFd, json_encode($clientMessage));
                                    }
                                    break;

                                case 'response.audio_transcript.done':
                                    if (isset($data['transcript'])) {
                                        $clientMessage = [
                                            'type' => 'text',
                                            'content' => $data['transcript'],
                                            'sender' => 'assistant'
                                        ];
                                        $server->push($clientFd, json_encode($clientMessage));
                                    }
                                    break;

                                case 'conversation.item.input_audio_transcription.completed':
                                    if (isset($data['transcript'])) {
                                        $clientMessage = [
                                            'type' => 'text',
                                            'content' => $data['transcript'],
                                            'sender' => 'user'
                                        ];
                                        $server->push($clientFd, json_encode($clientMessage));
                                    }
                                    break;

                                case 'error':
                                    Log::error("OpenAI sent error message", [
                                        'chat_id' => $chatId,
                                        'error' => $data['error'] ?? 'Unknown error'
                                    ]);
                                    $clientMessage = [
                                        'type' => 'error',
                                        'message' => $data['error']['message'] ?? 'Unknown error'
                                    ];
                                    $server->push($clientFd, json_encode($clientMessage));
                                    break;
                            }
                            break;
                    }
                }
            } catch (\Exception $e) {
                Log::error("OpenAI message handling error", [
                    'chat_id' => $chatId,
                    'error' => $e->getMessage(),
                    'reconnect_attempt' => $reconnectAttempts + 1,
                    'max_attempts' => $maxReconnectAttempts
                ]);

                // Try to reconnect if possible
                if ($reconnectAttempts < $maxReconnectAttempts) {
                    $reconnectAttempts++;
                    $timeoutCount = 0;
                    
                    try {
                        if ($openaiClient->connected) {
                            $openaiClient->close();
                        }

                        $client = $this->connectionManager->getClient($clientFd);
                        if (!$client || empty($client['assistant_id'])) {
                            throw new \Exception("Cannot reconnect: missing assistant_id");
                        }

                        usleep(1000000 * pow(2, $reconnectAttempts - 1));
                        $this->handleOpenAIConnection($server, $chatId, $clientFd, $client['assistant_id']);
                        
                        $openaiClient = $this->connectionManager->getOpenAIConnection($chatId);
                        if (!$openaiClient || !$openaiClient->connected) {
                            throw new \Exception("Failed to establish new OpenAI connection");
                        }

                        $lastPingTime = time();
                        continue;

                    } catch (\Exception $reconnectError) {
                        Log::error("Reconnection attempt failed", [
                            'chat_id' => $chatId,
                            'attempt' => $reconnectAttempts,
                            'error' => $reconnectError->getMessage()
                        ]);
                    }
                }

                if ($server->exists($clientFd)) {
                    $server->push($clientFd, json_encode([
                        'type' => 'error',
                        'message' => 'Lost connection to OpenAI assistant',
                        'chat_id' => $chatId
                    ]));
                }

                $this->connectionManager->removeOpenAIConnection($chatId);
                break;
            }
        }
    }

    private function relayMessageToOpenAI(Server $server, string $chatId, int $clientFd, array $data): void
    {
        try {
            // Check web client connection first
            if (!$server->exists($clientFd)) {
                throw new \Exception("Web client disconnected before relay");
            }

            Log::debug("Starting message relay to OpenAI", [
                'chat_id' => $chatId,
                'type' => $data['type'],
                'web_client_connected' => $server->exists($clientFd)
            ]);

            $connection = $this->connectionManager->getOpenAIConnection($chatId);
            
            // Log connection state
            Log::info("OpenAI connection state", [
                'chat_id' => $chatId,
                'connection_exists' => $connection !== null,
                'connected' => $connection ? $connection->connected : false,
                'status_code' => $connection ? $connection->getStatusCode() : 'none',
                'last_error' => $connection ? $connection->errCode : 'none'
            ]);

            // If no connection or connection is closed, try to reconnect
            if (!$connection || !$connection->connected) {
                Log::info("OpenAI connection not found or closed, attempting to reconnect", [
                    'chat_id' => $chatId,
                    'connection_status' => $connection ? 'disconnected' : 'null'
                ]);
                
                // Get client info for assistant_id
                $client = $this->connectionManager->getClient($clientFd);
                if (!$client || empty($client['assistant_id'])) {
                    throw new \Exception("Cannot reconnect: missing assistant_id");
                }
                
                // Start a new connection
                $this->handleOpenAIConnection($server, $chatId, $clientFd, $client['assistant_id']);
                
                // Get the new connection
                $connection = $this->connectionManager->getOpenAIConnection($chatId);
                if (!$connection || !$connection->connected) {
                    throw new \Exception("Failed to establish OpenAI connection");
                }
            }

            Log::debug("Using OpenAI connection", [
                'chat_id' => $chatId,
                'connection_status' => $connection->connected ? 'connected' : 'disconnected'
            ]);

            // Handle different types of messages
            if ($data['type'] === 'audio') {
                $event = [
                    'type' => 'input_audio_buffer.append',
                    'event_id' => uniqid('evt_'),
                    'audio' => $data['data']
                ];
            } else if ($data['type'] === 'commit_audio') {
                $event = [
                    'type' => 'input_audio_buffer.commit',
                    'event_id' => uniqid('evt_')
                ];
            } else if ($data['type'] === 'clear_audio') {
                $event = [
                    'type' => 'input_audio_buffer.clear',
                    'event_id' => uniqid('evt_')
                ];
            } else if ($data['type'] === 'text') {
                $event = [
                    'type' => 'conversation.item.create',
                    'event_id' => uniqid('evt_'),
                    'item' => [
                        'type' => 'message',
                        'role' => 'user',
                        'content' => [
                            [
                                'type' => 'input_text',
                                'text' => $data['content']
                            ]
                        ]
                    ]
                ];

                // Send the conversation item
                $result = $connection->push(json_encode($event));
                if ($result === false) {
                    throw new \Exception("Failed to send conversation item to OpenAI");
                }

                // Wait briefly for the item to be created
                usleep(100000); // 100ms

                // Then request a response
                $responseEvent = [
                    'type' => 'response.create',
                    'event_id' => uniqid('evt_'),
                    'response' => [
                        'modalities' => ['text', 'audio'],
                        'temperature' => 0.8
                    ]
                ];

                Log::debug("Sending response.create after text message", [
                    'chat_id' => $chatId,
                    'event_type' => 'response.create'
                ]);

                $result = $connection->push(json_encode($responseEvent));
                if ($result === false) {
                    throw new \Exception("Failed to send response.create to OpenAI");
                }
                
                return;
            }

            Log::debug("Sending event to OpenAI", [
                'chat_id' => $chatId,
                'event_type' => $event['type']
            ]);

            $result = $connection->push(json_encode($event));
            if ($result === false) {
                throw new \Exception("Failed to send event to OpenAI");
            }
        } catch (\Exception $e) {
            Log::error("Relay error", [
                'chat_id' => $chatId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Only notify client if connection still exists
            if ($server->exists($clientFd)) {
                $server->push($clientFd, json_encode([
                    'type' => 'error',
                    'message' => 'Failed to relay message to OpenAI: ' . $e->getMessage(),
                    'chat_id' => $chatId
                ]));
            }
        }
    }
} 