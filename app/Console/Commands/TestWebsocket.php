<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use React\EventLoop\Loop;
use Ratchet\Client\Connector as ClientConnector;
use React\Socket\Connector;
use Ratchet\Client\WebSocket;

class TestWebsocket extends Command
{
    protected $signature = 'websocket:test {--token=}';
    protected $description = 'Test WebSocket connections';
    
    private $currentChatId = null;
    private $loop;
    private $richbotConn = null;
    private $openaiConn = null;

    public function handle()
    {
        $this->info("=== WebSocket Test ===\n");
        
        $token = $this->option('token') ?? "5|vSI82152s7xen9bYBE2vqLciUhNJZo6OZPJIRVsO92ebbc8e";
        $this->loop = Loop::get();

        // Configure connector with SSL options
        $connector = new ClientConnector($this->loop, new Connector([
            'tls' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ]
        ]));

        // Connect to richbot WebSocket server
        $richbotUrl = "wss://richbot9000.local:9501/app/" . urlencode($token);
        $this->info("Connecting to richbot: $richbotUrl");

        // Connect to OpenAI WebSocket server
        $openaiUrl = "wss://api.openai.com/v1/realtime?model=gpt-4-turbo-preview";
        $openaiKey = config('services.openai.api_key');
        $this->info("Connecting to OpenAI: $openaiUrl");

        // First connect to richbot
        $connector($richbotUrl)->then(
            function(WebSocket $conn) use ($connector, $openaiUrl, $openaiKey) {
                $this->info("Connected to richbot!");
                $this->richbotConn = $conn;

                // Handle richbot messages
                $conn->on('message', function($msg) use ($conn) {
                    $this->handleRichbotMessage($conn, $msg);
                });

                // Handle richbot close
                $conn->on('close', function($code = null, $reason = null) {
                    $this->info("Richbot connection closed ($code: $reason)");
                    $this->loop->stop();
                });

                // Now connect to OpenAI
                $this->info("Connecting to OpenAI...");
                return $connector($openaiUrl, [], [
                    'Authorization' => 'Bearer ' . $openaiKey,
                    'OpenAI-Beta' => 'realtime=v1',
                    'Content-Type' => 'application/json'
                ]);
            }
        )->then(
            function(WebSocket $conn) {
                $this->info("Connected to OpenAI!");
                $this->openaiConn = $conn;

                // Handle OpenAI messages
                $conn->on('message', function($msg) use ($conn) {
                    $this->handleOpenAIMessage($conn, $msg);
                });

                // Handle OpenAI close
                $conn->on('close', function($code = null, $reason = null) {
                    $this->info("OpenAI connection closed ($code: $reason)");
                });

                // Start chat with test assistant
                $startChat = json_encode([
                    'event' => 'start_chat',
                    'target_type' => 'assistant',
                    'assistant_id' => 'test_assistant'
                ]);
                
                $this->richbotConn->send($startChat);
                $this->info("Sent start_chat event");

                // Handle user input
                $this->loop->addPeriodicTimer(0.1, function() {
                    $this->handleUserInput();
                });
            },
            function(\Exception $e) {
                $this->error("Could not connect to OpenAI: {$e->getMessage()}");
                $this->loop->stop();
            }
        );

        $this->loop->run();
    }

    private function handleRichbotMessage(WebSocket $conn, $msg)
    {
        try {
            $data = json_decode($msg, true);
            $event = $data['event'] ?? null;

            switch ($event) {
                case 'connection_established':
                    $this->info("\nConnected with fd: " . ($data['fd'] ?? 'unknown'));
                    break;

                case 'chat_started':
                    $this->currentChatId = $data['chatId'] ?? null;
                    $this->info("\nChat started with ID: " . $this->currentChatId);
                    $this->info("Assistant info: " . json_encode($data['chatData']['assistant'] ?? [], JSON_PRETTY_PRINT));
                    
                    // Send initial message after a short delay
                    $this->loop->addTimer(1, function() {
                        $this->sendMessage("Hello! Can you help me with something?");
                    });
                    break;

                case 'message':
                    $message = $data['message'] ?? '';
                    $this->info("\nReceived from richbot: " . $message);
                    
                    // Relay assistant messages to OpenAI
                    if (($data['from'] ?? '') === 'assistant' && $this->openaiConn) {
                        $openaiMessage = [
                            'type' => 'message',
                            'message' => [
                                'role' => 'assistant',
                                'content' => $message
                            ]
                        ];
                        $this->openaiConn->send(json_encode($openaiMessage));
                        $this->info("Relayed assistant message to OpenAI");
                    }
                    break;

                case 'state_update':
                    $clients = $data['clients'] ?? [];
                    $chats = $data['activeChats'] ?? [];
                    $this->info("\nState Update:");
                    $this->info("Connected clients: " . count($clients));
                    $this->info("Active chats: " . count($chats));
                    break;

                default:
                    $this->info("\nUnhandled richbot event: $event");
                    $this->info(json_encode($data, JSON_PRETTY_PRINT));
            }
        } catch (\Exception $e) {
            $this->error("Error handling richbot message: " . $e->getMessage());
        }
    }

    private function handleOpenAIMessage(WebSocket $conn, $msg)
    {
        try {
            $data = json_decode($msg, true);
            $this->info("\nReceived from OpenAI: " . json_encode($data, JSON_PRETTY_PRINT));

            // Relay OpenAI messages to richbot
            if (isset($data['message']['content']) && $this->richbotConn && $this->currentChatId) {
                $richbotMessage = [
                    'event' => 'message',
                    'chat_id' => $this->currentChatId,
                    'message' => $data['message']['content'],
                    'from' => 'openai'  // Mark as coming from OpenAI
                ];
                $this->richbotConn->send(json_encode($richbotMessage));
                $this->info("Relayed OpenAI message to richbot");
            }
        } catch (\Exception $e) {
            $this->error("Error handling OpenAI message: " . $e->getMessage());
        }
    }

    private function sendMessage(string $text)
    {
        if (!$this->currentChatId) {
            $this->warn("No active chat to send message to!");
            return;
        }

        // Send to richbot
        $richbotMessage = [
            'event' => 'message',
            'chat_id' => $this->currentChatId,
            'message' => $text
        ];

        $this->info("\nSending message: $text");
        $this->richbotConn->send(json_encode($richbotMessage));

        // Send to OpenAI
        $openaiMessage = [
            'type' => 'message',
            'message' => [
                'role' => 'user',
                'content' => $text
            ]
        ];
        $this->openaiConn->send(json_encode($openaiMessage));
    }

    private function handleUserInput()
    {
        $input = fgets(STDIN);
        if ($input !== false) {
            $text = trim($input);
            if ($text === 'quit' || $text === 'exit') {
                $this->info("Closing connections...");
                if ($this->openaiConn) $this->openaiConn->close();
                if ($this->richbotConn) $this->richbotConn->close();
                $this->loop->stop();
                return;
            }
            if ($text) {
                $this->sendMessage($text);
            }
        }
    }
} 