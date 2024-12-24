<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use OpenSwoole\WebSocket\Server;
use OpenSwoole\WebSocket\Frame;
use OpenSwoole\Constant;
use OpenSwoole\Table;

class StartSwooleServer extends Command
{
    protected $signature = 'swoole:start';
    protected $description = 'Start WebSocket server for audio streaming';

    private Table $managersTable;
    private Table $clientsTable;
    private Table $activeCallsTable;

    public function __construct()
    {
        parent::__construct();
        
        // Initialize managers table
        $this->managersTable = new Table(1024);
        $this->managersTable->column('active', Table::TYPE_INT, 1);
        $this->managersTable->create();

        // Initialize clients table
        $this->clientsTable = new Table(1024);
        $this->clientsTable->column('type', Table::TYPE_STRING, 32);
        $this->clientsTable->column('streaming', Table::TYPE_INT, 1);
        $this->clientsTable->column('callId', Table::TYPE_STRING, 64);
        $this->clientsTable->column('sessionId', Table::TYPE_STRING, 64);
        $this->clientsTable->column('userName', Table::TYPE_STRING, 64);
        $this->clientsTable->column('streamingTo', Table::TYPE_STRING, 1024); // JSON array of manager FDs
        $this->clientsTable->create();

        // Initialize active calls table
        $this->activeCallsTable = new Table(1024);
        $this->activeCallsTable->column('twilioFd', Table::TYPE_INT);
        $this->activeCallsTable->column('clients', Table::TYPE_STRING, 1024); // JSON array of client FDs
        $this->activeCallsTable->create();
    }

    public function handle()
    {
        $this->info("Starting WebSocket Server...");
        $server = new Server('0.0.0.0', 9501, SWOOLE_PROCESS, SWOOLE_SOCK_TCP | SWOOLE_SSL);

        $this->configureSSL($server);

        $server->on(Constant::EVENT_START, function() {
            $this->info("[SERVER] WebSocket server started on wss://richbot9000.com:9501");
        });

        $server->on('handshake', function(\OpenSwoole\Http\Request $request, \OpenSwoole\Http\Response $response) use ($server) {
            // Store query parameters in a property for use in onConnect
            $queryString = $request->server['query_string'] ?? '';
            parse_str($queryString, $queryParams);

            dump($queryParams);            
            // Store in temporary property
            $server->queryParams[$request->fd] = $queryParams;
            
            // Complete handshake
            $secWebSocketKey = $request->header['sec-websocket-key'];
            $patten = '#^[+/0-9A-Za-z]{21}[AQgw]==$#';
            
            if (0 === preg_match($patten, $secWebSocketKey) || 16 !== strlen(base64_decode($secWebSocketKey))) {
                $response->end();
                return false;
            }
            
            $key = base64_encode(sha1($secWebSocketKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', true));
            
            $headers = [
                'Upgrade' => 'websocket',
                'Connection' => 'Upgrade',
                'Sec-WebSocket-Accept' => $key,
                'Sec-WebSocket-Version' => '13',
            ];
            
            foreach ($headers as $key => $val) {
                $response->header($key, $val);
            }
            
            $response->status(101);
            $response->end();
            return true;
        });

        $server->on(Constant::EVENT_CONNECT, function($server, $fd) {
            
            dump($fd);

            $this->onConnect($server, $fd);
        });

        $server->on(Constant::EVENT_MESSAGE, function($server, Frame $frame) {

            //dump(__FUNCTION__.':'.__FILE__.':'.__LINE__, 'Current managers:', $this->managersTable->get());

            $this->onMessage($server, $frame);
        });

        $server->on(Constant::EVENT_CLOSE, function($server, $fd) {

            //var_dump($this->managersTable->get());
            $this->onClose($server, $fd);


            //var_dump($this->clientsTable->get());
            //var_dump($this->activeCallsTable->get());
        });

        $server->start();
    }

    private function configureSSL(Server $server)
    {
        $this->info("Configuring SSL...");
        $server->set([
            'ssl_cert_file' => '/etc/letsencrypt/live/boxcrossranch.com/fullchain.pem',
            'ssl_key_file' => '/etc/letsencrypt/live/boxcrossranch.com/privkey.pem',
            'ssl_verify_peer' => false,
        ]);
    }

    private function onConnect($server, $fd)
    {
        $this->info("[CONNECT] New connection (fd: {$fd})");
        
        // Get stored query parameters
        $queryParams = $server->queryParams[$fd] ?? [];
        
        if (!$this->clientsTable->get($fd)) {
            $this->clientsTable->set($fd, [
                'type' => '',
                'streaming' => 0,
                'callId' => '',
                'sessionId' => '',
                'userName' => $queryParams['phone'] ?? '',  // Store phone number as userName
                'streamingTo' => json_encode([]),
                'token' => $queryParams['token'] ?? ''      // Store token for validation
            ]);
        }
        
        // Clean up stored query params
        unset($server->queryParams[$fd]);
        
        $this->logState();
    }

    // Method to get all clients
    private function getAllClients()
    {
        $clients = [];
        foreach($this->clientsTable as $fd => $clientData) {
            $clients[$fd] = $clientData;
        }
        return $clients;
    }

    private function getAllManagers()
    {
        $managers = [];
        foreach($this->managersTable as $fd => $managerData) {
            $managers[$fd] = $managerData;
        }
        return $managers;
    }

    private function getAllActiveCalls(){
        $activeCalls = [];
        foreach($this->activeCallsTable as $callId => $callData) {
            $activeCalls[$callId] = $callData;
        }
        return $activeCalls;
    }
   

    // Example usage in broadcastState method
    private function broadcastState($server)
    {
        // Prepare the state data
        $state = [
            'event' => 'state_update',
            'clients' => $this->getAllClients(), // Now sends full client data
            'activeCalls' => $this->activeCallsTable->count(),
        ];

        // Broadcast to all connected managers
        foreach ($this->managersTable as $fd => $data) {
            if ($data['active']) {
                $server->push($fd, json_encode($state));
            }
        }
    }

    private function onMessage($server, Frame $frame)
    {
        try {
            $data = json_decode($frame->data, true);
            if (!$data || !isset($data['event'])) {
                throw new \Exception("Invalid message format".json_encode($data));
            }

            $this->info("[MESSAGE] Received event '{$data['event']}' from fd {$frame->fd}".json_encode($data));

            switch ($data['event']) {
                case 'connected':
                    $this->handleConnection($server, $frame->fd, $data);
                    break;

                case 'media':
                    $this->handleMedia($server, $frame->fd, $data);
                    break;

                case 'start':
                    $this->handleTwilioStart($server, $frame->fd, $data);
                    break;

                case 'start_stream':
                    $this->handleStreamStart($server, $frame->fd, $data);
                    break;

                case 'stop_stream':
                    $this->handleStreamStop($server, $frame->fd, $data);
                    break;

                default:
                    $this->warn("[UNKNOWN EVENT] Unhandled event: {$data['event']}");
            }
        } catch (\Exception $e) {
            $this->error("[ERROR] " . $e->getMessage());
        }
    }

    private function onClose($server, $fd)
    {
        $this->info("[DISCONNECT] Connection closed (fd: {$fd})");
        
        if ($this->managersTable->get($fd)) {
            $this->info("[MANAGER] Manager temporarily disconnected (fd: {$fd})");
            $this->managersTable->set($fd, ['active' => 0]); // Mark as inactive
        }

        $this->removeClient($fd);
        
        $this->info("\n[DISCONNECT EVENT] Connection closed");
        $this->logState();
    }

    private function handleConnection($server, $fd, $data)
    {
        $protocol = $data['protocol'] ?? 'Unknown';

        if ($protocol === 'Manager') {
            $this->managersTable->set($fd, ['active' => 1]);
            
            // Send FD back to manager
            $server->push($fd, json_encode([
                'event' => 'connection_established',
                'fd' => $fd
            ]));

            $this->broadcastState($server);
        } elseif ($protocol === 'Client') {

            $this->info("[CLIENT] Client connected (fd: {$fd})");
            
            // Get the userName from the stored query parameters
            $clientData = $this->clientsTable->get($fd);
            $userName = $data['userData']['userName'] ?? 'Anonymous';
            
            $this->clientsTable->set($fd, [
                'type' => 'Client',
                'streaming' => 0,
                'callId' => '',
                'sessionId' => $data['sessionId'] ?? '',
                'userName' => $userName,  // Use the phone number stored during handshake
                'streamingTo' => json_encode([])
            ]);

            // Send FD back to client
            $server->push($fd, json_encode([
                'event' => 'connection_established',
                'fd' => $fd
            ]));

            // Notify managers of new client
            $this->broadcastToManagers($server, [
                'event' => 'client_connected',
                'clientId' => $fd,
                'clientData' => $this->clientsTable->get($fd)
            ]);
        }

        // Log state after any new connection
        $this->info("\n[CONNECTION EVENT] New {$protocol} connection");
        $this->logState();
    }

    private function handleTwilioStart($server, $fd, $data)
    {
        $callId = $data['start']['streamSid'] ?? null;
        $toNumber = $queryParams['to'] ?? $data['start']['to'] ?? null;
        $fromNumber = $queryParams['from'] ?? $data['start']['from'] ?? null;

        dump(__FUNCTION__.':'.__FILE__.':'.__LINE__, 'Data:', $data);
        dump(__FUNCTION__.':'.__FILE__.':'.__LINE__, 'Query Params:', [
            'to' => $toNumber,
            'from' => $fromNumber
        ]);

        if (!$callId) {
            $this->warn("[TWILIO] Missing call ID");
            return;
        }

        $this->info("[TWILIO] Incoming call details:");
        $this->info("- Call SID: {$callId}");
        $this->info("- From: {$fromNumber}");
        $this->info("- To: {$toNumber}");
        $this->info("- Twilio FD: {$fd}");
        
        // First try to find client with matching phone number
        $targetClientFd = null;
        foreach ($this->clientsTable as $clientFd => $clientData) {
            if ($clientData['userName'] === $toNumber) {
                $targetClientFd = $clientFd;
                $this->info("[TWILIO] Found matching client FD: {$targetClientFd}");
                break;
            }
        }

        // If no specific client found, find first available non-streaming client
        if (!$targetClientFd) {
            $this->info("[TWILIO] No specific client found, looking for any available client");
            foreach ($this->clientsTable as $clientFd => $clientData) {
                if ($clientData['type'] === 'Client' && !$clientData['streaming']) {
                    $targetClientFd = $clientFd;
                    $this->info("[TWILIO] Found available client FD: {$targetClientFd}");
                    break;
                }
            }
        }

        if (!$targetClientFd) {
            $this->warn("[TWILIO] No available clients found");
        }

        $this->activeCallsTable->set($callId, [
            'twilioFd' => $fd,
            'clients' => json_encode([$fd, $targetClientFd])
        ]);

        $this->clientsTable->set($fd, [
            'type' => 'Twilio',
            'streaming' => 1,
            'callId' => $callId,
            'sessionId' => '',
            'userName' => '',
            'streamingTo' => json_encode([$targetClientFd])
        ]);

        if ($targetClientFd) {
            $server->push($targetClientFd, json_encode([
                'event' => 'incoming_call',
                'callId' => $callId
            ]));
        }

        $this->broadcastToManagers($server, [
            'event' => 'call_started',
            'callId' => $callId,
        ]);

        $this->info("\n[TWILIO EVENT] New call started");
        $this->logState();
    }

    private function broadcastToManagers($server, $data)
    {
        $this->info("[BROADCAST] Starting broadcast to " . $this->managersTable->count() . " managers");
        
        foreach ($this->managersTable as $fd => $managerData) {
            if ($managerData['active'] && $server->isEstablished($fd)) {
                $server->push($fd, json_encode($data));
            } elseif (!$managerData['active']) {
                $this->info("[MANAGER] Skipping inactive manager (fd: {$fd})");
            } else {
                $this->info("[MANAGER] Removing permanently disconnected manager (fd: {$fd})");
                $this->logManagerChange('broadcastToManagers', 'Removing invalid', $fd);
                $this->managersTable->del($fd);
            }
        }
    }

    private function broadcastToCall($server, $callId, $data, $excludeFd = null)
    {
        if (!isset($this->activeCalls[$callId])) {
            $this->warn("[CALL] Invalid call ID: {$callId}");
            return;
        }

        foreach ($this->activeCalls[$callId]['clients'] as $clientFd) {
            if ($clientFd !== $excludeFd) {
                $server->push($clientFd, json_encode($data));
            }
        }
    } 

    private function removeClient($fd)
    {
        if ($this->managersTable->get($fd)) {
            $this->warn("[WARNING] Attempted to remove manager through removeClient: {$fd}");
            return;
        }

        if ($this->clientsTable->get($fd)) {
            $clientData = $this->clientsTable->get($fd);
            $this->clientsTable->del($fd);

            // Clean up active calls
            foreach ($this->activeCallsTable as $callId => $callData) {
                $clients = json_decode($callData['clients'], true);
                $clients = array_filter($clients, fn($clientFd) => $clientFd !== $fd);
                
                if (empty($clients)) {
                    $this->activeCallsTable->del($callId);
                } else {
                    $this->activeCallsTable->set($callId, [
                        'twilioFd' => $callData['twilioFd'],
                        'clients' => json_encode($clients)
                    ]);
                }
            }
        }
    }

    private function logState()
    {
        $this->info("\n=== Server State ===");
        
        // Simplified manager logging
        $this->info("Active Managers: " . count(array_filter($this->getAllManagers(), fn($m) => $m['active'])));
        
        // Enhanced client logging
        $this->info("\nConnected Clients:");
        foreach ($this->clientsTable as $fd => $clientData) {
            $this->info("- FD: {$fd}");
            $this->info("  Type: {$clientData['type']}");
            $this->info("  User: {$clientData['userName']}");
            $this->info("  Call ID: {$clientData['callId']}");
            $this->info("  Streaming: " . ($clientData['streaming'] ? 'Yes' : 'No'));
            $streamingTo = json_decode($clientData['streamingTo'], true);
            $this->info("  Streaming to: " . (empty($streamingTo) ? 'None' : implode(', ', $streamingTo)));
        }
        
        // Enhanced active calls logging
        $this->info("\nActive Calls:");
        foreach ($this->activeCallsTable as $callId => $callData) {
            $this->info("- Call ID: {$callId}");
            $this->info("  Twilio FD: {$callData['twilioFd']}");
            $clients = json_decode($callData['clients'], true);
            $this->info("  Connected clients: " . implode(', ', $clients));
        }
        $this->info("================\n");
    }

    private function handleStreamStart($server, $fd, $data)
    {
        dump(__FUNCTION__.':'.__FILE__.':'.__LINE__, 'Data:', $data);

        if (!isset($data['targetClient'])) {
            return;
        }

        $targetClient = $data['targetClient'];
        $this->info("[STREAM START] Target client: {$targetClient}");

        $clientData = $this->clientsTable->get($targetClient);
        dump(__FUNCTION__.':'.__FILE__.':'.__LINE__, 'Client data:', $clientData);
        
        if ($clientData) {
            // Send stream start request to target client
            $server->push($targetClient, json_encode([
                'event' => 'stream_start_request',
                'requestingManager' => $fd
            ]));

            $streamingTo = json_decode($clientData['streamingTo'], true);
            $streamingTo[] = $fd;
            
            $this->clientsTable->set($targetClient, [
                'type' => $clientData['type'],
                'streaming' => $clientData['streaming'],
                'callId' => $clientData['callId'],
                'sessionId' => $clientData['sessionId'],
                'userName' => $clientData['userName'],
                'streamingTo' => json_encode($streamingTo)
            ]);

            $this->broadcastToManagers($server, [
                'event' => 'stream_started',
                'clientId' => $targetClient
            ]);
        }
    }

    private function handleStreamStop($server, $fd, $data)
    {
        if (!isset($data['targetClient'])) {
            return;
        }

        $targetClient = $data['targetClient'];
        $clientData = $this->clientsTable->get($targetClient);
        if ($clientData) {
            // Send stream stop request to target client
            $server->push($targetClient, json_encode([
                'event' => 'stream_stop_request',
                'requestingManager' => $fd
            ]));

            $streamingTo = json_decode($clientData['streamingTo'], true);
            $streamingTo = array_filter($streamingTo, fn($managerId) => $managerId !== $fd);
            
            $this->clientsTable->set($targetClient, [
                'type' => $clientData['type'],
                'streaming' => $clientData['streaming'],
                'callId' => $clientData['callId'],
                'sessionId' => $clientData['sessionId'],
                'userName' => $clientData['userName'],
                'streamingTo' => json_encode($streamingTo)
            ]);

            $this->broadcastToManagers($server, [
                'event' => 'stream_stopped',
                'clientId' => $targetClient
            ]);
        }
    }

    private function handleMedia($server, $fd, $data)
    {
        $clientData = $this->clientsTable->get($fd);
        if (!$clientData) {
            $this->warn("[MEDIA] Unknown client (fd: {$fd})");
            return;
        }

        // Forward media to all listening managers
        $streamingTo = json_decode($clientData['streamingTo'], true);
        foreach ($streamingTo as $id) {
            $server->push($id, json_encode([
                'event' => 'media',
                'sourceClient' => $fd,
                'media' => $data['media']
            ]));
        }
    }

    private function logManagerChange($location, $action, $fd)
    {
        // Simplified manager logging
        $this->info("[MANAGERS] {$action} FD: {$fd}");
    }







    private function createOpenAIWebSocketClient(Server $server, int $twilioFd, string $callId)
    {
        // OpenAI API URL
        $openAiWsUrl = "wss://api.openai.com/v1/realtime?model=gpt-4o-realtime-preview-2024-10-01";
    
        // OpenAI API Headers
        $headers = [
            "Authorization: Bearer " . env('OPENAI_API_KEY'),
            "OpenAI-Beta: realtime=v1"
        ];
    
        // Create OpenAI WebSocket client
        $openAiClient = new \OpenSwoole\Coroutine\Http\Client('api.openai.com', 443, true);
    
        // Connect to OpenAI WebSocket
        if (!$openAiClient->upgrade($openAiWsUrl)) {
            $this->warn("[OPENAI] Failed to connect to OpenAI WebSocket");
            return;
        }
    
        // Initialize OpenAI session
        $this->initializeOpenAISession($openAiClient);
    
        // Concurrently handle Twilio-to-OpenAI and OpenAI-to-Twilio communication
        go(function () use ($server, $openAiClient, $twilioFd, $callId) {
            $this->relayAudioFromTwilioToOpenAI($server, $openAiClient, $twilioFd, $callId);
        });
    
        go(function () use ($server, $openAiClient, $twilioFd, $callId) {
            $this->relayAudioFromOpenAIToTwilio($server, $openAiClient, $twilioFd, $callId);
        });
    }
    
    private function initializeOpenAISession($openAiClient)
    {
        $sessionUpdate = [
            "type" => "session.update",
            "session" => [
                "turn_detection" => ["type" => "server_vad"],
                "input_audio_format" => "g711_ulaw",
                "output_audio_format" => "g711_ulaw",
                "voice" => "alloy",
                "instructions" => "You are a helpful assistant.",
                "modalities" => ["text", "audio"],
                "temperature" => 0.8,
            ]
        ];
    
        $openAiClient->push(json_encode($sessionUpdate));
        $this->info("[OPENAI] Session initialized");
    }
    
    private function relayAudioFromTwilioToOpenAI(Server $server, $openAiClient, int $twilioFd, string $callId)
    {
        $this->info("[OPENAI] Relaying audio from Twilio to OpenAI for Call ID: {$callId}");
    
        while ($server->exists($twilioFd)) {
            $data = $server->recv($twilioFd);
            if (!$data) {
                break;
            }
    
            $data = json_decode($data, true);
            if (isset($data['event']) && $data['event'] === 'media') {
                $audioAppend = [
                    "type" => "input_audio_buffer.append",
                    "audio" => $data['media']['payload']
                ];
    
                $openAiClient->push(json_encode($audioAppend));
            }
        }
    
        $this->info("[OPENAI] Stopped relaying audio from Twilio for Call ID: {$callId}");
    }
    
    private function relayAudioFromOpenAIToTwilio(Server $server, $openAiClient, int $twilioFd, string $callId)
    {
        $this->info("[OPENAI] Relaying audio from OpenAI to Twilio for Call ID: {$callId}");
    
        while (true) {
            $response = $openAiClient->recv();
            if (!$response) {
                break;
            }
    
            $data = json_decode($response, true);
            if (isset($data['type']) && $data['type'] === 'response.audio.delta') {
                $payload = $data['delta'] ?? null;
                if ($payload) {
                    $twilioMedia = [
                        "event" => "media",
                        "media" => [
                            "payload" => $payload
                        ]
                    ];
    
                    $server->push($twilioFd, json_encode($twilioMedia));
                }
            }
        }
    
        $this->info("[OPENAI] Stopped relaying audio to Twilio for Call ID: {$callId}");
    }
    private function handleMediaNew(Server $server, $fd, $data)
    {
        $clientData = $this->clientsTable->get($fd);
        if (!$clientData) {
            $this->warn("[MEDIA] Unknown client (FD: {$fd})");
            return;
        }

        // Relay media to all connected clients
        $this->handleRelayStream($server, $fd, $data);
    }



}
