<?php

namespace App\WebSocket\Router;

use App\WebSocket\Handlers\Interfaces\ConnectionHandlerInterface;
use Swoole\WebSocket\Server;
use Illuminate\Support\Facades\Log;

class ConnectionRouter
{
    private $handlers = [];
    private $tables;

    public function __construct(array $tables)
    {
        $this->tables = $tables;
    }

    public function registerHandler(string $type, ConnectionHandlerInterface $handler): void
    {
        $this->handlers[$type] = $handler;
    }

    public function getHandler(string $type): ?ConnectionHandlerInterface
    {
        return $this->handlers[$type] ?? null;
    }

    public function route(Server $server, $request): void
    {
        $path = parse_url($request->server['request_uri'], PHP_URL_PATH);
        $parts = explode('/', trim($path, '/'));
        $type = $parts[0] ?? null;

        if (isset($this->handlers[$type])) {
            $handler = $this->handlers[$type];
            $handler->handleConnection($server, $request->fd, [
                'room' => $parts[1] ?? null,
                'assistant_id' => $parts[2] ?? null,
                'call_sid' => $parts[3] ?? null,
                'api_token' => $request->get['token'] ?? null,
            ]);
        } else {
            Log::error("[ROUTER] Unknown connection type", ['type' => $type]);
            $server->close($request->fd);
        }
    }

    public function handleClose(Server $server, int $fd): void
    {
        $client = $this->tables['clients']->get($fd);
        if ($client && isset($this->handlers[$client['type']])) {
            $this->handlers[$client['type']]->handleClose($server, $fd);
        }
    }
} 