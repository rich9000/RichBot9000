<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use React\EventLoop\Factory;
use Ratchet\Client\Connector;
use React\Socket\Connector as ReactConnector;
use Illuminate\Support\Facades\Log;
use React\Promise\Promise;

class BareWebsocketTestV2 extends Command
{
    protected $signature = 'bare:test-v2 {--host=richbot9000.com} {--port=9502} {--api-token=} {--test-all} {--test-connections} {--test-messages} {--test-openai} {--test-dashboard} {--test-twilio} {--test-webclient} {--test-monitor} {--test-richbot} {--no-ssl}';
    protected $description = 'Test BareWebsocketServerV2 connections and message handling';

    private $loop;
    private $connector;
    private $connections = [];
    private $testResults = [];
    private $host;
    private $port;
    private $apiToken;

    private $useSSL = true;
    private $testRoom;
    private $testStartTime;

    // Connection types supported by V2
    private $connectionTypes = [
        'webclient' => 'WebClient Connection Test',
        'twilio' => 'Twilio Connection Test',
        'openai' => 'OpenAI Connection Test', 
        'monitor' => 'Monitor Connection Test',
        'dashboard' => 'Dashboard Connection Test',
        'remote_richbot' => 'Remote Richbot Connection Test'
    ];

    // Message types supported by V2 (initialized in constructor)
    private $messageTypes = [];

    // OpenAI specific message types (initialized in constructor)
    private $openaiMessageTypes = [];

    public function __construct()
    {
        parent::__construct();
        
        // Initialize message types with dynamic values
        $this->messageTypes = [
            'text' => ['content' => 'Hello from test client'],
            'media' => ['data' => base64_encode('test audio data'), 'format' => 'g711_ulaw'],
            'dtmf' => ['digit' => '1'],
            'control' => ['action' => 'status_check'],
            'status' => ['status' => 'testing', 'details' => ['test' => 'active']],
            'command' => ['command' => 'test_command', 'params' => ['test' => true]],
            'heartbeat' => ['timestamp' => time()],
            'broadcast' => ['content' => 'Test broadcast message'],
            'get_all_clients' => [],
            'get_all_rooms' => [],
            'get_room_status' => ['room' => '']
        ];

        $this->openaiMessageTypes = [
            'session.created' => ['session' => ['id' => 'test-session-' . uniqid()]],
            'conversation.created' => ['conversation' => ['id' => 'test-conv-' . uniqid()]],
            'input_audio_buffer.speech_started' => ['audio_start_ms' => 1000],
            'input_audio_buffer.speech_stopped' => ['audio_end_ms' => 2000],
            'response.created' => ['response' => ['id' => 'test-response-' . uniqid()]],
            'response.audio.delta' => ['delta' => base64_encode('test audio delta')],
            'error' => ['error' => ['type' => 'test_error', 'message' => 'Test error message']]
        ];
    }

    public function handle()
    {
        $this->testStartTime = microtime(true);
        $this->host = $this->option('host');
        $this->port = $this->option('port');
        $this->apiToken = $this->option('api-token') ?: 'test-token-' . uniqid();

        $this->useSSL = !$this->option('no-ssl');
        $this->testRoom = 'test-room-' . uniqid();

        $this->info("Starting BareWebsocketServerV2 Comprehensive Tests");
        $this->info("=============================================");
        $this->info("Host: {$this->host}");
        $this->info("Port: {$this->port}");
        $this->info("Test Room: {$this->testRoom}");
        $this->info("SSL: " . ($this->useSSL ? 'Enabled' : 'Disabled'));
        $this->info("API Token: " . substr($this->apiToken, 0, 10) . "...");
        $this->info("=============================================");

        $this->loop = Factory::create();
        $this->connector = new Connector($this->loop, new ReactConnector($this->loop, [
            'tls' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]));

        $this->initializeTestResults();

        // Determine which tests to run
        $testAll = $this->option('test-all');
        $testConnections = $this->option('test-connections') || $testAll;
        $testMessages = $this->option('test-messages') || $testAll;
        
        // Individual connection tests
        $testWebclient = $this->option('test-webclient') || $testAll;
        $testTwilio = $this->option('test-twilio') || $testAll;
        $testOpenai = $this->option('test-openai') || $testAll;
        $testDashboard = $this->option('test-dashboard') || $testAll;
        $testMonitor = $this->option('test-monitor') || $testAll;
        $testRichbot = $this->option('test-richbot') || $testAll;

        // If no specific tests selected, run basic connection tests
        if (!$testAll && !$testConnections && !$testMessages && 
            !$testWebclient && !$testTwilio && !$testOpenai && 
            !$testDashboard && !$testMonitor && !$testRichbot) {
            $testConnections = true;
        }

        $promises = [];

        if ($testConnections || $testWebclient) {
            $promises[] = $this->testWebClientConnection();
        }
        
        if ($testConnections || $testDashboard) {
            $promises[] = $this->testDashboardConnection();
        }

        if ($testConnections || $testOpenai) {
            $promises[] = $this->testOpenAIConnection();
        }

        if ($testConnections || $testTwilio) {
            $promises[] = $this->testTwilioConnection();
        }

        if ($testConnections || $testMonitor) {
            $promises[] = $this->testMonitorConnection();
        }

        if ($testConnections || $testRichbot) {
            $promises[] = $this->testRemoteRichbotConnection();
        }

        if ($testMessages) {
            $promises[] = $this->testAllMessageTypes();
        }

        // Wait for all tests to complete
        $this->loop->addTimer(2, function() use ($promises, $testMessages) {
            if ($testMessages) {
                $this->runMessageTests();
            }
        });

        // Set timeout for all tests
        $this->loop->addTimer(30, function() {
            $this->info("Test timeout reached, stopping tests...");
            $this->loop->stop();
        });

        $this->loop->run();

        $this->displayTestResults();
    }

    private function initializeTestResults(): void
    {
        foreach ($this->connectionTypes as $type => $description) {
            $this->testResults[$type] = [
                'description' => $description,
                'status' => 'PENDING',
                'connected' => false,
                'messages_sent' => 0,
                'messages_received' => 0,
                'errors' => [],
                'duration' => 0,
                'start_time' => null
            ];
        }

        $this->testResults['message_tests'] = [
            'description' => 'Message Type Tests',
            'status' => 'PENDING',
            'tests_passed' => 0,
            'tests_failed' => 0,
            'errors' => []
        ];
    }

    private function testWebClientConnection(): Promise
    {
        return $this->testConnection('webclient', [
            'room' => $this->testRoom,
            'assistant_id' => '123',
            'api_token' => $this->apiToken
        ]);
    }

    private function testDashboardConnection(): Promise
    {
        return $this->testConnection('dashboard', [
            'room' => $this->testRoom . '-dashboard',
            'api_token' => $this->apiToken
        ]);
    }

    private function testOpenAIConnection(): Promise
    {
        return $this->testConnection('openai', [
            'room' => $this->testRoom,
            'assistant_id' => 'test-assistant-456'
        ]);
    }

    private function testTwilioConnection(): Promise
    {
        return $this->testConnection('twilio', [
            'room' => $this->testRoom,
            'call_sid' => 'test-call-' . uniqid()
        ]);
    }

    private function testMonitorConnection(): Promise
    {
        return $this->testConnection('monitor', [
            'room' => $this->testRoom . '-monitor',
            'api_token' => $this->apiToken
        ]);
    }

    private function testRemoteRichbotConnection(): Promise
    {
        return $this->testConnection('remote_richbot', [
            'room' => $this->testRoom,
            'assistant_id' => 'richbot-456',
            'api_token' => $this->apiToken
        ]);
    }

    private function testConnection(string $type, array $params): Promise
    {
        $this->testResults[$type]['start_time'] = microtime(true);
        $this->testResults[$type]['status'] = 'RUNNING';

        $url = $this->buildWebSocketUrl($type, $params);
        
        $this->verboseLog("Testing {$type} connection to: {$url}");

        $connector = $this->connector;
        return $connector($url)
            ->then(function($conn) use ($type, $params) {
                $this->handleConnectionSuccess($conn, $type, $params);
            }, function($e) use ($type) {
                $this->handleConnectionFailure($e, $type);
            });
    }

    private function buildWebSocketUrl(string $type, array $params): string
    {
        $protocol = $this->useSSL ? 'wss' : 'ws';
        $path = "/{$type}";
        
        if (isset($params['room'])) {
            $path .= "/{$params['room']}";
        }
        
        if (isset($params['assistant_id'])) {
            $path .= "/{$params['assistant_id']}";
        } elseif (isset($params['call_sid'])) {
            $path .= "/{$params['call_sid']}";
        }

        $queryParams = [];
        if (isset($params['api_token'])) {
            $queryParams['token'] = $params['api_token'];
        }

        $query = $queryParams ? '?' . http_build_query($queryParams) : '';
        
        return "{$protocol}://{$this->host}:{$this->port}{$path}{$query}";
    }

    private function handleConnectionSuccess($conn, string $type, array $params): void
    {
        $this->connections[$type] = $conn;
        $this->testResults[$type]['connected'] = true;
        $this->testResults[$type]['status'] = 'CONNECTED';
        
        $this->info("✅ {$type} connection successful");

        $conn->on('message', function($msg) use ($type) {
            $this->handleMessage($msg, $type);
        });

        $conn->on('close', function() use ($type) {
            $this->handleConnectionClose($type);
        });

        // Send test messages for this connection type
        $this->sendTestMessages($conn, $type);
    }

    private function handleConnectionFailure($error, string $type): void
    {
        $this->testResults[$type]['status'] = 'FAILED';
        $this->testResults[$type]['errors'][] = $error->getMessage();
        $this->testResults[$type]['duration'] = microtime(true) - $this->testResults[$type]['start_time'];
        
        $this->error("❌ {$type} connection failed: " . $error->getMessage());
    }

    private function handleMessage($msg, string $type): void
    {
        $this->testResults[$type]['messages_received']++;
        $message = json_decode($msg->getPayload(), true);
        
        $this->verboseLog("📨 Received {$type} message: " . json_encode($message));
        
        // Validate message format
        if (!$message || !isset($message['type'])) {
            $this->testResults[$type]['errors'][] = 'Invalid message format';
            return;
        }

        // Handle specific message types
        $this->handleSpecificMessage($message, $type);
    }

    private function handleSpecificMessage(array $message, string $type): void
    {
        switch ($message['type']) {
            case 'all_clients':
                $this->validateClientsResponse($message, $type);
                break;
            case 'all_rooms':
                $this->validateRoomsResponse($message, $type);
                break;
            case 'room_status':
                $this->validateRoomStatusResponse($message, $type);
                break;
            case 'control':
                $this->validateControlResponse($message, $type);
                break;
            case 'error':
                $this->testResults[$type]['errors'][] = "Server error: " . ($message['error'] ?? 'Unknown error');
                break;
            default:
                $this->verboseLog("Received {$message['type']} message from {$type}");
        }
    }

    private function validateClientsResponse(array $message, string $type): void
    {
        if (!isset($message['clients'])) {
            $this->testResults[$type]['errors'][] = 'Invalid clients response format';
            return;
        }

        $clientCount = count($message['clients']);
        $this->verboseLog("✅ {$type} clients response: {$clientCount} clients");
    }

    private function validateRoomsResponse(array $message, string $type): void
    {
        if (!isset($message['rooms'])) {
            $this->testResults[$type]['errors'][] = 'Invalid rooms response format';
            return;
        }

        $roomCount = count($message['rooms']);
        $this->verboseLog("✅ {$type} rooms response: {$roomCount} rooms");
    }

    private function validateRoomStatusResponse(array $message, string $type): void
    {
        if (!isset($message['room']) || !isset($message['found'])) {
            $this->testResults[$type]['errors'][] = 'Invalid room status response format';
            return;
        }

        $this->verboseLog("✅ {$type} room status response for room: {$message['room']}");
    }

    private function validateControlResponse(array $message, string $type): void
    {
        if (!isset($message['action'])) {
            $this->testResults[$type]['errors'][] = 'Invalid control response format';
            return;
        }

        $this->verboseLog("✅ {$type} control response: {$message['action']}");
    }

    private function handleConnectionClose(string $type): void
    {
        $this->testResults[$type]['duration'] = microtime(true) - $this->testResults[$type]['start_time'];
        
        if ($this->testResults[$type]['status'] === 'CONNECTED') {
            $this->testResults[$type]['status'] = 'COMPLETED';
            $this->info("🔌 {$type} connection closed normally");
        }
    }

    private function sendTestMessages($conn, string $type): void
    {
        // Send different test messages based on connection type
        switch ($type) {
            case 'dashboard':
                $this->sendDashboardTestMessages($conn, $type);
                break;
            case 'webclient':
                $this->sendWebClientTestMessages($conn, $type);
                break;
            case 'openai':
                $this->sendOpenAITestMessages($conn, $type);
                break;
            case 'twilio':
                $this->sendTwilioTestMessages($conn, $type);
                break;
            case 'monitor':
                $this->sendMonitorTestMessages($conn, $type);
                break;
            case 'remote_richbot':
                $this->sendRichbotTestMessages($conn, $type);
                break;
        }
    }

    private function sendDashboardTestMessages($conn, string $type): void
    {
        $messages = [
            ['type' => 'get_all_clients'],
            ['type' => 'get_all_rooms'],
            ['type' => 'get_room_status', 'room' => $this->testRoom],
            ['type' => 'control', 'action' => 'status_check'],
            ['type' => 'broadcast', 'content' => 'Test broadcast from dashboard']
        ];

        $this->sendMessagesWithDelay($conn, $type, $messages);
    }

    private function sendWebClientTestMessages($conn, string $type): void
    {
        $messages = [
            ['type' => 'text', 'content' => 'Hello from webclient test'],
            ['type' => 'media', 'data' => base64_encode('test audio'), 'format' => 'wav'],
            ['type' => 'dtmf', 'digit' => '5']
        ];

        $this->sendMessagesWithDelay($conn, $type, $messages);
    }

    private function sendOpenAITestMessages($conn, string $type): void
    {
        $messages = [
            ['type' => 'session.created', 'session' => ['id' => 'test-session-' . uniqid()]],
            ['type' => 'conversation.created', 'conversation' => ['id' => 'test-conv-' . uniqid()]],
            ['type' => 'response.audio.delta', 'delta' => base64_encode('test audio delta')],
            ['type' => 'input_audio_buffer.speech_started', 'audio_start_ms' => 1000]
        ];

        $this->sendMessagesWithDelay($conn, $type, $messages);
    }

    private function sendTwilioTestMessages($conn, string $type): void
    {
        $messages = [
            ['type' => 'media', 'data' => base64_encode('twilio audio'), 'format' => 'g711_ulaw'],
            ['type' => 'dtmf', 'digit' => '*'],
            ['type' => 'speech_started'],
            ['type' => 'speech_stopped']
        ];

        $this->sendMessagesWithDelay($conn, $type, $messages);
    }

    private function sendMonitorTestMessages($conn, string $type): void
    {
        $messages = [
            ['type' => 'get_all_clients'],
            ['type' => 'get_all_rooms'],
            ['type' => 'get_room_status', 'room' => $this->testRoom]
        ];

        $this->sendMessagesWithDelay($conn, $type, $messages);
    }

    private function sendRichbotTestMessages($conn, string $type): void
    {
        $messages = [
            ['type' => 'text', 'content' => 'Hello from richbot'],
            ['type' => 'status', 'status' => 'active', 'details' => ['test' => true]],
            ['type' => 'media', 'data' => base64_encode('richbot audio')]
        ];

        $this->sendMessagesWithDelay($conn, $type, $messages);
    }

    private function sendMessagesWithDelay($conn, string $type, array $messages): void
    {
        $delay = 0.5; // 500ms delay between messages
        
        foreach ($messages as $index => $message) {
            $this->loop->addTimer($delay * ($index + 1), function() use ($conn, $type, $message) {
                $this->sendMessage($conn, $type, $message);
            });
        }
    }

    private function sendMessage($conn, string $type, array $message): void
    {
        try {
            $conn->send(json_encode($message));
            $this->testResults[$type]['messages_sent']++;
            $this->verboseLog("📤 Sent {$type} message: " . json_encode($message));
        } catch (\Exception $e) {
            $this->testResults[$type]['errors'][] = "Failed to send message: " . $e->getMessage();
        }
    }

    private function testAllMessageTypes(): Promise
    {
        // This will be implemented as part of the individual connection tests
        return new Promise(function($resolve) {
            $resolve(true);
        });
    }

    private function runMessageTests(): void
    {
        $this->info("Running additional message type tests...");
        
        // Test with dashboard connection if available
        if (isset($this->connections['dashboard'])) {
            $this->testMessageValidation($this->connections['dashboard'], 'dashboard');
        }
    }

    private function testMessageValidation($conn, string $type): void
    {
        // Test invalid message formats
        $invalidMessages = [
            '{"invalid": "json"}', // Missing type
            '{"type": ""}', // Empty type
            '{"type": "unknown_type"}', // Unknown type
            'invalid json string' // Invalid JSON
        ];

        foreach ($invalidMessages as $invalidMsg) {
            try {
                $conn->send($invalidMsg);
                $this->verboseLog("📤 Sent invalid message test: {$invalidMsg}");
            } catch (\Exception $e) {
                $this->verboseLog("⚠️  Failed to send invalid message (expected): " . $e->getMessage());
            }
        }
    }

    private function displayTestResults(): void
    {
        $totalDuration = microtime(true) - $this->testStartTime;
        
        $this->info("\n" . str_repeat("=", 80));
        $this->info("BareWebsocketServerV2 Test Results");
        $this->info(str_repeat("=", 80));
        
        $totalTests = 0;
        $passedTests = 0;
        $failedTests = 0;

        foreach ($this->testResults as $type => $result) {
            if ($type === 'message_tests') continue;
            
            $totalTests++;
            $status = $result['status'];
            $duration = number_format($result['duration'], 2);
            $errors = count($result['errors']);
            
            $statusIcon = match($status) {
                'COMPLETED' => '✅',
                'CONNECTED' => '🔌',
                'FAILED' => '❌',
                'RUNNING' => '🔄',
                default => '⏳'
            };

            if ($status === 'COMPLETED' && $errors === 0) {
                $passedTests++;
            } else {
                $failedTests++;
            }

            $this->line(sprintf(
                "%s %-20s | %-10s | %6ss | %2d msgs sent | %2d msgs recv | %2d errors",
                $statusIcon,
                $result['description'],
                $status,
                $duration,
                $result['messages_sent'] ?? 0,
                $result['messages_received'] ?? 0,
                $errors
            ));

            if ($errors > 0 && $this->option('verbose')) {
                foreach ($result['errors'] as $error) {
                    $this->error("  ⚠️  {$error}");
                }
            }
        }

        $this->info(str_repeat("-", 80));
        $this->info(sprintf(
            "Total: %d tests | Passed: %d | Failed: %d | Duration: %.2fs",
            $totalTests,
            $passedTests,
            $failedTests,
            $totalDuration
        ));

        if ($failedTests > 0) {
            $this->error("\n❌ Some tests failed. Check the logs for details.");
            $this->info("Run with --verbose for detailed error information.");
        } else {
            $this->info("\n✅ All tests passed successfully!");
        }

        $this->info(str_repeat("=", 80));

        // Close all connections
        foreach ($this->connections as $conn) {
            try {
                $conn->close();
            } catch (\Exception $e) {
                // Ignore close errors
            }
        }

        $this->loop->stop();
    }

    private function verboseLog(string $message): void
    {
        if ($this->option('verbose')) {
            $this->line("[" . date('H:i:s') . "] " . $message);
        }
    }
} 