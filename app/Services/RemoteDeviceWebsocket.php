<?php

namespace App\Services;

use OpenSwoole\WebSocket\Server;
use OpenSwoole\Http\Request;
use OpenSwoole\WebSocket\Frame;
use OpenSwoole\Table;
use Laravel\Sanctum\PersonalAccessToken;
use Illuminate\Support\Facades\Log;

class RemoteDeviceWebsocket
{
    private Server $server;
    private Table $clients;
    private $logFile;

    public function __construct(string $host = '0.0.0.0', int $port = 9502)
    {
        $this->logFile = config('app.base_path') . '/storage/logs/remote_device_websocket.log';
        
        // Initialize the clients table
        $this->clients = new Table(1024); // Support up to 1024 concurrent connections
        $this->clients->column('user_id', Table::TYPE_INT);
        $this->clients->column('email', Table::TYPE_STRING, 64);
        $this->clients->column('connected_at', Table::TYPE_STRING, 32);
        $this->clients->column('remote_addr', Table::TYPE_STRING, 32);
        $this->clients->create();

        $this->server = new Server($host, $port);

        $this->server->on('start', function(Server $server) use ($host, $port) {
            echo "RemoteDeviceWebsocket started on ws://{$host}:{$port}\n";
            
            // Set up periodic timer (10 seconds)
            $server->tick(10000, function() use ($server) {
                $this->handlePeriodicTask($server);
            });
        });

        $this->server->on('open', [$this, 'handleConnection']);
        $this->server->on('message', [$this, 'handleMessage']);
        $this->server->on('close', [$this, 'handleDisconnect']);

        $this->logToFile("Starting Server on {$host}:{$port}");
    }

    public function start(): void
    {
        $this->server->start();
    }

    public function handleConnection(Server $server, Request $request): void
    {
        try {
            $this->logToFile("New connection attempt from {$request->server['remote_addr']}");

            // Extract token from URI
            $token = $this->extractToken($request->server['request_uri']);
            if (!$token) {
                $this->logToFile("No token provided for connection from {$request->server['remote_addr']}");

                $server->push($request->fd, json_encode([
                    'event' => 'connection_establishing',
                    'message' => 'test',
                    'user_id' => 'test',
                    'timestamp' => now()->toIso8601String()
                ]));
                $server->disconnect($request->fd, 1008, 'No token provided');
                return;
            }

            // Authenticate user
            $user = $this->authenticateUser($token);
            if (!$user) {
                $this->logToFile("Authentication failed for connection from {$request->server['remote_addr']}");
                $server->disconnect($request->fd, 1008, 'Authentication failed');
                return;
            }

            // Store client information in the table
            $this->clients->set($request->fd, [
                'user_id' => $user->id,
                'email' => $user->email,
                'connected_at' => now()->toIso8601String(),
                'remote_addr' => $request->server['remote_addr']
            ]);

            $this->logToFile("Successful connection established for user {$user->id} from {$request->server['remote_addr']}");

            // Send welcome message
            $server->push($request->fd, json_encode([
                'event' => 'connection_established',
                'message' => 'Successfully connected to RemoteDeviceWebsocket',
                'user_id' => $user->id,
                'timestamp' => now()->toIso8601String()
            ]));

        } catch (\Exception $e) {
            $this->logToFile("Error in handleConnection: {$e->getMessage()}");
            $server->disconnect($request->fd, 1011, 'Server error during connection');
        }
    }

    public function handleMessage(Server $server, Frame $frame): void
    {
        try {
            $this->logToFile("Received message from client {$frame->fd}: {$frame->data}");

            // Echo back the message for testing
            $response = [
                'event' => 'message_received',
                'original_message' => $frame->data,
                'timestamp' => now()->toIso8601String()
            ];

            $server->push($frame->fd, json_encode($response));

        } catch (\Exception $e) {
            $this->logToFile("Error in handleMessage: {$e->getMessage()}");
            $server->push($frame->fd, json_encode([
                'event' => 'error',
                'message' => 'Error processing message',
                'timestamp' => now()->toIso8601String()
            ]));
        }
    }

    public function handleDisconnect(Server $server, int $fd): void
    {
        if ($this->clients->exist($fd)) {
            $client = $this->clients->get($fd);
            $this->logToFile("Client {$fd} (User ID: {$client['user_id']}) disconnected");
            $this->clients->del($fd);
        } else {
            $this->logToFile("Client {$fd} disconnected");
        }
    }

    private function extractToken(string $uri): ?string
    {
        if (preg_match('/^\/device\/([^\/]+)$/', $uri, $matches)) {
            return urldecode($matches[1]);
        }
        return null;
    }

    private function authenticateUser(?string $token): ?\App\Models\User
    {
        if (!$token) {
            $this->logToFile('No token provided for authentication');
            return null;
        }
        
        try {
            $this->logToFile('Attempting to authenticate token');
            $accessToken = PersonalAccessToken::findToken($token);
            
            if (!$accessToken) {
                $this->logToFile('Token not found in database');
                return null;
            }
            
            $user = $accessToken->tokenable;
            $this->logToFile("User authenticated successfully: {$user->id}");
            
            return $user;
        } catch (\Exception $e) {
            $this->logToFile("Authentication error: {$e->getMessage()}");
            return null;
        }
    }

    private function logToFile(string $message): void
    {
        $timestamp = now()->toIso8601String();
        $logMessage = "[{$timestamp}] {$message}" . PHP_EOL;
        
        try {
            file_put_contents($this->logFile, $logMessage, FILE_APPEND);
        } catch (\Exception $e) {
            Log::error("Failed to write to RemoteDeviceWebsocket log file: {$e->getMessage()}");
        }
    }

    private function handlePeriodicTask(Server $server): void
    {
        try {
            $connectedClients = 0;
            $activeUsers = [];

            // Iterate through all clients and collect statistics
            foreach($this->clients as $fd => $clientInfo) {
                $connectedClients++;
                $activeUsers[] = $clientInfo['user_id'];
                
                // Send heartbeat to each client
                $server->push($fd, json_encode([
                    'event' => 'server_heartbeat',
                    'timestamp' => now()->toIso8601String(),
                    'connected_clients' => $connectedClients
                ]));
            }

            $this->logToFile("Periodic task - Connected clients: {$connectedClients}, Active users: " . implode(', ', $activeUsers));
        } catch (\Exception $e) {
            $this->logToFile("Error in periodic task: {$e->getMessage()}");
        }
    }
} 