<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use React\EventLoop\Factory;
use Ratchet\Client\Connector;
use React\Socket\Connector as ReactConnector;
use Illuminate\Support\Facades\Log;
use FFMpeg\FFMpeg;
class BareRemoteRichbotClient extends Command
{
    protected $signature = 'rr:client {--device-id=} {--capabilities=}';
    protected $description = 'Run a remote richbot WebSocket client using React and Ratchet';

    private $loop;
    private $connector;
    private $client;
    private $deviceId;
    private $capabilities;
    private $isConnected = false;
    private $lastPing = 0;
    private $reconnectAttempts = 0;
    private $maxReconnectAttempts = 5;
    private $reconnectDelay = 5; // seconds
    private $bareUrl;

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        //$this->deviceId = $this->option('device-id') ?: 'device-' . uniqid();
        $this->deviceId = 'device-workstation1';

        $this->capabilities = json_decode($this->option('capabilities') ?: '[]', true);
        $this->bareUrl = "wss://".config('app.domain').":".config('app.ws_port_alt')."/remote-richbot/{$this->deviceId}";

        if (empty($this->capabilities)) {
            $this->capabilities = [
                'audio' => true,
                'video' => true,
                'screen' => true,
                'text' => true
            ];
        }

        $this->info("Starting remote richbot WebSocket client...");
        $this->info("Device ID: {$this->deviceId}");
        $this->info("Capabilities: " . json_encode($this->capabilities));

        try {

            $this->loop = Factory::create();    
            $this->connector = new Connector($this->loop, new ReactConnector($this->loop, [
                'tls' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true 
            ]
            ]));

            $this->connect();
            $this->startHeartbeat();
            $this->startCommandListener();
            $this->loop->run();
        } catch (\Exception $e) {
            $this->error("Failed to start client: " . $e->getMessage());
            Log::error("Remote richbot WebSocket client error", [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    private function connect()
    {
        $connector = $this->connector;
        $connector($this->bareUrl)->then(
            function($conn) {
                $this->client = $conn;
                $this->isConnected = true;
                $this->reconnectAttempts = 0;
                $this->lastPing = time();
                
                $this->info("Connected to WebSocket server");
                
                // Register device
                $this->sendMessage([
                    'type' => 'device_register',
                    'device_id' => $this->deviceId,
                    'capabilities' => $this->capabilities
                ]);

                $conn->on('message', function($msg) {
                    $this->handleMessage(json_decode($msg, true));
                });

                $conn->on('close', function() {
                    $this->isConnected = false;
                    $this->info("Connection closed");
                    $this->attemptReconnect();
                });
            },
            function($e) {
                $this->error("Could not connect: {$e->getMessage()}");
                $this->attemptReconnect();
            }
        );
    }

    private function handleMessage($message)
    {
        if (!isset($message['type'])) {
            $this->error("Received message without type");
            return;
        }

        switch ($message['type']) {
            case 'welcome':
                $this->info("Server sent welcome message");
                break;

            case 'device_registered':
                $this->info("Device registered successfully");
                break;

            case 'device_command':
                $this->handleDeviceCommand($message);
                break;

            case 'ping':
                $this->handlePing($message);
                break;

            case 'error':
                $this->error("Server error: " . ($message['error'] ?? 'Unknown error'));
                break;

            default:
                $this->info("Received unknown message type: " . $message['type']);
        }
    }

    private function handleDeviceCommand($message)
    {
        if (!isset($message['command'])) {
            $this->error("Received device command without command type");
            return;
        }

        $command = $message['command'];
        $parameters = $message['parameters'] ?? [];

        $this->info("Received command: {$command}");
        $this->info("Parameters: " . json_encode($parameters));

        switch ($command) {
            case 'start_audio':
                $this->startAudioCapture();
                break;

            case 'stop_audio':
                $this->stopAudioCapture();
                break;

            case 'start_video':
                $this->startVideoCapture();
                break;

            case 'stop_video':
                $this->stopVideoCapture();
                break;

            case 'start_screen':
                $this->startScreenCapture();
                break;

            case 'stop_screen':
                $this->stopScreenCapture();
                break;

            case 'capture_image':
                $image = $this->captureImage();

                //dd($message);

                if($image) {

                    Log::info("Captured image", [
                        'image' => strlen($image),
                        'format' => 'jpg'
                    ]);

                   

                    $this->sendMessage([
                        'type' => 'device_command_response',
                        'device_id' => $this->deviceId,
                        'command' => 'capture_image',
                        'target_fd' => $message['source_fd'],
                        'data' => base64_encode($image),
                        'format' => 'jpg'
                    ]);
                }

                break;

            case 'send_text':
                if (isset($parameters['text'])) {
                    $this->sendText($parameters['text']);
                }
                break;

            default:
                $this->error("Unknown command: {$command}");
        }
    }

    private function handlePing($message)
    {
        $this->lastPing = time();
        $this->sendMessage([
            'type' => 'pong',
            'time' => time(),
            'device_id' => $this->deviceId
        ]);
    }

    private function sendMessage($message)
    {
        if (!$this->isConnected) {
            $this->error("Cannot send message: Not connected");
            return;
        }

        try {
            $this->client->send(json_encode($message));
        } catch (\Exception $e) {
            $this->error("Failed to send message: " . $e->getMessage());
            Log::error("Failed to send message", [
                'message' => $message,
                'error' => $e->getMessage()
            ]);
        }
    }

    private function startHeartbeat()
    {
        $this->loop->addPeriodicTimer(30, function() {
            if ($this->isConnected) {
                $this->info("Heartbeat - Connection active for " . (time() - $this->lastPing) . " seconds");
                $this->sendMessage([
                    'type' => 'ping',
                    'time' => time(),
                    'device_id' => $this->deviceId
                ]);
            } else {
                $this->warn("Heartbeat - Not connected");
            }
        });
    }

    private function startCommandListener()
    {
        $this->info("Starting command listener (type 'exit' to quit)");
        
        $this->loop->addPeriodicTimer(1, function() {
            $read = [STDIN];
            $write = null;
            $except = null;
            
            if (stream_select($read, $write, $except, 0) > 0) {
                if (PHP_OS === 'WINNT') {
                    $command = fgets(STDIN);
                } else {
                    $command = readline('Enter command: ');
                }
                
                if ($command === "exit\n" || $command === 'exit') {
                    $this->info("Exiting...");
                    $this->loop->stop();
                    return;
                }

                // Parse command and parameters
                $parts = explode(' ', trim($command));
                $cmd = array_shift($parts);
                $params = $parts;

                $this->info("Sending command: {$cmd}");
                $this->info("Parameters: " . json_encode($params));

                $this->sendMessage([
                    'type' => 'device_command',
                    'command' => $cmd,
                    'parameters' => $params
                ]);
            }
        });
    }

    private function attemptReconnect()
    {
        if ($this->reconnectAttempts >= $this->maxReconnectAttempts) {
            $this->error("Max reconnection attempts reached");
            return;
        }

        $this->reconnectAttempts++;
        $delay = $this->reconnectDelay * $this->reconnectAttempts;
        
        $this->info("Attempting to reconnect in {$delay} seconds...");
        
        $this->loop->addTimer($delay, function() {
            $this->connect();
        });
    }

    // Media capture methods
    private function startAudioCapture()
    {
        $this->info("Starting audio capture");
    }

    private function stopAudioCapture()
    {
        $this->info("Stopping audio capture");
    }

    private function startVideoCapture()
    {
        $this->info("Starting video capture");
    }

    private function stopVideoCapture()
    {
        $this->info("Stopping video capture");
    }

    private function startScreenCapture()
    {
        $this->info("Starting screen capture");
    }

    private function stopScreenCapture()
    {
        $this->info("Stopping screen capture");
    }

    private function captureImage()
    {
        $this->info("Capturing image");
// Define the temporary file path
$imagePath = storage_path('app/captured_image.jpg');

// Command to capture the image using fswebcam
$captureCommand = "fswebcam -r 640x480 --no-banner {$imagePath}";

// Execute the command
exec($captureCommand, $output, $resultCode);

if ($resultCode !== 0) {
    $this->error('Failed to capture image. Make sure fswebcam is installed and the webcam is connected.');
    return 1;
}

$this->info('Image captured successfully.');

        // Read the image file
        $imageData = file_get_contents($imagePath);

        return $imageData;
    }

    private function sendText($text)
    {
        $this->sendMessage([
            'type' => 'device_text',
            'device_id' => $this->deviceId,
            'text' => $text
        ]);
    }
}