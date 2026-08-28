<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use OpenSwoole\WebSocket\Server;
use OpenSwoole\Table;
use Illuminate\Support\Facades\Log;

class WebRTCWebsocket extends Command
{
    protected $signature = 'webrtc:websocket {--daemon} {--get-rooms}';
    protected $description = 'Start the WebRTC WebSocket server for signaling';

    private Server $server;
    private Table $clientTable;
    private Table $roomTable;

    public function __construct()
    {
        parent::__construct();

        // Initialize client table
        $this->clientTable = new Table(1024);
        $this->clientTable->column('fd', Table::TYPE_INT);
        $this->clientTable->column('room_id', Table::TYPE_STRING, 64);
        $this->clientTable->column('last_activity', Table::TYPE_INT);
        $this->clientTable->column('user_id', Table::TYPE_STRING, 64); // Add user identification
        $this->clientTable->column('status', Table::TYPE_STRING, 32);  // Add client status tracking
        $this->clientTable->column('meta', Table::TYPE_STRING, 1024);  // Add metadata storage
        $this->clientTable->create();

        // Initialize room table with enhanced columns
        $this->roomTable = new Table(1024);
        $this->roomTable->column('participants', Table::TYPE_STRING, 1024); // JSON array of participant FDs
        $this->roomTable->column('created_at', Table::TYPE_INT);
        $this->roomTable->column('last_activity', Table::TYPE_INT);    // Track room activity
        $this->roomTable->column('status', Table::TYPE_STRING, 32);    // Room status (active, closing, etc.)
        $this->roomTable->column('owner_id', Table::TYPE_STRING, 64);  // Room owner identification
        $this->roomTable->column('settings', Table::TYPE_STRING, 1024); // Room settings as JSON
        $this->roomTable->column('max_participants', Table::TYPE_INT); // Maximum allowed participants
        $this->roomTable->create();
    }

    public function handle()
    {
        // Handle --get-rooms option
        if ($this->option('get-rooms')) {
            return $this->getRoomStatus();
        }

        $this->info('Starting WebRTC WebSocket server...');

        try {
            // Add signal handlers
            pcntl_async_signals(true); // Enable async signals
            pcntl_signal(SIGTERM, [$this, 'handleSignal']);
            pcntl_signal(SIGINT, [$this, 'handleSignal']);
            pcntl_signal(SIGHUP, [$this, 'handleSignal']);

            // Store PID file
            $pidFile = storage_path('logs/webrtc_websocket.pid');
            file_put_contents($pidFile, getmypid());

            // Add shutdown function
            register_shutdown_function(function () use ($pidFile) {
                $error = error_get_last();
                if ($error) {
                    Log::error('Server shutdown due to error', [
                        'error' => $error,
                        'memory_usage' => memory_get_usage(true),
                        'peak_memory' => memory_get_peak_usage(true)
                    ]);
                }
                
                // Clean up PID file
                if (file_exists($pidFile)) {
                    unlink($pidFile);
                }
                
                // Notify all clients about server shutdown
                if (isset($this->server) && $this->server->isEstablished()) {
                    $clients = $this->server->getClientList();
                    if ($clients) {
                        foreach ($clients as $fd) {
                            try {
                                $this->server->push($fd, json_encode([
                                    'type' => 'server_shutdown',
                                    'message' => 'Server is shutting down'
                                ]));
                            } catch (\Exception $e) {
                                // Ignore push errors during shutdown
                            }
                        }
                    }
                }

                Log::info('Server shutdown', [
                    'pid' => getmypid(),
                    'memory_usage' => memory_get_usage(true),
                    'peak_memory' => memory_get_peak_usage(true)
                ]);
            });

            $this->server = new Server('0.0.0.0', 9502, SWOOLE_PROCESS, SWOOLE_SOCK_TCP | SWOOLE_SSL);
            
            // Configure SSL
            $sslCertFile = config('app.ssl_cert_file');
            $sslKeyFile = config('app.ssl_key_file');

            $this->info("Checking SSL certificate files:");
            $this->info("Certificate file: " . $sslCertFile);
            $this->info("Private key file: " . $sslKeyFile);

            if (!file_exists($sslCertFile)) {
                $this->error("SSL certificate file not found!");
                return;
            }
            if (!file_exists($sslKeyFile)) {
                $this->error("SSL private key file not found!");
                return;
            }

            $this->info("SSL certificate files exist. Creating WebSocket server...");

            $this->server->set([
                'ssl_cert_file' => $sslCertFile,
                'ssl_key_file' => $sslKeyFile,
                'ssl_verify_peer' => false,
                'ssl_allow_self_signed' => true,
                'worker_num' => 1,
                'daemonize' => $this->option('daemon'),
                'log_level' => SWOOLE_LOG_DEBUG,
                'log_file' => storage_path('logs/webrtc_websocket.log'),
                'pid_file' => storage_path('logs/webrtc_websocket.pid'),
                'enable_coroutine' => true,
                'heartbeat_check_interval' => 60,
                'heartbeat_idle_time' => 120,
                'max_request' => 0,
                'buffer_output_size' => 32 * 1024 * 1024, // 32MB
                'socket_buffer_size' => 32 * 1024 * 1024, // 32MB
            ]);

            $this->info("Server configuration complete. Setting up event handlers...");

            // Add status check timer
            \OpenSwoole\Timer::tick(10000, function () {
                try {
                    $this->info("\n=== WebRTC Status Check (10s) ===");
                    
                    // Get connected clients
                    $connectedClients = $this->server->getClientList();
                    $clientCount = $connectedClients ? count($connectedClients) : 0;
                    
                    Log::info("WebRTC Status", [
                        'active_rooms' => count($this->roomTable),
                        'total_participants' => $this->getTotalParticipants(),
                        'connected_clients' => $clientCount
                    ]);

                    // Clean up inactive rooms
                    foreach ($this->roomTable as $roomId => $room) {
                        $participants = json_decode($room['participants'], true) ?? [];
                        if (empty($participants)) {
                            $this->roomTable->del($roomId);
                            Log::info("Removed empty room", ['room_id' => $roomId]);
                        }
                    }

                    // Clean up inactive clients
                    foreach ($this->clientTable as $fd => $client) {
                        if (!$this->server->exists($fd)) {
                            $this->clientTable->del($fd);
                            $this->handleLeaveRoom($this->server, $fd);
                            Log::info("Removed disconnected client", ['fd' => $fd]);
                        }
                    }

                    $this->info("=== Status Check Complete ===\n");
                } catch (\Exception $e) {
                    Log::error("Status check error", [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            });

            // Add debug timer
            \OpenSwoole\Timer::tick(30000, function () {
                try {
                    Log::debug("Memory usage", [
                        'current' => memory_get_usage(true),
                        'peak' => memory_get_peak_usage(true)
                    ]);
                } catch (\Exception $e) {
                    Log::error("Debug timer error", [
                        'error' => $e->getMessage()
                    ]);
                }
            });

            // Handle WebSocket connections
            $this->server->on('Start', function (Server $server) {
                $this->info("WebSocket server started successfully");
                Log::info("WebSocket server started", [
                    'pid' => getmypid(),
                    'port' => 9502
                ]);
            });

            $this->server->on('Open', function (Server $server, $request) {
                try {
                    $this->info("New WebSocket connection: {$request->fd}");
                    Log::info("New WebSocket connection", [
                        'fd' => $request->fd,
                        'headers' => $request->header ?? [],
                        'server' => $request->server ?? []
                    ]);
                    
                    // Store client info
                    $this->clientTable->set($request->fd, [
                        'fd' => $request->fd,
                        'room_id' => '',
                        'last_activity' => time()
                    ]);

                    // Send welcome message
                    $server->push($request->fd, json_encode([
                        'type' => 'welcome',
                        'message' => 'Connected to WebRTC signaling server'
                    ]));
                } catch (\Exception $e) {
                    Log::error("Connection error", [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    $server->disconnect($request->fd, 1011, 'Server error');
                }
            });

            // Handle disconnections with error logging
            $this->server->on('Close', function (Server $server, $fd, $reactorId) {
                try {
                    $this->info("Client disconnected: {$fd} (Reactor: {$reactorId})");
                    Log::info("Client disconnected", [
                        'fd' => $fd,
                        'reactor_id' => $reactorId
                    ]);
                    
                    $this->handleLeaveRoom($server, $fd);
                    $this->clientTable->del($fd);
                } catch (\Exception $e) {
                    Log::error("Disconnect error", [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                        'fd' => $fd
                    ]);
                }
            });

            // Handle messages
            $this->server->on('Message', function (Server $server, $frame) {
                try {
                    Log::info("Received message", [
                        'fd' => $frame->fd,
                        'data' => $frame->data
                    ]);

                    $data = json_decode($frame->data, true);
                    $clientInfo = $this->clientTable->get($frame->fd);

                    if (!$clientInfo) {
                        Log::error("No client info found for fd: {$frame->fd}");
                        return;
                    }

                    // Update last activity
                    $clientInfo['last_activity'] = time();
                    $this->clientTable->set($frame->fd, $clientInfo);

                    switch ($data['type'] ?? '') {
                        case 'join':
                            $this->handleJoinRoom($server, $frame->fd, $data);
                            break;
                        case 'leave':
                            $this->handleLeaveRoom($server, $frame->fd);
                            break;
                        case 'offer':
                        case 'answer':
                        case 'ice-candidate':
                            $this->handleSignalingMessage($server, $frame->fd, $data);
                            break;
                        default:
                            Log::warning("Unknown message type", ['type' => $data['type'] ?? 'unknown']);
                    }
                } catch (\Exception $e) {
                    Log::error("Message handling error", [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                        'fd' => $frame->fd,
                        'data' => $frame->data
                    ]);
                }
            });

            // Periodic cleanup of inactive clients
            $this->server->tick(60000, function () {
                $now = time();
                foreach ($this->clientTable as $fd => $client) {
                    if ($now - $client['last_activity'] > 300) { // 5 minutes
                        $this->handleLeaveRoom($this->server, $fd);
                        $this->clientTable->del($fd);
                        if ($this->server->exists($fd)) {
                            $this->server->disconnect($fd, 1000, 'Inactive timeout');
                        }
                    }
                }
            });

            $this->info("WebRTC WebSocket server starting on wss://".config('app.domain').":".config('app.ws_port_alt'));
            $this->server->start();

        } catch (\Exception $e) {
            $this->error("Failed to start WebSocket server: " . $e->getMessage());
            Log::error("WebSocket server startup error", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    private function handleJoinRoom(Server $server, int $fd, array $data)
    {
        try {
            $roomId = $data['room_id'] ?? '';
            $userId = $data['user_id'] ?? '';
            $settings = $data['settings'] ?? [];

            if (empty($roomId)) {
                $server->push($fd, json_encode([
                    'type' => 'error',
                    'message' => 'Room ID is required'
                ]));
                return;
            }

            // Get or create room with enhanced state management
            $room = $this->roomTable->get($roomId);
            $now = time();

            if (!$room) {
                // Create new room with full configuration
                $room = [
                    'participants' => json_encode([$fd]),
                    'created_at' => $now,
                    'last_activity' => $now,
                    'status' => 'active',
                    'owner_id' => $userId,
                    'settings' => json_encode($settings),
                    'max_participants' => $settings['max_participants'] ?? 10
                ];
            } else {
                // Validate room state before joining
                if ($room['status'] !== 'active') {
                    $server->push($fd, json_encode([
                        'type' => 'error',
                        'message' => 'Room is not active'
                    ]));
                    return;
                }

                $participants = json_decode($room['participants'], true) ?? [];
                
                // Check maximum participants
                if (count($participants) >= $room['max_participants']) {
                    $server->push($fd, json_encode([
                        'type' => 'error',
                        'message' => 'Room is full'
                    ]));
                    return;
                }

                // Add participant if not already in room
                if (!in_array($fd, $participants)) {
                    $participants[] = $fd;
                    $room['participants'] = json_encode($participants);
                    $room['last_activity'] = $now;
                }
            }

            // Update room state
            $this->roomTable->set($roomId, $room);

            // Update client state with enhanced information
            $clientInfo = [
                'fd' => $fd,
                'room_id' => $roomId,
                'last_activity' => $now,
                'user_id' => $userId,
                'status' => 'connected',
                'meta' => json_encode([
                    'joined_at' => $now,
                    'client_info' => $data['client_info'] ?? []
                ])
            ];
            $this->clientTable->set($fd, $clientInfo);

            Log::info("Client joined room", [
                'fd' => $fd,
                'room_id' => $roomId,
                'user_id' => $userId,
                'timestamp' => $now
            ]);

            // Notify room participants with enhanced information
            $participants = json_decode($room['participants'], true) ?? [];
            $participantInfo = [];

            // Gather information about all participants
            foreach ($participants as $participantFd) {
                $pInfo = $this->clientTable->get($participantFd);
                if ($pInfo) {
                    $participantInfo[] = [
                        'fd' => $participantFd,
                        'user_id' => $pInfo['user_id'],
                        'status' => $pInfo['status']
                    ];
                }
            }

            // Notify existing participants about the new peer
            foreach ($participants as $participantFd) {
                if ($participantFd !== $fd) {
                    $server->push($participantFd, json_encode([
                        'type' => 'peer-joined',
                        'room_id' => $roomId,
                        'peer' => [
                            'fd' => $fd,
                            'user_id' => $userId,
                            'status' => 'connected'
                        ]
                    ]));
                }
            }

            // Send room information to the joining client
            $server->push($fd, json_encode([
                'type' => 'joined',
                'room_id' => $roomId,
                'room' => [
                    'owner_id' => $room['owner_id'],
                    'created_at' => $room['created_at'],
                    'settings' => json_decode($room['settings'], true),
                    'participants' => $participantInfo
                ]
            ]));

        } catch (\Exception $e) {
            Log::error("Error in handleJoinRoom", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'fd' => $fd,
                'data' => $data
            ]);
            $server->push($fd, json_encode([
                'type' => 'error',
                'message' => 'Failed to join room: ' . $e->getMessage()
            ]));
        }
    }

    private function handleLeaveRoom(Server $server, int $fd)
    {
        try {
            $clientInfo = $this->clientTable->get($fd);
            if (!$clientInfo || empty($clientInfo['room_id'])) {
                return;
            }

            $roomId = $clientInfo['room_id'];
            $room = $this->roomTable->get($roomId);

            if ($room) {
                $participants = json_decode($room['participants'], true) ?? [];
                $participants = array_diff($participants, [$fd]);
                $now = time();
                
                if (empty($participants)) {
                    // Room cleanup when empty
                    $this->roomTable->del($roomId);
                    Log::info("Room deleted - no participants", ['room_id' => $roomId]);
                } else {
                    // Update room state
                    $room['participants'] = json_encode(array_values($participants));
                    $room['last_activity'] = $now;
                    
                    // Transfer ownership if owner leaves
                    if ($room['owner_id'] === $clientInfo['user_id']) {
                        $newOwner = $this->clientTable->get($participants[0]);
                        if ($newOwner) {
                            $room['owner_id'] = $newOwner['user_id'];
                            Log::info("Room ownership transferred", [
                                'room_id' => $roomId,
                                'new_owner' => $newOwner['user_id']
                            ]);
                        }
                    }
                    
                    $this->roomTable->set($roomId, $room);

                    // Notify remaining participants with enhanced information
                    foreach ($participants as $participantFd) {
                        $server->push($participantFd, json_encode([
                            'type' => 'peer-left',
                            'room_id' => $roomId,
                            'peer' => [
                                'fd' => $fd,
                                'user_id' => $clientInfo['user_id'],
                                'was_owner' => ($room['owner_id'] === $clientInfo['user_id'])
                            ],
                            'new_owner' => $room['owner_id']
                        ]));
                    }
                }
            }

            // Update client state
            $clientInfo['room_id'] = '';
            $clientInfo['status'] = 'disconnected';
            $clientInfo['last_activity'] = $now;
            $this->clientTable->set($fd, $clientInfo);

            Log::info("Client left room", [
                'fd' => $fd,
                'room_id' => $roomId,
                'user_id' => $clientInfo['user_id'],
                'timestamp' => $now
            ]);

        } catch (\Exception $e) {
            Log::error("Error in handleLeaveRoom", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'fd' => $fd
            ]);
        }
    }

    private function handleSignalingMessage(Server $server, int $fd, array $data)
    {
        try {
            $clientInfo = $this->clientTable->get($fd);
            if (empty($clientInfo['room_id'])) {
                $server->push($fd, json_encode([
                    'type' => 'error',
                    'message' => 'Not in a room'
                ]));
                return;
            }

            $room = $this->roomTable->get($clientInfo['room_id']);
            if (!$room) {
                $server->push($fd, json_encode([
                    'type' => 'error',
                    'message' => 'Room not found'
                ]));
                return;
            }

            $participants = json_decode($room['participants'], true) ?? [];
            $targetFd = $data['target_fd'] ?? null;

            if ($targetFd && in_array($targetFd, $participants)) {
                // Forward the signaling message with sender information
                $server->push($targetFd, json_encode([
                    'type' => $data['type'],
                    'room_id' => $clientInfo['room_id'],
                    'data' => $data['data'],
                    'sender_fd' => $fd,
                    'sender_id' => $clientInfo['user_id']
                ]));

                Log::info("Forwarded signaling message", [
                    'type' => $data['type'],
                    'from_fd' => $fd,
                    'to_fd' => $targetFd,
                    'room_id' => $clientInfo['room_id']
                ]);
            } else {
                $server->push($fd, json_encode([
                    'type' => 'error',
                    'message' => 'Invalid target peer'
                ]));
            }
        } catch (\Exception $e) {
            Log::error("Error in handleSignalingMessage", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'fd' => $fd,
                'data' => $data
            ]);
            $server->push($fd, json_encode([
                'type' => 'error',
                'message' => 'Failed to forward signaling message'
            ]));
        }
    }

    public function handleSignal($signo)
    {
        try {
            Log::warning('Received signal', [
                'signal' => $signo,
                'pid' => getmypid(),
                'memory_usage' => memory_get_usage(true)
            ]);

            switch ($signo) {
                case SIGTERM:
                case SIGINT:
                    Log::info('Received ' . ($signo === SIGTERM ? 'SIGTERM' : 'SIGINT') . ', shutting down gracefully');
                    if (isset($this->server) && $this->server->isEstablished()) {
                        // Notify all clients
                        $clients = $this->server->getClientList();
                        if ($clients) {
                            foreach ($clients as $fd) {
                                try {
                                    $this->server->push($fd, json_encode([
                                        'type' => 'server_shutdown',
                                        'message' => 'Server is shutting down'
                                    ]));
                                } catch (\Exception $e) {
                                    // Ignore push errors during shutdown
                                }
                            }
                        }
                        
                        // Stop the server
                        $this->server->shutdown();
                    }
                    exit(0);
                case SIGHUP:
                    Log::info('Received SIGHUP, reloading configuration');
                    // Handle configuration reload if needed
                    break;
            }
        } catch (\Exception $e) {
            Log::error('Error handling signal', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            exit(1);
        }
    }

    private function getTotalParticipants()
    {
        $total = 0;
        foreach ($this->roomTable as $room) {
            $participants = json_decode($room['participants'], true) ?? [];
            $total += count($participants);
        }
        return $total;
    }

    private function getRoomStatus()
    {
        $rooms = [];
        foreach ($this->roomTable as $roomId => $room) {
            $participants = json_decode($room['participants'], true) ?? [];
            $rooms[$roomId] = [
                'participants' => $participants,
                'created_at' => $room['created_at'],
                'last_activity' => $room['last_activity'],
                'status' => $room['status'],
                'owner_id' => $room['owner_id'],
                'settings' => json_decode($room['settings'], true) ?? [],
                'max_participants' => $room['max_participants']
            ];
        }
        $this->line(json_encode($rooms));
        return 0;
    }
} 