<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\RemoteDeviceWebsocket;

class StartRemoteDeviceWebsocket extends Command
{
    protected $signature = 'websocket:remote-device {--host=0.0.0.0} {--port=9502}';
    protected $description = 'Start the RemoteDevice WebSocket server';

    public function handle()
    {
        $host = $this->option('host', '0.0.0.0');
        $port = (int) $this->option('port', 9502);

        $this->info("Starting RemoteDevice WebSocket server on {$host}:{$port}");

        try {
            $server = new RemoteDeviceWebsocket($host, $port);
            $server->start();
        } catch (\Exception $e) {
            $this->error("Failed to start server: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
} 