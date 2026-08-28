<?php

namespace App\WebSocket\Services;

use App\WebSocket\Interfaces\WebSocketControlInterface;
use React\EventLoop\LoopInterface;
use Ratchet\Client\Connector;
use React\Socket\Connector as ReactConnector;
use Illuminate\Support\Facades\Log;

class WebSocketControlService implements WebSocketControlInterface
{
    protected $connection;
    protected $loop;
    protected $onMessage;
    protected $onClose;
    protected $onError;
    protected $onOpen;

    public function __construct(LoopInterface $loop)
    {
        $this->loop = $loop;
    }

    public function connect(string $url, array $options = []): void
    {
        $connector = new Connector($this->loop, new ReactConnector($this->loop, [
            'tls' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ]));

        $connector($url)->then(function ($conn) {
            $this->connection = $conn;
            
            if (isset($this->onOpen)) {
                call_user_func($this->onOpen, $conn);
            }
            
            $conn->on('message', function ($msg) {
                if (isset($this->onMessage)) {
                    call_user_func($this->onMessage, $msg);
                }
            });
            
            $conn->on('close', function ($code = null, $reason = null) {
                if (isset($this->onClose)) {
                    call_user_func($this->onClose, $code, $reason);
                }
            });
            
            $conn->on('error', function ($e) {
                if (isset($this->onError)) {
                    call_user_func($this->onError, $e);
                }
            });
        }, function ($e) {
            Log::error("[WebSocketControlService] Connection failed", [
                'error' => $e->getMessage(),
                'url' => $url
            ]);
            if (isset($this->onError)) {
                call_user_func($this->onError, $e);
            }
        });
    }

    public function send(string $message): void
    {
        if ($this->connection) {
            $this->connection->send($message);
        } else {
            Log::warning("[WebSocketControlService] Attempted to send message without connection");
        }
    }

    public function close(): void
    {
        if ($this->connection) {
            $this->connection->close();
        }
    }

    public function onMessage(callable $callback): void
    {
        $this->onMessage = $callback;
    }

    public function onClose(callable $callback): void
    {
        $this->onClose = $callback;
    }

    public function onError(callable $callback): void
    {
        $this->onError = $callback;
    }

    public function onOpen(callable $callback): void
    {
        $this->onOpen = $callback;
    }
} 