<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use OpenSwoole\WebSocket\Server;
use OpenSwoole\WebSocket\Frame;
use OpenSwoole\Table;
use Illuminate\Support\Facades\Log;
use App\Models\Assistant;
use Symfony\Component\Process\Process;
use App\Models\User;
use Laravel\Sanctum\PersonalAccessToken;
use App\Models\Conversation;

class BareWebsocketServer extends Command
{
    protected $signature = 'bare:server';
    protected $description = 'Start WebSocket server for handling Twilio calls and OpenAI integration';

    private Table $clientTable;
    private Table $roomTable;
    private array $openAiConnections = [];

    private bool $debug = false;

    public function __construct()
    {
        parent::__construct();
        
        // Initialize client table
        $this->clientTable = new Table(1024);
        $this->clientTable->column('device_id', Table::TYPE_STRING, 64);
        $this->clientTable->column('type', Table::TYPE_STRING, 32);
        $this->clientTable->column('monitor_fd', Table::TYPE_INT);
        $this->clientTable->column('room_id', Table::TYPE_STRING, 64);
        $this->clientTable->column('conversation_id', Table::TYPE_STRING, 64);
        $this->clientTable->column('user_id', Table::TYPE_INT);
        $this->clientTable->column('assistant_id', Table::TYPE_STRING, 64);
        $this->clientTable->column('monitor_assistant_id', Table::TYPE_INT);
        $this->clientTable->column('last_seen', Table::TYPE_INT);
        $this->clientTable->column('services', Table::TYPE_STRING, 1024);
        $this->clientTable->column('status', Table::TYPE_STRING, 32);
        $this->clientTable->column('stream_sid', Table::TYPE_STRING, 64);
        $this->clientTable->column('api_token', Table::TYPE_STRING, 64);
        $this->clientTable->create();

        // Initialize room table
        $this->roomTable = new Table(1024);
        $this->roomTable->column('name', Table::TYPE_STRING, 64);
        $this->roomTable->column('created_at', Table::TYPE_INT);
        $this->roomTable->column('last_activity', Table::TYPE_INT);
        $this->roomTable->column('clients', Table::TYPE_STRING, 1024);
        $this->roomTable->create();
    }

    public function handle()
    {
        $this->info("Starting Bare WebSocket Server...");
        $server = new Server('0.0.0.0', 9502, SWOOLE_PROCESS, SWOOLE_SOCK_TCP | SWOOLE_SSL);

        $this->configureSSL($server);
        $this->configureServer($server);

        // Log tables every 20 seconds
        \OpenSwoole\Timer::tick(20000, function() {
            $this->logTableContents();
        });

        // Clean up empty rooms every 30 seconds
        \OpenSwoole\Timer::tick(30000, function() {
            $this->cleanupEmptyRooms();
        });

        $server->start();
    }

    private function configureSSL(Server $server)
    {
        $this->info("Configuring SSL...");
        $server->set([
            'ssl_cert_file' => config('app.ssl_cert_file'),
            'ssl_key_file' => config('app.ssl_key_file'),
            'ssl_verify_peer' => false,
        ]);
    }

    private function configureServer(Server $server)
    {
        $server->on('start', function() {
            $this->info("[SERVER] Bare WebSocket server started on:");
            $this->info("  - Local: wss://richbot9000.local:9502");
            $this->info("  - Public: wss://".config('app.domain').":".config('app.ws_port_alt'));
        });

        $server->on('open', function($server, $request) {
            $this->handleOpen($server, $request);
        });

        $server->on('message', function($server, $frame) {
            $this->handleMessage($server, $frame);
        });

        $server->on('close', function($server, $fd) {
            $this->handleClose($server, $fd);
        });
    }

    private function handleOpen(Server $server, $request)
    {
        $path = $request->server['request_uri'];
        $fd = $request->fd;
        $apiToken = $request->get['token'] ?? null;
        $this->info("[SERVER] New connection from fd: {$fd}, path: {$path}");

        // Parse path to determine connection type
        $parts = explode('/', trim($path, '/'));
        
        if (count($parts) >= 1) {
            $type = $parts[0];
            
            
            
            switch ($type) {
                case 'twilio':
                case 'twilio-outbound':
                    $room = $parts[1] ?? null;
                    $callSid = $parts[2] ?? null;
                    $this->handleTwilioConnection($server, $fd, $room, $callSid);
                    break;
                case 'twilio-inbound':
                    Log::info("[SERVER] Twilio inbound connection **************************", [
                        'room' => $parts[1] ?? null,
                        'callSid' => $parts[2] ?? null,
                        'fd' => $fd,
                        'path' => $path
                    ]);

                    $room = $parts[1] ?? null;
                    $callSid = $parts[2] ?? null;
                    $this->handleTwilioConnection($server, $fd, $room, $callSid);
                    break;
                case 'openai':
                case 'openai-realtime':
                    $room = $parts[1] ?? null;
                    $this->handleOpenAIRealtimeConnection($server, $fd, $room);
                    break;
                case 'monitor':
                    $room = $parts[1] ?? null;
                    $this->handleMonitorConnection($server, $fd, $room);
                    break;
                case 'webclient':
                    $assistantId = $parts[1] ?? null;
                    $this->handleWebClientConnection($server, $fd, $assistantId, $apiToken);
                    break;
                case 'dashboard':
                case 'remote-richbot-manager':
                    $room = $parts[1] ?? null;
                    $this->handleDashboardConnection($server, $fd, $room);
                    break;
                default:
                    $this->error("[SERVER] Unknown connection type: {$type}");
                    $server->close($fd);
                    break;
            }
        } else {
            $this->error("[SERVER] Invalid path format");
            $server->close($fd);
        }
    }

    private function handleWebClientConnection(Server $server, $fd, $assistantId = null,  $apiToken = null)
    {
        $this->info("[SERVER] New webclient connection for assistant: {$assistantId}");

            $room = null;
            
            if($apiToken)
            {
                $accessToken = PersonalAccessToken::findToken($apiToken);
            
                if (!$accessToken) {
                    Log::warning('Token not found in database');
                    return false;
                }
                
                $user = $accessToken->tokenable;
                Log::info('User authenticated successfully', [
                    'user_id' => $user->id,
                    'user_name' => $user->name
                ]);

                if (!$user) {
                    Log::warning('Authentication failed', [
                        'fd' => $fd,
                        'token' => $apiToken   
                    ]);
                    $server->disconnect($fd, 1008, 'Authentication failed');
                    return;
                }
            } else {
                $user = null;
            }


          

            
       

            
        // Store client information
        $clientInfo = [
            'device_id' => 'webclient_' . $fd,
            'type' => 'webclient',
            'assistant_id' => $assistantId,
            'room_id' => $room,
            'api_token' => $apiToken,
            'user_id' => $user ? $user->id : null,
            'last_seen' => time(),
            'services' => json_encode(['audio']),
        ];

        $this->clientTable->set($fd, $clientInfo);


        if($room)
        {
            $this->joinRoom($server, $fd, $room);
        }

       


        if($assistantId)
        {

        
            $this->startAssistantChat($server, $fd, $assistantId,$user);   
          
            $this->info("[SERVER] Started assistant: {$assistantId}");
        }




    }

    private function addAssistantToRoom(Server $server, $fd, $assistantId, $room,$user = null,$audioOutput = null)
    {
        $this->info("[SERVER] Adding assistant: {$assistantId} to room: {$room}");


        $command = [];
        $command[] = '/usr/bin/screen';
        $command[] = '-dmS';  // Detached mode with session name
        $command[] = 'richbot_assistants';  // Single screen session for all assistants
        $command[] = '/usr/bin/php';
        $command[] = ''.config('app.base_path').'/artisan';
        $command[] = 'bare:assistant';
        $command[] = $room;
        $command[] = $assistantId;
        if($user)
        {
            $command[] = '--user_id='.$user->id;
        }
        if($audioOutput == 'pcm16')
        {
            $command[] = '--audio-output-pcm16';
        }
        
        Log::info("Command: ".implode(' ', $command));
        
        $username = 'unknown';
        Log::info("[SERVER] Starting assistant chat: {$assistantId} in room: {$room} for user: {$username}");
        
        $process = new Process($command);
        $process->start();

        
        
    }




    private function startAssistantChat(Server $server, $fd, $assistantId, $user = null, $voice_enabled = false)
    {

        $room = 'webclient_'.$fd.'_assistant_'.$assistantId;

        $this->joinRoom($server, $fd, $room);
          
        $command = ['/usr/bin/php', ''.config('app.base_path').'/artisan', 'bare:assistant', $room, $assistantId, '--audio-output-pcm16'];
        if($user)
        {
            $command[] = '--user_id='.$user->id;       
        }

        $username = $user ? $user->name : 'unknown';
        $this->info("[SERVER] Starting assistant chat: {$assistantId} in room: {$room} for user: {$username}");
            


        $process = new Process($command);
        $process->start();

       // $this->info("[SERVER] Started assistant chat: {$assistantId} in room: {$room}");
    }   

    private function joinRoom(Server $server, int $fd, string $room)
    {
        $clientInfo = $this->clientTable->get($fd);
        $clientInfo['room_id'] = $room;
        $this->clientTable->set($fd, $clientInfo);

        $this->info("[SERVER] Joining room: {$room}");

        $roomTable = $this->roomTable->get($room);
        $clients = [];

        //if room does not exist, create it
        if(!$roomTable)
        {
            $this->info("[SERVER] Room does not exist, creating it");

            $clients[$fd] = $fd;
            $this->roomTable->set($room, [
                'name' => $room,
                'created_at' => time(),
                'last_activity' => time(),
                'clients' => json_encode($clients)
            ]);

            $this->info("[SERVER] Room created: {$room}");
        } else {
            $this->info("[SERVER] Room exists, adding client to it");

            $clients = json_decode($roomTable['clients'], true);
            $clients[$fd] = $fd;
            $this->roomTable->set($room, [
                'name' => $room,
                'created_at' => $roomTable['created_at'],
                'last_activity' => time(),
                'clients' => json_encode($clients)
            ]);
        }

        // Log table contents
        $this->logTableContents();

        // Send joined message to the joining client
        $server->push($fd, json_encode([
            'type' => 'joined',
            'room' => $room,
            'members' => array_map(function($clientFd) {
                $info = $this->clientTable->get($clientFd);
                return [
                    'fd' => $clientFd,
                    'type' => $info['type'],
                    'device_id' => $info['device_id']
                ];
            }, array_keys($clients))
        ]));

        // Notify other clients in the room
        foreach($clients as $clientFd) {
            if($clientFd != $fd) {
                $server->push($clientFd, json_encode([
                    'type' => 'user_joined',
                    'room' => $room,
                    'user' => $fd,
                    'member_type' => $clientInfo['type'],
                    'member_name' => $clientInfo['device_id']
                ]));
            }
        }
    }

    private function logTableContents()
    {
        $timestamp = date('Y-m-d H:i:s');
        $output = "\n[SERVER] Table Contents at {$timestamp}\n";
        $output .= "==========================================\n\n";

        // Clients Section
        $output .= "Connected Clients:\n";
        $output .= "-----------------\n";
        foreach ($this->clientTable as $fd => $info) {
            $output .= sprintf(
                "Client FD: %d\n" .
                "  Device ID: %s\n" .
                "  Type: %s\n" .
                "  Room ID: %s\n" .
                "  User ID: %s\n" .
                "  Assistant ID: %s\n" .
                "  Last Seen: %s\n" .
                "  Services: %s\n" .
                "  Status: %s\n" .
                "  Stream SID: %s\n" .
                "  API Token: %s\n\n",
                $fd,
                $info['device_id'],
                $info['type'],
                $info['room_id'] ?? 'None',
                $info['user_id'] ?? 'None',
                $info['assistant_id'] ?? 'None',
                date('Y-m-d H:i:s', $info['last_seen']),
                implode(', ', json_decode($info['services'], true)),
                $info['status'],
                $info['stream_sid'] ?? 'None',
                $info['api_token'] ?? 'None'
            );
        }

        // Rooms Section
        $output .= "Active Rooms:\n";
        $output .= "------------\n";
        foreach ($this->roomTable as $roomId => $info) {
            $clients = json_decode($info['clients'], true);
            $output .= sprintf(
                "Room ID: %s\n" .
                "  Name: %s\n" .
                "  Created: %s\n" .
                "  Last Activity: %s\n" .
                "  Connected Clients: %s\n\n",
                $roomId,
                $info['name'],
                date('Y-m-d H:i:s', $info['created_at']),
                date('Y-m-d H:i:s', $info['last_activity']),
                implode(', ', array_keys($clients))
            );
        }

        $output .= "==========================================\n";
        
        // Log the formatted output
        Log::info($output);
    }

    private function handleTwilioConnection(Server $server, int $fd, string $room, string $callSid)
    {
        $this->info("[SERVER] New connection in room: {$room}");
        
        // Store client information
        $clientInfo = [ 
            'device_id' => 'twilio_inbound_' . $fd,
            'type' => 'twilio_inbound',
            'room_id' => $room,
            'last_seen' => time(),
            'services' => json_encode(['audio']),
            'status' => 'connected',
            'stream_sid' => $callSid    
        ];

        $conversation = Conversation::where('id', $room)->first();
        if($conversation){
            $clientInfo['conversation_id'] = $conversation->id;

            Log::info("[SERVER] Conversation found", [
                
                'path_state' => $conversation->path_state
            ]);

            if(isset($conversation->path_state['monitor_call'])){
                

                Log::info("[SERVER] Monitor call found #########################", [
                    'monitor_call' => $conversation->path_state['monitor_call']
                ]);

                $path_state = $conversation->path_state ?? [];
                $path_state['richbot_user']['fd'] = $fd;
                $path_state['monitor_call']['target_fd'] = $fd;
                $conversation->path_state = $path_state;
                $conversation->save();

                $monitor_call = $conversation->path_state['monitor_call'];
                           
                $clientInfo['monitor_assistant_id'] = $monitor_call['assistantId'];

                $command = [];
                $command[] = '/usr/bin/screen';
                $command[] = '-dmS';  // Detached mode with session name
                $command[] = 'richbot_assistants';  // Same screen session as assistants
                $command[] = '/usr/bin/php';
                $command[] = ''.config('app.base_path').'/artisan';
                $command[] = 'bare:monitor';
                $command[] = $conversation->id;

                Log::info("Monitor command: ".implode(' ', $command));
                
                $process = new Process($command);
                $process->start();

                Log::info("ConversationPathService: monitor process started", [
                    'conversation_id' => $conversation->id,
                    'command' => implode(' ', $command)
                ]);



               
            }
        }
        
        $this->clientTable->set($fd, $clientInfo);
        
        // Create or update room
        $this->roomTable->set($room, [  
            'name' => $room,
            'created_at' => time(),
            'last_activity' => time(),
            'clients' => json_encode([$fd => $fd])
        ]); 

        $this->info("[SERVER] Twilio inbound client joined room: {$room} with callSid: {$callSid}");


        $this->logTableContents();

    }
    

    private function handleTwilioOutboundConnection(Server $server, int $fd, string $room, string $callSid)
    {
        $this->info("[SERVER] New outbound connection in room: {$room}");
        
        // Store client information
        $clientInfo = [
            'device_id' => 'twilio_' . $fd,
            'type' => 'twilio_inbound',
            'room_id' => $room,
            'last_seen' => time(),
            'services' => json_encode(['audio']),
            'status' => 'connected',
            'stream_sid' => $callSid
        ];
        
        $this->clientTable->set($fd, $clientInfo);
        
        // Create or update room
        $this->roomTable->set($room, [
            'name' => $room,
            'created_at' => time(),
            'last_activity' => time()
        ]);
    }

    private function handleOpenAIRealtimeConnection(Server $server, int $fd, string $room)
    {
        $this->info("[SERVER] New realtime connection in room: {$room}");
        
        // Store client information
        $clientInfo = [
            'device_id' => 'openai_' . $fd,
            'type' => 'openai',
            'room_id' => $room,
            'last_seen' => time(),
            'services' => json_encode(['audio', 'text']),
            'status' => 'connected'
        ];
        
        $this->clientTable->set($fd, $clientInfo);

        // Join the room if provided
        if ($room) {
            $this->joinRoom($server, $fd, $room);
            $this->info("[SERVER] OpenAI client joined room: {$room}");
            $this->logTableContents();
        }
    }

    private function handleMonitorConnection(Server $server, int $fd, string $room)
    {
        $this->info("[SERVER] New monitor connection");
        
        // Store client information
        $clientInfo = [
            'device_id' => 'monitor_' . $fd,
            'type' => 'monitor',
            'room_id' => null, // Monitor doesn't join a room
            'last_seen' => time(),
            'services' => json_encode(['audio', 'text']),
            'status' => 'connected',
            'monitor_fd' => null // Monitor doesn't monitor itself
        ];
        
        $this->clientTable->set($fd, $clientInfo);

        $conversation = Conversation::where('id', $room)->first();
        if($conversation){
            $clientInfo['conversation_id'] = $conversation->id;


            if($conversation->path_state['monitor_call']){                
                
                $monitor_call = $conversation->path_state['monitor_call'];

                $clientInfo['monitor_assistant_id'] = (string)$monitor_call['assistantId'];
                $target_fd = $monitor_call['target_fd'];
                $target_info = $this->clientTable->get($target_fd);
                if($target_info){
                    $target_info['monitor_fd'] = $fd;
                    $this->clientTable->set($target_fd, $target_info);
                }


                $this->info("[SERVER] Monitor client {$fd} connected to monitor target {$target_fd}");

                if($monitor_call['startInteractive']){
                    $command = [];
                    $command[] = '/usr/bin/screen';
                    $command[] = '-dmS';  // Detached mode with session name
                    $command[] = 'richbot_assistants';  // Same screen session as others
                    $command[] = '/usr/bin/php';
                    $command[] = ''.config('app.base_path').'/artisan';
                    $command[] = 'bare:assistant';
                    $command[] = $target_info['room_id'];
                    $command[] = $monitor_call['assistantId'];

                    Log::info("Monitor assistant command: ".implode(' ', $command));
                    
                    $process = new Process($command);
                    $process->start();

                    Log::info("ConversationPathService: monitor assistant process started", [
                        'room_id' => $target_info['room_id'],
                        'assistant_id' => $monitor_call['assistantId'],
                        'command' => implode(' ', $command)
                    ]);
                }

            }
        }

       
    }

    private function handleDashboardConnection(Server $server, int $fd, string $token)
    {
        $this->info("[SERVER] New dashboard connection");
        
        // Store client information
        $clientInfo = [
            'device_id' => 'dashboard_' . $fd,
            'type' => 'dashboard',
            'room_id' => null, // Will be set when a call starts
            'last_seen' => time(),
            'services' => json_encode(['status', 'transcript', 'audio']),
            'status' => 'connected'
        ];
        
        $this->clientTable->set($fd, $clientInfo);
    }

    private function handleMessage(Server $server, Frame $frame)
    {
        try {
            $data = json_decode($frame->data, true);
            $fd = $frame->fd;
            
            // Get client info first
            $clientInfo = $this->clientTable->get($fd);

            // Update last seen if client exists
            if ($clientInfo) {
                $clientInfo['last_seen'] = time();
                $this->clientTable->set($fd, $clientInfo);

                // Forward message to monitor if one exists
                if (isset($clientInfo['monitor_fd'])) {
                    $monitorFd = $clientInfo['monitor_fd'];
                    $monitorInfo = $this->clientTable->get($monitorFd);
                    
                    if ($monitorInfo && $monitorInfo['type'] === 'monitor') {
                        // Add metadata to the message
                        $monitorMessage = array_merge($data, [
                            'monitored_fd' => $fd,
                            'monitored_type' => $clientInfo['type'],
                            'monitored_device_id' => $clientInfo['device_id']
                        ]);
                        
                        $server->push($monitorFd, json_encode($monitorMessage));
                    }
                }
            } else {
                Log::error("[SERVER] No client info found for fd", ['fd' => $fd]);
                return;
            }

            if($this->debug)
            {
                Log::info("[SERVER] Received message", [
                    'client_type' => $clientInfo['type'] ?? 'unknown',
                    'room' => $clientInfo['room_id'] ?? null,
                    'event' => $data['event'] ?? null,
                    'message_type' => $data['type'] ?? $data['event'] ?? 'unknown',
                    'has_media' => isset($data['media']) || isset($data['data']) || isset($data['delta'])
                ]);
            }

            // Handle clear message
            if (isset($data['event']) && $data['event'] === 'clear') {
                $this->handleClearMessage($server, $fd, $data);
                return;
            }

            if(isset($data['type']) && $data['type'] === 'end_call')
            {
                Log::info("[SERVER] End call received", [
                    'room' => $clientInfo['room_id'] ?? null,
                    'timestamp' => date('Y-m-d H:i:s.u')
                ]);

                $this->handleEndCall($server, $fd);
                return;
            }

            // Skip logging for Twilio inbound media messages
            if (isset($data['client_type']) && $data['client_type'] === 'twilio_inbound' && 
                isset($data['message_type']) && $data['message_type'] === 'media') {
                // Handle the message but don't log it
                $this->handleFromTwilioMediaMessage($server, $fd, $data);
                return;
            }

            // Handle JSON messages
            if (!$data) {
                $this->error("[SERVER] Invalid JSON format");
                return;
            }

            // Log all other incoming messages for debugging
           // Log::info("[SERVER] Received message", [
           //     'client_type' => $clientInfo['type'] ?? 'unknown',
           //     'room' => $clientInfo['room_id'] ?? null,
           //     'message_type' => $data['type'] ?? $data['event'] ?? 'unknown',
           //     'has_media' => isset($data['media']) || isset($data['data']) || isset($data['delta'])
           // ]);


          // Log::info("[SERVER] Received message", [
          //  'client_type' => $clientInfo['type'] ?? 'unknown',
          
        //]);


        if(isset($data['event']) && $data['event'] === 'dtmf')
        {

            $digit = $data['dtmf']['digit'] ?? null;

            Log::info("[SERVER] DTMF received", [
                'room' => $clientInfo['room_id'] ?? null,
                'timestamp' => date('Y-m-d H:i:s.u'),
                'dtmf' => $data['dtmf'] ?? null,
                'digit' => $digit
            ]);
        }

        if (isset($data['event']) && $data['event'] === 'mark') {
            Log::info("[SERVER] Mark received", [
                'room' => $clientInfo['room_id'] ?? null,
                'timestamp' => date('Y-m-d H:i:s.u'),
                'mark' => $data['mark']['name'] ?? null
            ]);
            Log::info("[BARE SERVER] Mark received, ************** ************** *************forwarding to room members", [
                'mark' => $data['mark']['name'] ?? null,
                'room' => $clientInfo['room_id'],
                'timestamp' => date('Y-m-d H:i:s.u')
            ]);
            
            // Forward mark to all clients in the room
            foreach ($this->clientTable as $clientFd => $info) {
                if ($info['room_id'] === $clientInfo['room_id'] && $clientFd !== $fd) {
                    $server->push($clientFd, json_encode($data));
                }
            }
            return;
        }
            // Handle media messages first
            if (isset($data['event']) && $data['event'] === 'media') {
                if ($clientInfo['type'] === 'twilio_inbound' || $clientInfo['type'] === 'twilio') {
                    if($this->debug)
                    {
                        Log::info("[AUDIO FLOW] Twilio → Server", [
                            'room' => $clientInfo['room_id'] ?? null,
                            'timestamp' => date('Y-m-d H:i:s.u'),
                            'fd' => $fd,
                            'event' => $data['event'],                        
                            'payload_size' => strlen($data['media']['payload'] ?? '')
                        ]);
                    }
                    $this->handleFromTwilioMediaMessage($server, $fd, $data);
                } else if ($clientInfo['type'] === 'openai') {
                    $this->handleFromOpenaiMediaMessage($server, $fd, $data);
                }
                return;
            }

            if($clientInfo['type'] === 'openai')
            {
                $this->handleFromOpenaiMessage($server, $fd, $data);
                return;
            }

            if($clientInfo['type'] === 'webclient')
            {
                $this->handleFromWebClientMessage($server, $fd, $data);
                
            }

            $type = $data['type'] ?? null;
            if (!$type && isset($data['event'])) {            
                $type = $data['event'];
                $data['type'] = $type;
            }

            if (!$type) {
                $this->error("[SERVER] Missing message type or event");
                return;
            }

            // Log message for debugging
           // Log::debug("[SERVER] Processing message type: {$type}", [
           //     'room' => $clientInfo['room_id'] ?? null,
           //     'message' => array_keys($data)
          //  ]);

            switch ($type) {
                case 'message':

                    Log::info("[SERVER] Message received from webclient", [
                        'room' => $clientInfo['room_id'] ?? null,
                        'message' => $data
                    ]);

                    foreach ($this->clientTable as $clientFd => $info) {

                        if($clientFd == $fd )
                        {
                            continue;
                        }

                        if($info['type'] == 'openai')
                        {
                            Log::info("[SERVER] Pushing message to openai", [
                                'room' => $clientInfo['room_id'] ?? null,
                                'from_fd' => $fd,
                                'to_fd' => $clientFd,
                                'to_info' => $info,
                                'message' => $data
                            ]);
                            $server->push($clientFd, json_encode($data));
                        }
                        
                    }


                    
                    break;
                case 'media':
                    $this->handleMediaMessage($server, $fd, $data);
                    break;
                case 'media_data':
                    $this->handleMediaDataMessage($server, $fd, $data);
                    break;
                case 'request_server_data':
                case 'get_all_clients':
                    $this->handleRequestServerData($server, $fd);
                    break;
                case 'response.audio.delta':
                    $this->handleAudioDelta($server, $fd, $data);
                    break;
                case 'input_audio_buffer.append':
                    $this->handleInputAudioBufferAppend($server, $fd, $data);
                    break;
                case 'conversation.item.create':
                    $this->handleConversationItemCreate($server, $fd, $data);
                    break;
                case 'response.text.delta':
                    $this->handleTextDelta($server, $fd, $data);
                    break;
                case 'response.text.done':
                    $this->handleTextDone($server, $fd, $data);
                    break;
                case 'response.audio.done':
                    $this->handleAudioDone($server, $fd, $data);
                    break;
                case 'transcript_delta':
                    $this->handleTranscriptDelta($server, $fd, $data);
                    break;
                case 'transcript_complete':
                    $this->handleTranscriptComplete($server, $fd, $data);
                    break;
                case 'start_call':
                    $this->handleStartCall($server, $fd, $data);
                    break;
                case 'end_call':

                    Log::info('[SERVER] End call received', [
                        'room' => $clientInfo['room_id'] ?? null,
                        'timestamp' => date('Y-m-d H:i:s.u')
                    ]);

                    $this->handleEndCall($server, $fd);
                    break;
                case 'mute_call':
                    $this->handleMuteCall($server, $fd, $data);
                    break;
                case 'ping':
                    $this->handlePing($server, $fd);
                    break;
                case 'response.create':
                    Log::info("[SERVER] Response create", [
                        'room' => $clientInfo['room_id'] ?? null,
                        'timestamp' => date('Y-m-d H:i:s.u')
                    ]);
                    // Forward mark to all clients in the room
                    foreach ($this->clientTable as $clientFd => $info) {
                        if ($info['room_id'] === $clientInfo['room_id'] && $clientFd !== $fd) {
                            $server->push($clientFd, json_encode($data));
                        }
                    }
                    break;
                case 'rate_limits.updated':
                    $this->handleRateLimitsUpdated($server, $fd, $data);
                    break;
                case 'response.output_item.added':
                    $this->handleResponseOutputItemAdded($server, $fd, $data);
                    break;
                case 'conversation.item.created':
                    $this->handleConversationItemCreated($server, $fd, $data);
                    break;
                case 'response.content_part.added':
                    $this->handleResponseContentPartAdded($server, $fd, $data);
                    break;
                case 'response.content_part.done':
                    $this->handleResponseContentPartDone($server, $fd, $data);
                    break;
                case 'response.output_item.done':
                    $this->handleResponseOutputItemDone($server, $fd, $data);
                    break;
                case 'session.created':
                    $this->handleSessionCreated($server, $fd, $data);
                    break;
                case 'session.update':
                    Log::info("[SERVER] Session update", [
                        'room' => $clientInfo['room_id'] ?? null,
                        'timestamp' => date('Y-m-d H:i:s.u')
                    ]);
                    // Forward mark to all clients in the room
                    foreach ($this->clientTable as $clientFd => $info) {
                        if ($info['room_id'] === $clientInfo['room_id'] && $clientFd !== $fd) {
                            $server->push($clientFd, json_encode($data));
                        }
                    }
                    break;
                case 'session.updated':
                    $this->handleSessionUpdated($server, $fd, $data);
                    break;
                case 'dtmf':
                    Log::info("[BARE SERVER] DTMF received, forwarding to room members", [
                        'dtmf' => $data['dtmf'] ?? null,
                        'room' => $clientInfo['room_id'],
                        'timestamp' => date('Y-m-d H:i:s.u')
                    ]);
                    
                    // Forward mark to all clients in the room
                    foreach ($this->clientTable as $clientFd => $info) {
                        if ($info['room_id'] === $clientInfo['room_id'] && $clientFd !== $fd) {
                            $server->push($clientFd, json_encode($data));
                        }
                    }
                    break;
                case 'mark':
                    Log::info("[BARE SERVER] Mark received, forwarding to room members", [
                        'mark' => $data['mark']['name'] ?? null,
                        'room' => $clientInfo['room_id'],
                        'timestamp' => date('Y-m-d H:i:s.u')
                    ]);
                    
                    // Forward mark to all clients in the room
                    foreach ($this->clientTable as $clientFd => $info) {
                        if ($info['room_id'] === $clientInfo['room_id'] && $clientFd !== $fd) {
                            $server->push($clientFd, json_encode($data));
                        }
                    }
                    break;
                default:
                    Log::debug("[SERVER] Unhandled message type: {$type}", [
                        'room' => $clientInfo['room_id'] ?? null,
                        'message' => $data
                    ]);
            }
        } catch (\Exception $e) {
            $this->error("[SERVER] Error handling message: " . $e->getMessage());
            Log::error("[SERVER] Error handling message", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'message' => $frame->data
            ]);
        }
    }

    private function handleFromWebClientMessage(Server $server, int $fd, array $data)
    {
        Log::info("[SERVER] Received WebClient message***********", [
            'timestamp' => date('Y-m-d H:i:s.u'),
            'fd' => $fd,
            'data' => array_keys($data)
        ]);

        $clientInfo = $this->clientTable->get($fd);
        if(!$clientInfo)
        {
            Log::error("[SERVER] No client info found for WebClient message");
            return;
        }

        $user_id = $clientInfo['user_id'];

        switch($data['type'])
        {

            case 'add_assistant_to_room':
                
                $this->info("[SERVER] Adding assistant to room");
                $assistantId = $data['assistant_id'];
                $room = $data['room'];

                if($user_id){
                    $user = User::find($user_id);
                    if($clientInfo['type'] == 'webclient')
                    {
                        $this->addAssistantToRoom($server, $fd, $assistantId, $room, $user, 'pcm16');
                    }
                    else{
                        $this->addAssistantToRoom($server, $fd, $assistantId, $room, $user);
                    }
                }
                else{ 
                    if($clientInfo['type'] == 'webclient')
                    {
                        $this->addAssistantToRoom($server, $fd, $assistantId, $room, null, 'pcm16');
                    }
                    else{
                        $this->addAssistantToRoom($server, $fd, $assistantId, $room, null);
                    }
                }


                return true;
                break;



            case 'start_assistant_chat':
                $assistantId = $data['assistant_id'];
                $voice_enabled = $data['voice_enabled'];

                $user = User::find($user_id);

                $this->startAssistantChat($server, $fd, $assistantId, $user, $voice_enabled);

                $room = 'webclient_'.$fd.'_assistant_'.$assistantId;

                //send a message to the client to let them know the assistant has been started
                $server->push($fd, json_encode([
                    'type' => 'assistant_chat_started',
                    'assistant_id' => $assistantId,
                    'voice_enabled' => $voice_enabled,
                    'room' => $room,
                ]));
               
                return true;
                break;
            case 'join':
               
                return true;
                break;
            case 'message':
               
                return true;
                break;
        }


        return false;

    }   
    
    private function handleFromOpenaiMessage(Server $server, int $fd, array $data)
    {
        Log::info("[SERVER] Received OpenAI message***********", [
            'timestamp' => date('Y-m-d H:i:s.u'),
            'fd' => $fd,
            'type' => $data['type'],
            'data' => array_keys($data)
        ]);

        $clientInfo = $this->clientTable->get($fd);
        if (!$clientInfo) {
            Log::error("[SERVER] No client info found for OpenAI message");
            return;
        }

        $room = $clientInfo['room_id'];
        if (!$room) {
            Log::error("[SERVER] No room found for OpenAI message");
            return;
        }

        $this->roomTable->set($room, [
            'last_activity' => time()
        ]);

        foreach ($this->clientTable as $clientFd => $info) {
            if($clientFd == $fd)
            {
                continue;
            }

            if ($info['type'] === 'webclient' && $info['room_id'] === $room) {
                $server->push($clientFd, json_encode($data));
                break;
            }
        }

        return;

    }
    
    private function handleFromTwilioMediaMessage(Server $server, int $fd, array $data)
    {
        if($this->debug)
        {
            Log::info("[SERVER] handleFromTwilioMediaMessage", [
                'stream_sid' => $data['streamSid'] ?? null,
                'payload_size' => isset($data['media']['payload']) ? strlen($data['media']['payload']) : 0,
                'data' => $data
            ]);
        }

        if (!isset($data['streamSid']) || !isset($data['media']['payload'])) {
            Log::error("[SERVER] Invalid Twilio media message format", [
                'data' => $data
            ]);
            return;
        }

        $streamSid = $data['streamSid'];

        $clientInfo = $this->clientTable->get($fd);
        if (!$clientInfo) {
            Log::error("[SERVER] No client info found for fd", ['fd' => $fd]);
            return;
        }

        // Log incoming Twilio message
        $this->logTwilioIncoming($streamSid, $data);

        // Add proper validation for stream_sid
        if (!isset($clientInfo['stream_sid'])) {
            $clientInfo['stream_sid'] = null;
        }

        if($streamSid != $clientInfo['stream_sid']) {
            $clientInfo['stream_sid'] = $streamSid;
            $this->clientTable->set($fd, $clientInfo);
            Log::info("[SERVER] Stream SID updated for Twilio client", [
                'stream_sid' => $streamSid,
                'client_info' => $clientInfo
            ]);
        }
        
        $room = $clientInfo['room_id'];
        if (!$room) {
            Log::error("[SERVER] No room ID found for client", ['fd' => $fd]);
            return;
        }

        // First, try to route to other Twilio clients in the same room
        $twilioClientFound = false;
        foreach ($this->clientTable as $clientFd => $info) {
            if ($clientFd != $fd && 
                ($info['type'] === 'twilio' || $info['type'] === 'twilio_inbound') && 
                $info['room_id'] === $room) {
                
                if($this->debug)
                {
                    Log::info("[AUDIO FLOW] Twilio → Twilio", [
                        'room' => $room,
                        'bytes' => strlen($data['media']['payload']),
                        'from_fd' => $fd,
                        'to_fd' => $clientFd,
                        'stream_sid' => $info['stream_sid']
                    ]);
                }

                $mediaMessage = [
                    'event' => 'media',
                    'streamSid' => $info['stream_sid'],
                    'media' => [
                        'payload' => $data['media']['payload']
                    ]
                ];
                
                $server->push($clientFd, json_encode($mediaMessage));
                $twilioClientFound = true;
            }
        }
        
        // If no Twilio clients found, route to OpenAI client
        if (!$twilioClientFound) {
            foreach ($this->clientTable as $clientFd => $info) {
                if ($info['type'] === 'openai' && $info['room_id'] === $room) {
                    if($this->debug)
                    {
                        Log::info("[AUDIO FLOW] Twilio → OpenAI", [
                            'room' => $room,
                            'bytes' => strlen($data['media']['payload']),
                            'from_fd' => $fd,
                            'to_fd' => $clientFd
                        ]);
                    }

                    try {
                        $mediaMessage = [
                            'type' => 'input_audio_buffer.append',
                            'audio' => $data['media']['payload'],
                            'sequence_id' => uniqid(),
                            'timestamp' => time() * 1000,
                            'format' => [
                                'type' => 'g711_ulaw',
                                'sample_rate' => 8000,
                                'channels' => 1
                            ]
                        ];
                        
                        $success = $server->push($clientFd, json_encode($mediaMessage));
                        
                        if (!$success) {
                            Log::error("[SERVER] Failed to push audio to OpenAI client", [
                                'fd' => $clientFd,
                                'room' => $room
                            ]);
                        }
                    } catch (\Exception $e) {
                        Log::error("[SERVER] Error processing Twilio media message", [
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString()
                        ]);
                    }
                    break;
                }
            }
        }
    }

    private function validateTwilioMessage($message) {
        if (!is_array($message)) {
            Log::error("[TWILIO VALIDATION] Message is not an array");
            return false;
        }

        // Required fields
        if (!isset($message['event']) || $message['event'] !== 'media') {
            Log::error("[TWILIO VALIDATION] Missing or invalid event field");
            return false;
        }

        if (!isset($message['streamSid']) || !is_string($message['streamSid'])) {
            //Log::error("[TWILIO VALIDATION] Missing or invalid streamSid");
            return false;
        }

        if (!isset($message['media']) || !is_array($message['media'])) {
            Log::error("[TWILIO VALIDATION] Missing or invalid media object");
            return false;
        }

        if (!isset($message['media']['payload']) || !is_string($message['media']['payload'])) {
            Log::error("[TWILIO VALIDATION] Missing or invalid media payload");
            return false;
        }

        // Validate streamSid format (should be "CA" followed by 32 hex chars)
        if (!preg_match('/^CA[a-f0-9]{32}$/', $message['streamSid'])) {
            Log::error("[TWILIO VALIDATION] Invalid streamSid format", [
                'streamSid' => $message['streamSid']
            ]);
            return false;
        }

        return true;
    }

    private function handleFromOpenaiMediaMessage(Server $server, int $fd, array $data)
    {
        $clientInfo = $this->clientTable->get($fd);
        if (!$clientInfo) {
            Log::error("[SERVER] No client info found for OpenAI media message");
            return;
        }

        $room = $clientInfo['room_id'];
        if (!$room) {
            Log::error("[SERVER] No room found for OpenAI media message");
            return;
        }

        $twilioClientFound = false;
        foreach ($this->clientTable as $clientFd => $info) {
            if (($info['type'] === 'twilio' || $info['type'] === 'twilio_inbound') && $info['room_id'] === $room) {
                $twilioClientFound = true;
                if($this->debug)
                {
                    Log::info("[AUDIO FLOW] OpenAI → Twilio", [
                        'room' => $room,
                        'bytes' => strlen($data['media']['payload']),
                        'from_fd' => $fd,
                        'to_fd' => $clientFd,
                        'stream_sid' => $info['stream_sid']
                    ]);
                }

                $mediaMessage = [
                    'event' => 'media',
                    'streamSid' => $info['stream_sid'],
                    'media' => [
                        'payload' => $data['media']['payload']
                    ]
                ];

                // Validate message before sending
                if (!$this->validateTwilioMessage($mediaMessage)) {
                    //Log::error("[TWILIO] Message validation failed", [
                    //    'stream_sid' => $info['stream_sid']
                    //]);
                  //  return;
                }

                // Log exact message structure before encoding
                if($this->debug)
                {
                    Log::info("[TWILIO DEBUG] Pre-encode message structure:", [
                        'message' => $mediaMessage,
                        'stream_sid' => $info['stream_sid'],
                        'has_payload' => !empty($data['media']['payload']),
                        'payload_length' => strlen($data['media']['payload'])
                    ]);
                }

                $encodedMessage = json_encode($mediaMessage);
                
                // Log the exact JSON string being sent
                if($this->debug)
                {
                    Log::info("[TWILIO DEBUG] Encoded message:", [
                        'json' => $encodedMessage,
                        'json_length' => strlen($encodedMessage),
                        'json_last_error' => json_last_error(),
                        'json_last_error_msg' => json_last_error_msg()
                    ]);
                }

                // Log outgoing Twilio message
                $this->logTwilioOutgoing($info['stream_sid'], $mediaMessage);

                $result = $server->push($clientFd, $encodedMessage);
                
                if ($result === false) {
                    if($this->debug)
                    {
                        Log::error("[AUDIO FLOW] Failed to send media to Twilio client", [
                            'room' => $room,
                            'client_fd' => $clientFd,
                            'stream_sid' => $info['stream_sid']
                        ]);
                    }
                }
                break;
            }
        }

        if (!$twilioClientFound) {
            if($this->debug)
            {   
                Log::error("[AUDIO FLOW] No Twilio client found in room", [
                    'room' => $room,
                    'openai_client_fd' => $fd
                ]);
            }
        }
    }

    private function logTwilioIncoming(string $streamSid, array $data)
    {
        $timestamp = date('Y-m-d_H:i:s.u');
        $logFile = storage_path('logs/twilio_incoming.log');
        
        $logData = [
            'timestamp' => $timestamp,
            'stream_sid' => $streamSid,
            'payload_size' => strlen($data['media']['payload'] ?? ''),
            'event' => $data['event'] ?? 'unknown',
            'has_payload' => !empty($data['media']['payload'])
        ];

        file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND);
    }

    private function logTwilioOutgoing(string $streamSid, array $data)
    {
        $timestamp = date('Y-m-d_H:i:s.u');
        $logFile = storage_path('logs/twilio_outgoing.log');
        
        $logData = [
            'timestamp' => $timestamp,
            'stream_sid' => $streamSid,
            'payload_size' => strlen($data['media']['payload'] ?? ''),
            'event' => $data['event'] ?? 'unknown',
            'has_payload' => !empty($data['media']['payload'])
        ];

        file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND);
    }

    private function handleMediaDataMessage(Server $server, int $fd, array $data)
    {
        $clientInfo = $this->clientTable->get($fd);
        if (!$clientInfo) {
            return;
        }

        $room = $clientInfo['room_id'];
        if (!$room) {
            return;
        }

        // Only handle media_data from OpenAI source
        if (isset($data['source']) && $data['source'] === 'openai') {
            foreach ($this->clientTable as $clientFd => $info) {
                if ($info['type'] === 'twilio' && $info['room_id'] === $room) {
                    if($this->debug)
                    {
                        Log::info("[AUDIO FLOW] OpenAI → Twilio", [
                            'room' => $room,
                            'bytes' => strlen($data['data']),
                            'from_fd' => $fd,
                            'to_fd' => $clientFd
                        ]);
                    }

                    $mediaMessage = [
                        'event' => 'media',
                        'streamSid' => $info['stream_sid'],
                        'media' => [
                            'payload' => $data['data']
                        ]
                    ];
                    
                    $server->push($clientFd, json_encode($mediaMessage));
                    break;
                }
            }
        }
    }

    private function handleTranscriptDelta(Server $server, int $fd, array $data)
    {
        $clientInfo = $this->clientTable->get($fd);
        if (!$clientInfo || $clientInfo['type'] !== 'openai') {
            return;
        }

        $room = $clientInfo['room_id'];
        if (!$room) {
            return;
        }

        // Forward to Twilio client
        foreach ($this->clientTable as $clientFd => $info) {
            if ($info['type'] === 'twilio' && $info['room_id'] === $room) {
                $textMessage = [
                    'type' => 'message',
                    'message' => $data['delta'] ?? ''
                ];
                $server->push($clientFd, json_encode($textMessage));
                break;
            }
        }

        // Forward to dashboard
        foreach ($this->clientTable as $clientFd => $info) {
            if ($info['type'] === 'dashboard' && $info['room_id'] === $room) {
                $server->push($clientFd, json_encode([
                    'type' => 'transcript_delta',
                    'delta' => $data['delta'] ?? ''
                ]));
                break;
            }
        }
    }

    private function handleTranscriptComplete(Server $server, int $fd, array $data)
    {
        $clientInfo = $this->clientTable->get($fd);
        if (!$clientInfo || $clientInfo['type'] !== 'openai') {
            return;
        }

        // Find Twilio client in the same room
        foreach ($this->clientTable as $clientFd => $info) {
            if ($info['type'] === 'twilio' && $info['room_id'] === $clientInfo['room_id']) {
                $textMessage = [
                    'type' => 'message',
                    'message' => $data['transcript'] ?? ''
                ];
                
                $server->push($clientFd, json_encode($textMessage));
                break;
            }
        }
    }

    private function handleConversationItemCreate(Server $server, int $fd, array $data)
    {
        $clientInfo = $this->clientTable->get($fd);
        if (!$clientInfo || $clientInfo['type'] !== 'openai') {
            return;
        }

        if (isset($data['item']) && $data['item']['type'] === 'text') {
            // Find Twilio client in the same room
            foreach ($this->clientTable as $clientFd => $info) {
                if ($info['type'] === 'twilio' && $info['room_id'] === $clientInfo['room_id']) {
                    $textMessage = [
                        'type' => 'message',
                        'message' => $data['item']['text']
                    ];
                    
                    $server->push($clientFd, json_encode($textMessage));
                    break;
                }
            }
        }
    }

    private function handleMediaMessage(Server $server, int $fd, array $data)
    {
        $clientInfo = $this->clientTable->get($fd);
        if (!$clientInfo || $clientInfo['type'] !== 'twilio') {
            Log::debug("[SERVER] Media message from non-Twilio client: " . json_encode($data));
            return;
        }

        if($clientInfo['stream_sid'] != false && $data['streamSid'] == false)
        {
            $data['streamSid'] = $clientInfo['stream_sid'];            
        }


        // Find OpenAI client in the same room
        foreach ($this->clientTable as $clientFd => $info) {

            if ($info['room_id'] === $clientInfo['room_id'] && $clientInfo['device_id'] != $info['device_id']) {
                if($this->debug)
                {
                    Log::debug("[SERVER] Twilio Media -> OpenAI: " . strlen($data['media']['payload']) . " bytes", [
                        'room' => $clientInfo['room_id'],
                        'from_fd' => $fd,
                        'to_fd' => $clientFd
                    ]);
                }
                
                $server->push($clientFd, json_encode($data));
                break;
            }
        }
    }

    private function handleAudioDelta(Server $server, int $fd, array $data)
    {
        if($this->debug)
        {
            Log::info("[SERVER] Processing OpenAI audio delta", [
                'data' => $data,
                'timestamp' => date('Y-m-d H:i:s.u'),
                'fd' => $fd
            ]);
        }

        $clientInfo = $this->clientTable->get($fd);
        if (!$clientInfo || $clientInfo['type'] !== 'openai') {
            Log::error("[SERVER] Invalid client for audio delta", [
                'client_type' => $clientInfo['type'] ?? 'unknown',
                'fd' => $fd,
                'client_info' => $clientInfo
            ]);
            return;
        }

        $room = $clientInfo['room_id'];
        if (!$room) {
            Log::error("[SERVER] No room found for audio delta", [
                'fd' => $fd,
                'client_info' => $clientInfo
            ]);
            return;
        }

        // Find Twilio client in the same room
        foreach ($this->clientTable as $clientFd => $info) {
            if ($info['type'] === 'twilio' && $info['room_id'] === $room) {

                if($this->debug)
                {
                    Log::info("[AUDIO FLOW] OpenAI Delta → Twilio", [
                        'room' => $room,
                        'bytes' => strlen($data['delta']),
                        'from_fd' => $fd,
                        'to_fd' => $clientFd,
                        'stream_sid' => $info['stream_sid'],
                    'timestamp' => date('Y-m-d H:i:s.u'),
                    'client_info' => [
                        'from' => $clientInfo,
                        'to' => $info
                        ]
                    ]);
                }

                $mediaMessage = [
                    'event' => 'media',
                    'streamSid' => $info['stream_sid'],
                    'media' => [
                        'payload' => $data['delta']
                    ]
                ];
                
                $result = $server->push($clientFd, json_encode($mediaMessage));
                
                if($this->debug)
                {
                    Log::info("[AUDIO FLOW] Media message sent to Twilio", [
                        'success' => $result !== false,
                        'room' => $room,
                    'bytes' => strlen($data['delta']),
                    'stream_sid' => $info['stream_sid'],
                        'timestamp' => date('Y-m-d H:i:s.u')
                    ]);
                }
                
                break;
            }
        }
    }

    private function handleTextDelta(Server $server, int $fd, array $data)
    {
        // Handle text delta if needed
        Log::info("[SERVER] Delta received: " . ($data['delta'] ?? ''));
    }

    private function handleTextDone(Server $server, int $fd, array $data)
    {
        // Handle text completion if needed
        Log::info("[SERVER] Text completion received: " . ($data['text'] ?? ''));
    }

    private function handleAudioDone(Server $server, int $fd, array $data)
    {
        // Handle audio completion if needed
        Log::info("[SERVER] Audio completion received");
    }

    private function handlePing(Server $server, int $fd)
    {
        $server->push($fd, json_encode([
            'type' => 'pong',
            'time' => time()
        ]));
    }

    private function handleClose(Server $server, int $fd)
    {
        $clientInfo = $this->clientTable->get($fd);
        if ($clientInfo) {
            $this->info("[SERVER] Client disconnected: {$clientInfo['device_id']}");
            
            // If this client has a monitor, close the monitor connection
            if (isset($clientInfo['monitor_fd']) && $clientInfo['monitor_fd']) {
                $monitorFd = $clientInfo['monitor_fd'];
                $monitorInfo = $this->clientTable->get($monitorFd);
                
                if ($monitorInfo) {
                    Log::info("[SERVER] Closing monitor connection", [
                        'monitor_fd' => $monitorFd,
                        'monitored_client' => $clientInfo['device_id']
                    ]);
                    
                    // Close the monitor connection
                    $server->close($monitorFd);
                    
                    // Update conversation path state if this was a monitored call
                    if (isset($clientInfo['conversation_id'])) {
                        $conversation = \App\Models\Conversation::find($clientInfo['conversation_id']);
                        if ($conversation && isset($conversation->path_state['monitor_call'])) {
                            $pathState = $conversation->path_state;
                            unset($pathState['monitor_call']);
                            $conversation->path_state = $pathState;
                            $conversation->save();
                            
                            Log::info("[SERVER] Updated conversation path state after monitor disconnect", [
                                'conversation_id' => $clientInfo['conversation_id']
                            ]);
                        }
                    }
                }
            }
            
            // If this is a monitor, clear monitor_fd from any clients it was monitoring
            if ($clientInfo['type'] === 'monitor') {
                foreach ($this->clientTable as $targetFd => $targetInfo) {
                    if ($targetInfo['monitor_fd'] === $fd) {
                        $targetInfo['monitor_fd'] = 0;
                        $this->clientTable->set($targetFd, $targetInfo);
                        $this->info("[SERVER] Removed monitor {$fd} from client {$targetFd}");
                    }
                }
            }
            
            // Remove client from room
            if ($clientInfo['room_id']) {
                $room = $clientInfo['room_id'];
                $roomInfo = $this->roomTable->get($room);
                if ($roomInfo) {
                    $clients = json_decode($roomInfo['clients'], true);
                    unset($clients[$fd]);
                    
                    // Check if room is now empty
                    if (empty($clients)) {
                        Log::info("[SERVER] Room is now empty, cleaning up", ['room' => $room]);
                        $this->roomTable->del($room);
                    } else {
                        $this->roomTable->set($room, [
                            'name' => $room,
                            'created_at' => $roomInfo['created_at'],
                            'last_activity' => time(),
                            'clients' => json_encode($clients)
                        ]);
                    }
                }
            }
            
            // If it's a twilio_inbound client, disconnect any OpenAI clients in the same room
            if ($clientInfo['type'] === 'twilio_inbound' && $clientInfo['room_id']) {
                $room = $clientInfo['room_id'];
                
                // Find and close connections for OpenAI clients in the same room
                foreach ($this->clientTable as $otherFd => $otherClient) {
                    if ($otherFd != $fd && $otherClient['type'] === 'openai' && $otherClient['room_id'] === $room) {
                        Log::info("[SERVER] Closing OpenAI connection for room", [
                            'room' => $room,
                            'client_type' => $otherClient['type'],
                            'client_id' => $otherClient['device_id']
                        ]);
                        $server->close($otherFd);
                    }
                }
            }
            
            // If it's a webclient, disconnect other room members
            if ($clientInfo['type'] === 'webclient' && $clientInfo['room_id']) {
                $room = $clientInfo['room_id'];
                
                // Find and close connections for other clients in the same room
                foreach ($this->clientTable as $otherFd => $otherClient) {
                    if ($otherFd != $fd && $otherClient['room_id'] === $room) {
                        Log::info("[SERVER] Closing connection for room member", [
                            'room' => $room,
                            'client_type' => $otherClient['type'],
                            'client_id' => $otherClient['device_id']
                        ]);
                        $server->close($otherFd);
                    }
                }
            }

            // Remove client from table
            $this->clientTable->del($fd);
        }
    }

    private function handleStartCall(Server $server, int $fd, array $data)
    {
        $clientInfo = $this->clientTable->get($fd);
        if (!$clientInfo || $clientInfo['type'] !== 'dashboard') {
            return;
        }

        $phoneNumber = $data['phone_number'] ?? null;
        if (!$phoneNumber) {
            $this->error("[SERVER] No phone number provided");
            return;
        }

        // Generate a unique room ID
        $room = uniqid('call_');
        
        // Update dashboard client with room ID
        $clientInfo['room_id'] = $room;
        $this->clientTable->set($fd, $clientInfo);

        // Start the call relay process
        $process = new \Symfony\Component\Process\Process([
            'php', 'artisan', 'bare:call', $phoneNumber, '22'
        ]);
        $process->start();

        // Notify dashboard of call status
        $server->push($fd, json_encode([
            'type' => 'call_status',
            'status' => 'initiated',
            'room' => $room
        ]));
    }

    private function handleEndCall(Server $server, int $fd)
    {
        $clientInfo = $this->clientTable->get($fd);
        if (!$clientInfo) {
            Log::error("[SERVER] No client info found for fd: {$fd}");
            return;
        }

        $room = $clientInfo['room_id'];
        if (!$room) {
            Log::error("[SERVER] No room found for client", [
                'fd' => $fd,
                'client_info' => $clientInfo
            ]);
            return;
        }

        Log::info("[SERVER] Handling end call", [
            'room' => $room,
            'client_type' => $clientInfo['type'],
            'client_id' => $clientInfo['device_id'] ?? 'unknown'
        ]);

        // Find and close all connections in the room
        foreach ($this->clientTable as $clientFd => $info) {
            if ($info['room_id'] === $room) {
                Log::info("[SERVER] Closing connection for room member", [
                    'room' => $room,
                    'client_type' => $info['type'],
                    'client_id' => $info['device_id'] ?? 'unknown'
                ]);
                $server->close($clientFd);
            }
        }

        // Clean up room data
        if ($this->roomTable->exists($room)) {
            $this->roomTable->del($room);
            Log::info("[SERVER] Room deleted", ['room' => $room]);
        }
    }

    private function handleMuteCall(Server $server, int $fd, array $data)
    {
        $clientInfo = $this->clientTable->get($fd);
        if (!$clientInfo || $clientInfo['type'] !== 'dashboard') {
            return;
        }

        $room = $clientInfo['room_id'];
        if (!$room) {
            return;
        }

        $muted = $data['muted'] ?? false;

        // Find Twilio client and send mute command
        foreach ($this->clientTable as $clientFd => $info) {
            if ($info['type'] === 'twilio' && $info['room_id'] === $room) {
                $server->push($clientFd, json_encode([
                    'type' => 'mute',
                    'muted' => $muted
                ]));
                break;
            }
        }
    }

    private function handleInputAudioBufferAppend(Server $server, int $fd, array $data)
    {
        static $appendMessageCount = 0;
        static $receivedMessageCount = 0;
        $appendMessageCount++;
        $receivedMessageCount++;

        $clientInfo = $this->clientTable->get($fd);
        if (!$clientInfo) {
            Log::error("[SERVER] No client info found for audio buffer append", [
                'fd' => $fd,
                'data' => $data
            ]);
            return;
        }

        
        $room = $clientInfo['room_id'];
        if (!$room) {
            Log::error("[SERVER] No room found for audio buffer append", [
                'fd' => (int)$fd,
                'client_info' => $clientInfo
            ]);
            return;
        }

        // Find OpenAI client in the same room
        foreach ($this->clientTable as $clientFd => $info) {
            if($clientFd == $fd) {
                continue;
            }

            if ($info['type'] === 'openai' && $info['room_id'] === $room) {
                if($this->debug && $appendMessageCount % 20 === 0)
                {
                    Log::info("[AUDIO FLOW] WebClient → OpenAI", [
                        'room' => $room,
                        'bytes' => strlen($data['audio']),
                        'from_fd' => (int)$fd,
                        'to_fd' => (int)$clientFd,
                        'data' => array_keys($data)
                    ]);
                }

                $server->push($clientFd, json_encode($data));
                break;
            }
        }
    }

    private function handleResponseCreate(Server $server, int $fd, array $data)
    {
        // Handle response creation if needed
        Log::debug("[SERVER] Response create received", [
            'room' => $this->clientTable->get($fd)['room_id'] ?? null
        ]);
    }

    private function handleRateLimitsUpdated(Server $server, int $fd, array $data)
    {
        // Handle rate limits update if needed
        Log::debug("[SERVER] Rate limits updated", [
            'room' => $this->clientTable->get($fd)['room_id'] ?? null,
            'limits' => $data['rate_limits'] ?? []
        ]);
    }

    private function handleResponseOutputItemAdded(Server $server, int $fd, array $data)
    {
        // Handle response output item added if needed
        Log::debug("[SERVER] Output item added", [
            'room' => $this->clientTable->get($fd)['room_id'] ?? null,
            'item' => $data['item'] ?? null
        ]);
    }

    private function handleConversationItemCreated(Server $server, int $fd, array $data)
    {
        // Handle conversation item created if needed
        Log::debug("[SERVER] Item created", [
            'room' => $this->clientTable->get($fd)['room_id'] ?? null,
            'item' => $data['item'] ?? null
        ]);
    }

    private function handleResponseContentPartAdded(Server $server, int $fd, array $data)
    {
        // Handle response content part added if needed
        Log::debug("[SERVER] Content part added", [
            'room' => $this->clientTable->get($fd)['room_id'] ?? null,
            'part' => $data['part'] ?? null
        ]);
    }

    private function handleResponseContentPartDone(Server $server, int $fd, array $data)
    {
        // Handle response content part done if needed
        Log::debug("[SERVER] Content part done", [
            'room' => $this->clientTable->get($fd)['room_id'] ?? null,
            'part' => $data['part'] ?? null
        ]);
    }

    private function handleResponseOutputItemDone(Server $server, int $fd, array $data)
    {
        // Handle response output item done if needed
        Log::debug("[SERVER] Output item done", [
            'room' => $this->clientTable->get($fd)['room_id'] ?? null,
            'item' => $data['item'] ?? null
        ]);
    }

    private function handleSessionCreated(Server $server, int $fd, array $data)
    {
        // Handle session created if needed
        Log::debug("[SERVER] Session created", [
            'room' => $this->clientTable->get($fd)['room_id'] ?? null,
            'session' => $data['session'] ?? null
        ]);
    }

    private function handleSessionUpdated(Server $server, int $fd, array $data)
    {
        // Handle session updated if needed
        Log::debug("[SERVER] Session updated", [
            'room' => $this->clientTable->get($fd)['room_id'] ?? null,
            'session' => $data['session'] ?? null
        ]);
    }

    private function handleRequestServerData(Server $server, int $fd)
    {
        $clientData = [];
        foreach ($this->clientTable as $clientFd => $info) {
            $clientData[] = [
                'fd' => $clientFd,
                'device_id' => $info['device_id'],
                'type' => $info['type'],
                'room_id' => $info['room_id'],
                'last_seen' => $info['last_seen'],
                'services' => json_decode($info['services'], true),
                'status' => $info['status'],
                'stream_sid' => $info['stream_sid'],
                'api_token' => $info['api_token']
            ];
        }

        $roomData = [];
        foreach ($this->roomTable as $roomId => $info) {
            $roomData[$roomId] = [
                'name' => $info['name'] ?? $roomId,
                'created_at' => $info['created_at'] ?? null,
                'last_activity' => $info['last_activity'] ?? null,
                'clients' => $info['clients'] ?? '[]',
                'status' => 'active',
            ];
        }

        $response = [
            'type' => 'server_data',
            'clients' => $clientData,
            'rooms' => $roomData,
        ];

        $server->push($fd, json_encode($response));
    }

    private function handleClearMessage(Server $server, int $fd, array $data)
    {
        $clientInfo = $this->clientTable->get($fd);
        if (!$clientInfo) {
            Log::error("[SERVER] No client info found for clear message");
            return;
        }

        $room = $clientInfo['room_id'];
        if (!$room) {
            Log::error("[SERVER] No room found for clear message");
            return;
        }

        // Find Twilio client in the same room
        foreach ($this->clientTable as $clientFd => $info) {
            if (($info['type'] === 'twilio' || $info['type'] === 'twilio_inbound') && $info['room_id'] === $room) {
                if($this->debug)
                {
                    Log::info("[SERVER] Forwarding clear message to Twilio", [
                        'room' => $room,
                        'from_fd' => $fd,
                        'to_fd' => $clientFd,
                        'stream_sid' => $info['stream_sid']
                    ]);
                }

                $clearMessage = [
                    'event' => 'clear',
                    'streamSid' => $info['stream_sid']
                ];

                $server->push($clientFd, json_encode($clearMessage));
                break;
            }
        }
    }

    private function cleanupEmptyRooms()
    {
        foreach ($this->roomTable as $roomId => $roomInfo) {
            $clients = json_decode($roomInfo['clients'], true);
            if (empty($clients)) {
                Log::info("[SERVER] Cleaning up empty room", ['room' => $roomId]);
                $this->roomTable->del($roomId);
            }
        }
    }
} 