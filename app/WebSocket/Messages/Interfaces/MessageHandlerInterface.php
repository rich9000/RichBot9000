<?php

namespace App\WebSocket\Messages\Interfaces;

use Swoole\WebSocket\Server;

interface MessageHandlerInterface
{
    public function handle(Server $server, int $fd, array $message): void;
} 