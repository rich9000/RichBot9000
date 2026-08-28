<?php

namespace App\WebSocket\Handlers\Interfaces;

use Swoole\WebSocket\Server;

interface ConnectionHandlerInterface
{
    public function handleConnection(Server $server, int $fd, array $params = []): void;
    public function handleMessage(Server $server, int $fd, array $message): void;
    public function handleClose(Server $server, int $fd): void;
} 