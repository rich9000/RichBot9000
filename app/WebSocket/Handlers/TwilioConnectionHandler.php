<?php

namespace App\WebSocket\Handlers;

use App\WebSocket\Handlers\Base\BaseConnectionHandler;
use Swoole\WebSocket\Server;
use Illuminate\Support\Facades\Log;

class TwilioConnectionHandler extends BaseConnectionHandler
{
    public function handleConnection(Server $server, int $fd, array $params = []): void
    {
        if (!$this->validateConnection($params)) {
            $server->close($fd);
            return;
        }

        $room = $params['room'];
        $callSid = $params['call_sid'];

        // Check if room exists, if not create it with this Twilio connection as owner
        $existingRoom = $this->tables['rooms']->get($room);
        if (!$existingRoom) {
            $this->tables['rooms']->set($room, [
                'owner_fd' => $fd,
                'created_at' => time(),
                'last_activity' => time(),
                'status' => 'active',
                'clients' => '[]'
            ]);
            Log::info("[TWILIO] Room created with Twilio as owner", [
                'fd' => $fd,
                'room' => $room
            ]);
        }

        // Join room
        $this->joinRoom($fd, $room, [
            'name' => $callSid ?: $room,
            'call_sid' => $callSid,
        ]);

        Log::info("[TWILIO] Twilio client connected", [
            'fd' => $fd,
            'room' => $room,
            'call_sid' => $callSid
        ]);
    }

    public function handleMessage(Server $server, int $fd, array $message): void
    {
        $client = $this->tables['clients']->get($fd);
        if (!$client) {
            Log::error("[TWILIO] Unknown client", ['fd' => $fd]);
            return;
        }

        $room = $client['room'];
        
        // Handle different message types
        switch ($message['type']) {
            case 'connected':
                Log::info("[TWILIO] Client connected event", ['fd' => $fd, 'room' => $room]);
                break;

            case 'start':
                Log::info("[TWILIO] Client start event", ['fd' => $fd, 'room' => $room]);
                if (isset($message['start']['streamSid'])) {
                    $client['stream_sid'] = $message['start']['streamSid'];
                    $this->tables['clients']->set($fd, $client);
                    Log::info("[TWILIO] Stored streamSid for client", [
                        'fd' => $fd, 
                        'room' => $room,
                        'streamSid' => $client['stream_sid']
                    ]);
                }
                break;

            case 'join':
                Log::info("[TWILIO] Client join event", ['fd' => $fd, 'room' => $room]);
                break;

            case 'media':
                $this->broadcastToRoom($room, [
                    'type' => 'media',
                    'data' => $message['data'],
                    'source' => 'twilio'
                ]);
                break;

            case 'dtmf':
                $this->broadcastToRoom($room, [
                    'type' => 'dtmf',
                    'digit' => $message['digit'],
                    'source' => 'twilio'
                ]);
                break;

            case 'speech_started':
                $this->broadcastToRoom($room, [
                    'type' => 'input_audio_buffer.speech_started',
                    'source' => 'twilio'
                ]);
                break;

            case 'speech_stopped':
                $this->broadcastToRoom($room, [
                    'type' => 'input_audio_buffer.speech_stopped',
                    'source' => 'twilio'
                ]);
                break;

            case 'end_call':
                $this->broadcastToRoom($room, [
                    'type' => 'end_call',
                    'source' => 'twilio'
                ]);
                break;

            default:
                Log::warning("[TWILIO] Unknown message type", [
                    'type' => $message['type'],
                    'fd' => $fd
                ]);
        }
    }

    public function handleClose(Server $server, int $fd): void
    {
        $client = $this->tables['clients']->get($fd);
        if ($client) {
            $this->leaveRoom($fd);
            Log::info("[TWILIO] Twilio client disconnected", [
                'fd' => $fd,
                'room' => $client['room']
            ]);
        }
    }

    protected function getConnectionType(): string
    {
        return 'twilio';
    }
} 