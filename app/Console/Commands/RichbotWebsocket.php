<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use OpenSwoole\WebSocket\Server;
use OpenSwoole\Table;
use Illuminate\Support\Facades\Log;

use App\Services\ConnectionManager;
use App\Services\Logging\OpenAILogger;
use Symfony\Component\Process\Process;
use App\Models\Conversation;
use App\Models\User;
use App\Models\Assistant;
use App\Models\Message;

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

        $this->clientTable = new Table(1024);
        $this->clientTable->column('type', Table::TYPE_STRING, 32);      //  'webclient' or 'twilio_phone'
        $this->clientTable->column('status', Table::TYPE_STRING, 32);
        $this->clientTable->column('stream_sid', Table::TYPE_STRING, 64);
        $this->clientTable->column('chat_id', Table::TYPE_STRING, 64);
        $this->clientTable->column('user_fd', Table::TYPE_INT);
        $this->clientTable->column('relay_fd', Table::TYPE_INT);
        $this->clientTable->column('assistant_id', Table::TYPE_INT);
        $this->clientTable->create();

        // Relay connections table - stores Ratchet client connections
        $this->relayTable = new Table(1024);        
        $this->relayTable->column('type', Table::TYPE_STRING, 32);      //  'webclient' or 'twilio_phone'
        $this->relayTable->column('status', Table::TYPE_STRING, 32);
        $this->relayTable->column('stream_sid', Table::TYPE_STRING, 64);
        $this->relayTable->column('user_fd', Table::TYPE_INT);
        $this->relayTable->column('relay_fd', Table::TYPE_INT);
        $this->relayTable->column('assistant_id', Table::TYPE_INT);
        $this->relayTable->column('last_activity', Table::TYPE_INT);
        $this->relayTable->column('pid', Table::TYPE_INT);
        $this->relayTable->create();

        // Forward table
        $this->forwardTable = new Table(1024);
        $this->forwardTable->column('source_fd', Table::TYPE_INT);
        $this->forwardTable->column('target_fd', Table::TYPE_INT);
        $this->forwardTable->column('chat_id', Table::TYPE_STRING, 64);
        $this->forwardTable->column('assistant_id', Table::TYPE_INT);
        $this->forwardTable->column('last_event_id', Table::TYPE_STRING, 64);
        $this->forwardTable->column('last_activity', Table::TYPE_INT);
        $this->forwardTable->column('stream_sid', Table::TYPE_STRING, 64);
        $this->forwardTable->column('message_count', Table::TYPE_INT);
        $this->forwardTable->column('status', Table::TYPE_STRING, 32);
        $this->forwardTable->create();
    }

    private function extractTwilioStatusInfo($uri)
    {
        // Pattern: /twilio/status
        if (preg_match('#^/twilio/status$#', $uri)) {
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

    private function extractTwilioInfo($uri)
    {
        // Add debug logging
        Log::info("Extracting Twilio info from URI", ['uri' => $uri]);
        
        // Pattern: /twilio/{callSid}/{assistant_id}
        if (preg_match('#^/twilio/([^/]+)/(\d+)$#', $uri, $matches)) {
            $info = [
                'type' => 'twilio_phone',
                'call_sid' => $matches[1],
                'assistant_id' => intval($matches[2])
            ];
            Log::info("Twilio info extracted", $info);
            return $info;
        }
        
        Log::info("No Twilio info matched");
        return null;
    }

    // Add helper methods for the forward table
    private function addForwardRoute($sourceFd, $targetFd, $chatId, $assistantId, $streamSid = null)
    {
        $this->forwardTable->set($sourceFd, [
            'source_fd' => $sourceFd,
            'target_fd' => $targetFd,
            'chat_id' => $chatId,
            'assistant_id' => $assistantId,
            'last_event_id' => '',
            'stream_sid' => $streamSid,
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

    private function handleTwilioConnection($server, $request, $info)
    {
        try {

            $this->info("Twilio Connection Info" . json_encode($info,JSON_PRETTY_PRINT));
            $this->info("Request" . json_encode($request,JSON_PRETTY_PRINT));
            
            $chatId     = $info['call_sid'];
            //$streamSid  = $info['call_sid'];

            $clientInfo = $this->clientTable->get($request->fd);
            //$clientInfo['stream_sid'] = $streamSid;
            $clientInfo['chat_id'] = $chatId;
            $clientInfo['assistant_id'] = $info['assistant_id'];
            $clientInfo['type'] = 'twilio_phone';
            $clientInfo['status'] = 'waiting';
            $clientInfo['user_fd'] = $request->fd;

            $this->clientTable->set($request->fd, $clientInfo);

            $relayInfo = $this->relayTable->get($chatId);
            $relayInfo['type'] = 'twilio_phone';
            $relayInfo['status'] = 'waiting';
            //$relayInfo['stream_sid'] = $streamSid;
            $relayInfo['user_fd'] = $request->fd;
            $relayInfo['assistant_id'] = $info['assistant_id'];
            $this->relayTable->set($chatId, $relayInfo);

                 
            //start the connection to openaiwebsocketrelay here instead of on connection.

            return $chatId;
        } catch (\Exception $e) {
            Log::error("Error handling Twilio connection", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }

    // And let's update the message handling to use the client table
    private function handleMessage(Server $server, $frame)
    {
        try {


            $data = json_decode($frame->data, true);

            

            $clientInfo = $this->clientTable->get($frame->fd);
            $chatId = $clientInfo['chat_id'];
            $relayInfo = $this->relayTable->get($chatId);
            $route = $this->forwardTable->get($frame->fd);

    
            Log::info("Client Info". json_encode($clientInfo,JSON_PRETTY_PRINT));
            Log::info("Relay Info". json_encode($relayInfo,JSON_PRETTY_PRINT));
            Log::info("Route". json_encode($route,JSON_PRETTY_PRINT));

            if (!$chatId) {
                Log::error("No chat ID or client info found", [
                    'client_info' => $clientInfo,
                    'route' => $route
                ]);
                return;
            }

            // Log message flow
            Log::channel('message_flow')->info("Message forwarded", [
                'direction' => $frame->fd === $clientInfo['user_fd'] ? 'to relay' : 'to client',
                'from_fd' => $frame->fd,
                'to_fd' => $route['target_fd'] ?? 'no target',
                'client_type' => $clientInfo['type'],
                'event'=> $data['event'] ?? null,
                'message_type' => is_array($data) ? ($data['type'] ?? 'unknown') : 'raw',
                'message' => print_r($data,true),
            ]);

            // Update activity timestamps
            $relayInfo['last_activity'] = time();
            $clientInfo['last_activity'] = time();
            $this->clientTable->set($frame->fd, $clientInfo);
            $this->relayTable->set($chatId, $relayInfo);
           

            if(isset($data['event']) && $data['event'] == 'start'){

                
                $streamSid = $data['streamSid'] ?? null;
                Log::info("Twilio Relay received start event", [
                    'stream_sid' => $data['streamSid'] ?? 'none',
                    'data' => $data,
                    'streamSid'=>$streamSid 
                ]);

                if($streamSid){

                    $clientInfo['stream_sid'] = $streamSid;
                    $relayInfo['stream_sid'] = $streamSid;

                    //start the connection to openaiwebsocktwilioetrelay here instead of on connection.
                    
                    $this->clientTable->set($frame->fd, $clientInfo);
                    $this->relayTable->set($chatId, $relayInfo);


                }


// Start the relay process
$artisanCommand = [
    'php',
    'artisan',
    'richbot:websocket-twilio-relay',
    $chatId,
    $streamSid,
    $clientInfo['assistant_id']
];

$process = new Process($artisanCommand);
$process->setTimeout(null);
$process->disableOutput();
$process->start();

// Update relay table with process ID
$this->relayTable->set($chatId, [
    'type' => 'twilio_phone',
    'status' => 'waiting',
    'user_fd' => $frame->fd,
    'relay_fd' => 0,
    'chat_id' => $chatId,
    'assistant_id' => $clientInfo['assistant_id'],
    'stream_sid' => $streamSid,
    'last_activity' => time(),
    'pid' => $process->getPid()
]);

Log::info("Twilio relay process started", [
    'pid' => $process->getPid(),
    'chat_id' => $chatId,
    'stream_sid' => $streamSid
]);



            }

            $this->clientTable->set($frame->fd, $clientInfo);
            $this->relayTable->set($chatId, $relayInfo);

            // Forward message if target exists
            if($route && $route['target_fd']){
                $server->push($route['target_fd'], $frame->data);
            } else {


                foreach($this->forwardTable as $id => $route){
                    Log::info("Forward Route for $id", $route);
                }

                foreach($this->relayTable as $id => $relay){
                    Log::info("Relay Route for $id", $relay);
                }

                foreach($this->clientTable as $id => $client){
                    Log::info("Client Route for $id", $client);
                }


                Log::error("No target found for message", [
                    'chat_id' => $chatId,
                    
                ]);
            }

        } catch (\Exception $e) {
            Log::error("Message handling error", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    private function handleTwilioStatusMessage($server, $request)
    {
        try {
            $data = json_decode(file_get_contents('php://input'), true);
            Log::info("Stream Status", $data);
            
            // Send 200 OK response
            $response = new \OpenSwoole\HTTP\Response();
            $response->status(200);
            $response->header('Content-Type', 'application/json');
            $response->end(json_encode(['status' => 'ok']));
            
            return true;
        } catch (\Exception $e) {
            Log::error("Error handling Twilio status", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    public function handle()
    {
        $this->info('Richbot WebSocket server started. V23.01.16');

        $this->server = new Server('0.0.0.0', 9501, SWOOLE_PROCESS, SWOOLE_SOCK_TCP | SWOOLE_SSL);
        
        // Configure SSL with more permissive settings
        $this->server->set([
            'ssl_cert_file' => config('app.ssl_cert_file'),
            'ssl_key_file' => config('app.ssl_key_file'),
            'ssl_verify_peer' => false,
            'ssl_allow_self_signed' => true,
            'worker_num' => 1,
            'daemonize' => false,
            'log_level' => SWOOLE_LOG_DEBUG,
            'log_file' => storage_path('logs/websocket.log'),
            'enable_coroutine' => true,
            'task_worker_num' => 4,
            'task_enable_coroutine' => true,
            'task_use_object' => true,
            'task_ipc_mode' => 1,
            'message_queue_key' => ftok(public_path('index.php'), 1)
        ]);

        $this->connectionManager->setServer($this->server);

        $this->server->on('Open', function (Server $server, $request) {
            try {
                $uri = $request->server['request_uri'];       

                // Check for Twilio connection
                $twilioInfo = $this->extractTwilioInfo($uri);
                if ($twilioInfo) {
                    $this->info("Twilio Info from Connection" . json_encode($twilioInfo,JSON_PRETTY_PRINT));
                    $chatId = $this->handleTwilioConnection($server, $request, $twilioInfo);
                    Log::info("Twilio connection established", [
                        'chat_id' => $chatId,
                        'info' => $twilioInfo,
                        'stream_sid' => $twilioInfo['call_sid']
                    ]);
                    return;
                } else {

                    Log::info("No Twilio Info found", ['uri' => $uri]);
                }

                $statusCheck = $this->extractStatusInfo($uri);

               
                if($statusCheck){
 Log::info("Status check", ['status' => $statusCheck ? $statusCheck : 'none']);

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

                    $this->addForwardRoute($request->fd, $linkData['user_fd'], $relayInfo['chat_id'], $linkData['assistant_id'], $relayInfo['chat_id']);
                    $this->addForwardRoute($linkData['user_fd'], $request->fd, $relayInfo['chat_id'], $linkData['assistant_id'], $relayInfo['chat_id']);

                    $this->clientTable->set($request->fd, [
                        'type' => 'openai-relay',
                        'status' => 'in_chat',
                        'chat_id' => $relayInfo['chat_id'],
                        'stream_sid' => $linkData['stream_sid'],
                        'user_fd' => $request->fd,
                        'assistant_id' => $linkData['assistant_id']
                    ]);

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

                    // Set up initial relay table entry
                    $this->relayTable->set($chatId, [
                        'user_fd' => $request->fd,
                        'assistant_id' => intval($assistantId),
                        'type' => 'openai',                        
                        'status' => 'waiting',                        
                        'last_activity' => time()
                    ]);

                    // Start relay process
                    $artisanCommand = [
                        'php',
                        'artisan',
                        'richbot:websocket-relay',
                        $chatId,
                        $assistantId,
                        '--client_type=webclient'
                    ];

                    $process = new Process($artisanCommand);
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

                    
                    $this->clientTable->set($request->fd, [
                        'type' => 'webclient',
                        'status' => 'waiting',
                        'chat_id' => $chatId,
                        'user_fd' => $request->fd,
                        'assistant_id' => intval($assistantId)
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
                $this->info("\n=== Richbot Status Check (10s) ===");

                // Get connected clients
                $connectedClients = $this->server->getClientList();

                if(!$connectedClients){
                    $this->info("No connected clients");
                    $connectedClients = [];
                }

                

                $connectedFds = array_flip($connectedClients);
                
                $this->info("Connected Clients: " . count($connectedClients));
                Log::info("Connected clients count", ['count' => count($connectedClients)]);

                // Check Forward Routes
                $routes = iterator_to_array($this->forwardTable);
                $this->info("Active Forward Routes: " . count($routes));
                
                foreach ($routes as $id => $route) {
                    $sourceConnected = isset($connectedFds[$route['source_fd']]);
                    $targetConnected = isset($connectedFds[$route['target_fd']]);

                    if (!$sourceConnected || !$targetConnected) {
                        $this->info("Cleaning up disconnected route: {$route['chat_id']}");
                        
                        // Clean up disconnected routes
                        if (!$sourceConnected && !$targetConnected) {
                            $this->forwardTable->del($id);
                        } elseif (!$sourceConnected) {
                            $this->server->disconnect($route['target_fd']);
                            $this->forwardTable->del($id);
                        } elseif (!$targetConnected) {
                            $this->server->disconnect($route['source_fd']);
                            $this->forwardTable->del($id);
                        }
                    }
                }

                // Check Relay Connections
                $relays = iterator_to_array($this->relayTable);
                $this->info("Active Relays: " . count($relays));
                
                foreach ($relays as $id => $relay) {
                    $userConnected = isset($connectedFds[$relay['user_fd']]);
                    $relayConnected = isset($relay['relay_fd']) && isset($connectedFds[$relay['relay_fd']]);
                    
                    // Check for stale connections (5 minutes without activity)
                    $inactiveMinutes = floor((time() - $relay['last_activity']) / 60);
                    if ($inactiveMinutes > 5) {
                        $this->info("Stale relay found: {$id} (inactive for {$inactiveMinutes} minutes)");
                    }

                    if (!$userConnected || !$relayConnected) {
                        $this->info("Cleaning up disconnected relay: {$id}");
                        
                        // Clean up disconnected relays
                        if (!$userConnected && !$relayConnected) {
                            $this->relayTable->del($id);
                        } elseif (!$userConnected) {
                            if ($relayConnected) {
                                $this->server->disconnect($relay['relay_fd']);
                            }
                            $this->relayTable->del($id);
                        } elseif (!$relayConnected && isset($relay['relay_fd'])) {
                            $this->server->disconnect($relay['user_fd']);
                            $this->relayTable->del($id);
                        }
                    }
                }

                // Check Client Table
                $clients = iterator_to_array($this->clientTable);
                $this->info("Active Clients: " . count($clients));
                
                foreach ($clients as $fd => $client) {
                    if (!isset($connectedFds[$fd])) {
                        $this->info("Removing disconnected client: {$fd} ({$client['type']})");
                        $this->clientTable->del($fd);
                    }
                }

                $this->info("=== Status Check Complete ===\n");

            } catch (\Exception $e) {
                $this->error("Timer error: " . $e->getMessage());
                Log::error("Status check error", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        });

        // Add timer setup right after server creation
        \OpenSwoole\Timer::tick(10000, function () {
            $this->info("\n=== Route Debug ===");
            
            foreach($this->forwardTable as $id => $route) {
                $this->info("Forward Route: {$id} -> {$route['target_fd']} (chat: {$route['chat_id']})");
            }
            
            foreach($this->relayTable as $chatId => $relay) {
                $this->info("Relay: {$chatId} -> user_fd: {$relay['user_fd']}, relay_fd: {$relay['relay_fd']}");
            }
        });

        // Handle messages
        $this->server->on('Message', function (Server $server, $frame) {
            $decodedData = json_decode($frame->data, true);
            $truncatedData = substr($frame->data, 0, 100) . (strlen($frame->data) > 100 ? '...' : '');
            
            $this->info(sprintf(
                "Message: [fd:%d] [op:%d] [flags:%d] Data: %s",
                $frame->fd,
                $frame->opcode,
                $frame->flags,
                $truncatedData
            ));
        
            $clientInfo = $this->clientTable->get($frame->fd);

            
            $this->handleMessage($server, $frame);


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

        // Add HTTP request handler for Twilio status updates
        $this->server->on('Request', function($request, $response) {
            $uri = $request->server['request_uri'];
            
            // Check if this is a Twilio status request
            if (preg_match('#^/twilio/status$#', $uri)) {
                $data = json_decode($request->rawContent(), true);
                Log::info("Stream Status", $data);
                
                $response->status(200);
                $response->header('Content-Type', 'application/json');
                $response->end(json_encode(['status' => 'ok']));
                return;
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
