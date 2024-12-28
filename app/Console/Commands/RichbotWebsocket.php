<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use OpenSwoole\WebSocket\Server;
use OpenSwoole\Table;
use Illuminate\Support\Facades\Log;
use App\Services\ConnectionManager;

use Symfony\Component\Process\Process;

class RichbotWebsocket extends Command
{
    protected $signature = 'richbot:websocket';
    protected $description = 'Start the Richbot WebSocket server';

    private ConnectionManager $connectionManager;
    
    private Server $server;
    private Table $relayTable;
    private Table $forwardTable;

    public function __construct(ConnectionManager $connectionManager)
    {
        parent::__construct();
        $this->connectionManager = $connectionManager;

        // Relay connections table - stores Ratchet client connections
        $this->relayTable = new Table(1024);
        
        $this->relayTable->column('type', Table::TYPE_STRING, 32);      // 'openai' or 'richbot'
        $this->relayTable->column('status', Table::TYPE_STRING, 32);
        
        $this->relayTable->column('user_fd', Table::TYPE_INT);
        $this->relayTable->column('relay_fd', Table::TYPE_INT);
        $this->relayTable->column('assistant_id', Table::TYPE_INT); // Changed to STRING type
        $this->relayTable->column('last_activity', Table::TYPE_INT);
        $this->relayTable->column('pid', Table::TYPE_INT);
        $this->relayTable->create();

        // New forward table
        $this->forwardTable = new Table(1024);
        $this->forwardTable->column('source_fd', Table::TYPE_INT);
        $this->forwardTable->column('target_fd', Table::TYPE_INT);
        $this->forwardTable->column('chat_id', Table::TYPE_STRING, 64);
        $this->forwardTable->column('assistant_id', Table::TYPE_INT);
        $this->forwardTable->column('last_event_id', Table::TYPE_STRING, 64);
        $this->forwardTable->column('last_activity', Table::TYPE_INT);
        $this->forwardTable->column('message_count', Table::TYPE_INT);
        $this->forwardTable->column('status', Table::TYPE_STRING, 32); // 'active', 'paused', 'closed'
        $this->forwardTable->create();
    }

    private function extractTwilioStatusInfo($uri)
    {
        if (preg_match('#^/twilio_status$#', $uri)) {
            return ['type' => 'twilio_status'];
        }
        return null;
    }

    private function extractStatusInfo($uri)
    {
        if (preg_match('#^/status_check$#', $uri)) {
            return ['type' => 'status_check'];
        }
        return null;
    }

    private function extractRelayInfo($uri)
    {
        if (preg_match('#^/relay/([^/]+)(?:/([^/]+))?$#', $uri, $matches)) {
            return [
                'chat_id' => $matches[1],
                'assistant_id' => $matches[2] ?? null
            ];
        }
        return null;
    }

    // Add helper methods for the forward table
    private function addForwardRoute($sourceFd, $targetFd, $chatId, $assistantId)
    {
        $this->forwardTable->set($sourceFd, [
            'source_fd' => $sourceFd,
            'target_fd' => $targetFd,
            'chat_id' => $chatId,
            'assistant_id' => $assistantId,
            'last_event_id' => '',
            'last_activity' => time(),
            'message_count' => 0,
            'status' => 'active'
        ]);
    }

    private function updateForwardActivity($sourceFd, $eventId = null)
    {
        if ($route = $this->forwardTable->get($sourceFd)) {
            $route['last_activity'] = time();
            $route['message_count']++;
            if ($eventId) {
                $route['last_event_id'] = $eventId;
            }
            $this->forwardTable->set($sourceFd, $route);
        }
    }

    private function removeForwardRoute($sourceFd)
    {
        $this->forwardTable->del($sourceFd);
    }

    private function getForwardTarget($sourceFd)
    {
        $route = $this->forwardTable->get($sourceFd);
        return $route ? $route['target_fd'] : null;
    }

    public function handle()
    {
        $this->info('Richbot WebSocket server started');

        $this->server = new Server('0.0.0.0', 9501, SWOOLE_PROCESS, SWOOLE_SOCK_TCP | SWOOLE_SSL);
        
        // Configure SSL with more permissive settings
        $this->server->set([
            'ssl_cert_file' => '/etc/ssl/certs/richbot9000.crt',
            'ssl_key_file' => '/etc/ssl/private/richbot9000.key',
            'ssl_verify_peer' => false,
            
            'ssl_allow_self_signed' => true,
       
            
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

        $this->connectionManager->setServer($this->server);

        $this->server->on('Open', function (Server $server, $request) {
            
            try {
                $uri = $request->server['request_uri'];       
                
                
                $twilioStatus = $this->extractTwilioStatusInfo($uri);


                $statusCheck = $this->extractStatusInfo($uri);
                Log::info("Status check", ['status' => $statusCheck ? $statusCheck : 'none']);
                if($statusCheck){

                    return;


                }
                
                // Check if this is a relay connection
                $relayInfo = $this->extractRelayInfo($uri);
                if ($relayInfo) {
                    Log::info("Relay connection detected", $relayInfo);
                    
                    // Get the link data from relay table
                    $linkData = $this->relayTable->get($relayInfo['chat_id']);
                    
                    if (!$linkData) {
                        Log::error("No link data found for chat", ['chat_id' => $relayInfo['chat_id']]);
                        $server->disconnect($request->fd, 1011, 'Invalid chat session');
                        return;
                    }

                    Log::info("Link data found", $linkData);

                    $linkData['relay_fd'] = $request->fd;
                    $linkData['status'] = 'in_chat';
                    $linkData['last_activity'] = time();
                    
                    $this->relayTable->set($relayInfo['chat_id'], $linkData);

                    $this->addForwardRoute($request->fd, $linkData['user_fd'], $relayInfo['chat_id'], $linkData['assistant_id']);
                    $this->addForwardRoute($linkData['user_fd'], $request->fd, $relayInfo['chat_id'], $linkData['assistant_id']);

                    return;
                }

                $this->info('On Open New Connection');

                Log::info("New WebSocket connection", [
                    'fd' => $request->fd,
                    'uri' => $request->server['request_uri']
                ]);

                // Authenticate user
                $token = $this->connectionManager->extractToken($request->server['request_uri']);

                Log::info("Extracted token", ['token' => $token]);

                $assistantId = $this->connectionManager->extractAssistantId($request->server['request_uri']);

                Log::info("Extracted assistant_id", ['assistant_id' => $assistantId]);


                $user = $this->connectionManager->authenticateUser($token);
                if (!$user) {
                    $server->disconnect($request->fd, 1008, 'Authentication failed');
                    return;
                }

                Log::info("Authenticated user", ['user' => $user]);

                // If assistant_id is present, automatically set up for chat
                if ($assistantId) {
                    $this->info("Assistant ID found, setting up for chat");
                    $chatId = uniqid('chat_', true);

                    $this->relayTable->set($chatId, [
                        'user_fd' => $request->fd,
                        'assistant_id' => intval($assistantId),
                        'type' => 'openai',                        
                        'status' => 'waiting',                        
                        'last_activity' => time()
                    ]);

                    $artisanCommand = [
                        'php',
                        'artisan',
                        'richbot:websocket-relay',
                        $chatId,
                        $assistantId
                    ];
                
                    $process = new Process($artisanCommand);
                    $process->setTimeout(null);
                    $process->disableOutput();
                    $process->start();
                    
                    Log::info("Background process started with PID: " . $process->getPid());

                    // Update relay table with process ID
                    $this->relayTable->set($chatId, [
                        'user_fd' => $request->fd,
                        'assistant_id' => intval($assistantId),
                        'type' => 'openai',
                        'status' => 'waiting',
                        'last_activity' => time(),
                        'pid' => $process->getPid()
                    ]);
                }


            } catch (\Exception $e) {
                Log::error("Connection error", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                $server->disconnect($request->fd, 1011, 'Server error');
            }
        });

        // Add timer setup right after server creation
    \OpenSwoole\Timer::tick(10000, function () {
        try {
            $this->info("\n=== Richbot Maintenance Check ===");

            // Get list of connected clients
            $connectedClients = $this->server->getClientList();
            if(!$connectedClients){
                $connectedClients = [];
            }
            $connectedFds = array_flip($connectedClients);

            Log::info("Connected clients", [
                'count' => count($connectedClients),
                'fds' => $connectedClients
            ]);

            // Check Forward Table for disconnected clients
            $activeRoutes = iterator_count($this->forwardTable);
            $routes = iterator_to_array($this->forwardTable);
            
            foreach ($routes as $id => $route) {
                $sourceConnected = isset($connectedFds[$route['source_fd']]);
                $targetConnected = isset($connectedFds[$route['target_fd']]);

                if (!$sourceConnected && !$targetConnected) {
                    // Both sides disconnected, remove the route
                    $this->forwardTable->del($id);
                    Log::info("Removed forward route - both sides disconnected", [
                        'route_id' => $id,
                        'source_fd' => $route['source_fd'],
                        'target_fd' => $route['target_fd'],
                        'chat_id' => $route['chat_id']
                    ]);
                } elseif (!$sourceConnected) {
                    // Source disconnected, kick target
                    $this->server->disconnect($route['target_fd'], 1001, 'Other party disconnected');
                    $this->forwardTable->del($id);
                    Log::info("Kicked target client - source disconnected", [
                        'route_id' => $id,
                        'target_fd' => $route['target_fd'],
                        'chat_id' => $route['chat_id']
                    ]);
                } elseif (!$targetConnected) {
                    // Target disconnected, kick source
                    $this->server->disconnect($route['source_fd'], 1001, 'Other party disconnected');
                    $this->forwardTable->del($id);
                    Log::info("Kicked source client - target disconnected", [
                        'route_id' => $id,
                        'source_fd' => $route['source_fd'],
                        'chat_id' => $route['chat_id']
                    ]);
                }
            }

            // Check Relay Table for disconnected clients
            $activeRelays = iterator_count($this->relayTable);
            $relays = iterator_to_array($this->relayTable);
            
            foreach ($relays as $id => $relay) {
                $userConnected = isset($connectedFds[$relay['user_fd']]);
                $relayConnected = isset($relay['relay_fd']) && isset($connectedFds[$relay['relay_fd']]);

                if (!$userConnected && !$relayConnected) {
                    // Both sides disconnected, remove the relay
                    $this->relayTable->del($id);
                    Log::info("Removed relay - both sides disconnected", [
                        'chat_id' => $id,
                        'user_fd' => $relay['user_fd'],
                        'relay_fd' => $relay['relay_fd'] ?? 'N/A'
                    ]);
                } elseif (!$userConnected) {
                    // User disconnected, kick relay
                    if ($relayConnected) {
                        $this->server->disconnect($relay['relay_fd'], 1001, 'User disconnected');
                    }
                    $this->relayTable->del($id);
                    Log::info("Kicked relay client - user disconnected", [
                        'chat_id' => $id,
                        'relay_fd' => $relay['relay_fd']
                    ]);
                } elseif (!$relayConnected && isset($relay['relay_fd'])) {
                    // Relay disconnected, kick user
                    $this->server->disconnect($relay['user_fd'], 1001, 'Relay disconnected');
                    $this->relayTable->del($id);
                    Log::info("Kicked user client - relay disconnected", [
                        'chat_id' => $id,
                        'user_fd' => $relay['user_fd']
                    ]);
                }
            }

            // Continue with existing status reporting...
            $this->info("\nForward Table Status:");
            $this->info("Active Routes: " . iterator_count($this->forwardTable));
            
            if ($routes) {
                $this->info("\nActive Routes Details:");
                foreach ($routes as $id => $route) {
                    $this->info(sprintf(
                        "  Route %s:\n    Source: %d\n    Target: %d\n    Chat: %s\n    Assistant: %d\n    Last Activity: %s",
                        $id,
                        $route['source_fd'],
                        $route['target_fd'],
                        $route['chat_id'],
                        $route['assistant_id'],
                        date('Y-m-d H:i:s', $route['last_activity'])
                    ));
                }
            }

            // Relay Table Status
            $activeRelays = iterator_count($this->relayTable);
            $relays = iterator_to_array($this->relayTable);
            
            $this->info("\nRelay Table Status:");
            $this->info("Active Relays: {$activeRelays}");
            
            if ($relays) {
                $this->info("\nActive Relays Details:");
                foreach ($relays as $id => $relay) {
                    $this->info(sprintf(
                        "  Relay %s:\n    Type: %s\n    Status: %s\n    User FD: %d\n    Relay FD: %d\n    Assistant: %d\n    Last Activity: %s",
                        $id,
                        $relay['type'],
                        $relay['status'],
                        $relay['user_fd'],
                        $relay['relay_fd'] ?? 'N/A',
                        $relay['assistant_id'],
                        date('Y-m-d H:i:s', $relay['last_activity'])
                    ));
                }
            }

            // Check for stale connections
            $staleFound = false;
            foreach ($this->relayTable as $id => $row) {
                if (time() - $row['last_activity'] > 300) {
                    if (!$staleFound) {
                        $this->info("\nStale Connections:");
                        $staleFound = true;
                    }
                    
                    $this->info(sprintf(
                        "  ID: %s\n  Last Activity: %s\n  Status: %s\n  Inactive for: %d minutes",
                        $id,
                        date('Y-m-d H:i:s', $row['last_activity']),
                        $row['status'],
                        floor((time() - $row['last_activity']) / 60)
                    ));
                }
            }

            if (!$staleFound) {
                $this->info("\nNo stale connections found.");
            }

            $this->info("\n=== End Maintenance Check ===\n");

            // Fix the Log::info call by providing proper array context
            Log::info("Richbot: Maintenance Check Summary", [
                'timestamp' => date('Y-m-d H:i:s'),
                'forward_routes' => [
                    'count' => $activeRoutes,
                    'routes' => array_map(function($route) {
                        return [
                            'source_fd' => $route['source_fd'],
                            'target_fd' => $route['target_fd'],
                            'chat_id' => $route['chat_id'],
                            'assistant_id' => $route['assistant_id'],
                            'last_activity' => date('Y-m-d H:i:s', $route['last_activity'])
                        ];
                    }, $routes)
                ],
                'relay_table' => [
                    'count' => $activeRelays,
                    'relays' => array_map(function($relay) {
                        return [
                            'type' => $relay['type'],
                            'status' => $relay['status'],
                            'user_fd' => $relay['user_fd'],
                            'relay_fd' => $relay['relay_fd'] ?? 'N/A',
                            'assistant_id' => $relay['assistant_id'],
                            'last_activity' => date('Y-m-d H:i:s', $relay['last_activity'])
                        ];
                    }, $relays)
                ]
            ]);

        } catch (\Exception $e) {
            $this->error("Timer error: " . $e->getMessage());
            Log::error("Richbot: Timer error", ['error' => $e->getMessage()]);
        }
    });


        // Handle messages
        $this->server->on('Message', function (Server $server, $frame) {
            try {
                $data = json_decode($frame->data, true);

                if($data['type'] === 'assistant_audio_delta'){
                    Log::info("Received audio delta", [
                        'fd' => $frame->fd,
                    ]);
                } else {
                    Log::info("Received message", [
                        'fd' => $frame->fd,
                        'data' => $data,
                    ]);
                }
               
                if (!$data) {
                    Log::error("Invalid JSON received", ['fd' => $frame->fd]);
                    return;
                }

                if($data['type'] === 'status_check'){

                    Log::info("Status check received", $data);
                     $websocketClientList = $this->server->getClientList();

                     $relayArray = [];
                     $forwardArray = [];
                     $relayTable = $this->relayTable;
                     $forwardTable = $this->forwardTable;     

                     foreach($relayTable as $id => $row){
                        $relayArray[$id] = $row;
                     }

                     foreach($forwardTable as $id => $row){
                        $forwardArray[$id] = $row;
                     }

                     Log::info("Relay table forwarded to client {$frame->fd}", $relayArray);
                     Log::info("Forward table forwarded to client {$frame->fd}", $forwardArray);

                    

                    Log::info("Sending client list", $websocketClientList);                   
                    $this->server->push($frame->fd, json_encode(['client_list' => $websocketClientList]));
                  
                    $this->server->push($frame->fd, json_encode(['relay_table' => $relayArray]));
                   
                    $this->server->push($frame->fd, json_encode(['forward_table' => $forwardArray]));
                    return;
                }

                // Handle start_chat event
                if ($data['type'] === 'start_chat') {
                    Log::info("Start chat event", $data);
                    
                    // Check if there's already a relay for this client
                    $existingRelay = null;
                    foreach ($this->relayTable as $id => $relay) {
                        if ($relay['user_fd'] === $frame->fd) {
                            $existingRelay = $relay;
                            break;
                        }
                    }

                    if ($existingRelay) {
                        Log::info("Relay already exists for client", [
                            'user_fd' => $frame->fd,
                            'relay_fd' => $existingRelay['relay_fd']
                        ]);
                        return;
                    }

                    $chatId = uniqid('chat_', true);
                    $assistantId = $data['assistant_id'];

                    // Store the initial connection in relay table
                    $this->relayTable->set($chatId, [
                        'user_fd' => $frame->fd,
                        'assistant_id' => intval($assistantId),
                        'type' => 'openai',
                        'status' => 'waiting',
                        'last_activity' => time()
                    ]);

                    // Start the relay process
                    $artisanCommand = [
                        'php',
                        'artisan',
                        'richbot:websocket-relay',
                        $chatId,
                        $assistantId
                    ];
                
                    $process = new Process($artisanCommand);
                    $process->setTimeout(null);
                    $process->disableOutput();
                    $process->start();
                    
                    Log::info("Background process started with PID: " . $process->getPid());

                    // Update relay table with process ID
                    $this->relayTable->set($chatId, [
                        'user_fd' => $frame->fd,
                        'assistant_id' => intval($assistantId),
                        'type' => 'openai',
                        'status' => 'waiting',
                        'last_activity' => time(),
                        'pid' => $process->getPid()
                    ]);

                    return;
                }
                
                // Forward other messages to appropriate target
                $targetFd = null;
                
                // First check forward routes
                foreach ($this->forwardTable as $id => $route) {
                    if ($route['source_fd'] == $frame->fd) {
                        $targetFd = $route['target_fd'];
                        break;
                    }
                }
                
                // If no forward route, check relay table
                if (!$targetFd) {
                    foreach ($this->relayTable as $chatId => $data) {
                        if ($data['user_fd'] == $frame->fd && isset($data['relay_fd'])) {
                            $targetFd = $data['relay_fd'];
                            break;
                        }
                    }
                }

                if ($targetFd) {
                    Log::info("Forwarding message", [
                        'from_fd' => $frame->fd,
                        'to_fd' => $targetFd
                    ]);
                    
                    $server->push($targetFd, $frame->data);
                } else {
                    Log::error("No chat context found for client", [
                        'fd' => $frame->fd,
                        'frame' => $frame
                    ]);
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
                    //$this->handleOpenAIConnection($server, $data['chat_id'], $data['client_fd'], $data['data']['assistant_id']);
                }
                else if ($data['type'] === 'relay_message') {
                    Log::info("Relaying message to OpenAI", [
                        'chat_id' => $data['chat_id'],
                        'client_fd' => $data['client_fd'],
                        'type' => $data['data']['type'] ?? 'none',
                        'data_length' => isset($data['data']['data']) ? strlen($data['data']['data']) : 0
                    ]);

                   // $this->relayMessageToOpenAI($server, $data['chat_id'], $data['client_fd'], $data['data']);
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
                $this->removeForwardRoute($fd);
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

/*
    OpenSwoole\WebSocket\Server->on('Start', fn)
    OpenSwoole\WebSocket\Server->on('Handshake, fn)
    OpenSwoole\WebSocket\Server->on('Open, fn)
    OpenSwoole\WebSocket\Server->on('Message, fn)
    OpenSwoole\WebSocket\Server->on('Request, fn)
    OpenSwoole\WebSocket\Server->on('Close, fn)


    OpenSwoole\WebSocket\Server::__construct()
    OpenSwoole\WebSocket\Server->start()
    OpenSwoole\WebSocket\Server->on()
    OpenSwoole\WebSocket\Server->push()
    OpenSwoole\WebSocket\Server->exist()
    OpenSwoole\WebSocket\Server->pack()
    OpenSwoole\WebSocket\Server->unpack()
    OpenSwoole\WebSocket\Server->disconnect()
    OpenSwoole\WebSocket\Server->isEstablished()
    OpenSwoole\WebSocket\Server->getClientInfo()
*/


}
