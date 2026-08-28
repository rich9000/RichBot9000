<?php

namespace App\WebSocket\Interfaces;

interface WebSocketControlInterface
{
    public function connect(string $url, array $options = []): void;
    public function send(string $message): void;
    public function close(): void;
    public function onMessage(callable $callback): void;
    public function onClose(callable $callback): void;
    public function onError(callable $callback): void;
} 