<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Symfony\Component\Process\Process;
use Illuminate\Support\Facades\Response;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Illuminate\Support\Facades\Artisan;

class WebRTCController extends Controller
{
    public function status()
    {
        $services = [
            'websocket' => [
                'name' => 'WebRTC WebSocket Server',
                'check_command' => 'ps aux | grep "[w]ebrtc:websocket"'
            ],
            'coturn' => [
                'name' => 'Coturn STUN/TURN Server',
                'check_command' => 'ps aux | grep "[t]urnserver"'
            ]
        ];

        $status = [];
        foreach ($services as $key => $service) {
            $process = new Process(['bash', '-c', $service['check_command']]);
            $process->setTty(false);
            $process->setTimeout(5);
            $process->run();

            $isRunning = $process->isSuccessful();
            $pid = null;
            if ($isRunning) {
                preg_match('/\s+(\d+)\s+/', $process->getOutput(), $matches);
                $pid = $matches[1] ?? null;
            }

            $status[$key] = [
                'name' => $service['name'],
                'running' => $isRunning,
                'pid' => $pid
            ];
        }

        return response()->json($status);
    }

    public function startService($service)
    {
        $services = [
            'websocket' => 'webrtc:websocket --daemon',
            'coturn' => 'turnserver -c /etc/turnserver.conf'
        ];

        if (!isset($services[$service])) {
            return response()->json(['error' => 'Invalid service'], 400);
        }

        $process = new Process(['php', 'artisan', $services[$service]]);
        $process->setTty(false);
        $process->setTimeout(5);
        $process->run();

        if (!$process->isSuccessful()) {
            return response()->json(['error' => 'Failed to start service'], 500);
        }

        return response()->json(['message' => 'Service started successfully']);
    }

    public function stopService($service)
    {
        $services = [
            'websocket' => 'pkill -f "webrtc:websocket"',
            'coturn' => 'pkill -f "turnserver"'
        ];

        if (!isset($services[$service])) {
            return response()->json(['error' => 'Invalid service'], 400);
        }

        $process = new Process(['bash', '-c', $services[$service]]);
        $process->setTty(false);
        $process->setTimeout(5);
        $process->run();

        return response()->json(['message' => 'Service stopped successfully']);
    }

    public function restartService($service)
    {
        $this->stopService($service);
        sleep(2); // Wait for service to stop
        return $this->startService($service);
    }

    public function widget()
    {
        // Generate TURN server credentials
        $username = time() . ':richbot9000';
        $credential = hash_hmac('sha1', $username, env('TURN_SECRET'));

        $turnConfig = [
            'iceServers' => [
                [
                    'urls' => [
                        'stun:'.config('app.turn_server').':3478',
                        'turn:'.config('app.turn_server').':3478'
                    ],
                    'username' => $username,
                    'credential' => $credential
                ]
            ]
        ];

        return view('webapp.webrtc._widget', compact('turnConfig'));
    }

    public function dashboard()
    {
        // Generate TURN server credentials
        $username = time() . ':richbot9000';
        $credential = hash_hmac('sha1', $username, env('TURN_SECRET'));

        $turnConfig = [
            'iceServers' => [
                [
                    'urls' => [
                        'stun:'.config('app.turn_server').':3478',
                        'turn:'.config('app.turn_server').':3478'
                    ],
                    'username' => $username,
                    'credential' => $credential
                ]
            ]
        ];

        return view('webapp.webrtc._dashboard', compact('turnConfig'));
    }

    public function signal(Request $request)
    {
        // This example assumes a simple one-to-one communication.
        // You would need to store the offer in a session or database and retrieve it for the peer.

        $offer = $request->input('offer');

        // Typically, you would send this offer to the other peer.
        // Here we just return an example answer.

        $answer = $this->createAnswer($offer); // Implement your logic here

        return response()->json(['answer' => $answer]);
    }

    public function handleIceCandidate(Request $request)
    {
        $candidate = $request->input('candidate');

        // Store the candidate or send it to the other peer
        // Implement your logic here

        return response()->json(['status' => 'success']);
    }

    private function createAnswer($offer)
    {
        // Use a WebRTC library or service to generate an answer
        // This is where the backend might interact with a TURN/STUN server if needed

        // For simplicity, we'll just return the same offer in this example
        return $offer;
    }

    public function getTurnCredentials()
    {
        // Generate TURN server credentials
        $username = time() . ':richbot9000';
        $credential = hash_hmac('sha1', $username, env('TURN_SECRET'));

        $turnConfig = [
            'iceServers' => [
                [
                    'urls' => [
                        'stun:'.config('app.turn_server').':3478',
                        'turn:'.config('app.turn_server').':3478'
                    ],
                    'username' => $username,
                    'credential' => $credential
                ]
            ]
        ];

        return response()->json($turnConfig);
    }

    public function getRooms()
    {
        try {
            $process = new Process(['php', 'artisan', 'webrtc:websocket', '--get-rooms']);
            $process->setTty(false);
            $process->setTimeout(5);
            $process->run();

            if (!$process->isSuccessful()) {
                return response()->json(['error' => 'Failed to get room status'], 500);
            }

            $output = $process->getOutput();
            $rooms = json_decode($output, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json(['error' => 'Invalid room data format'], 500);
            }

            return response()->json($rooms);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to get room status: ' . $e->getMessage()], 500);
        }
    }
}
