<?php
/*
 * This file is part of the Kurento Client php package.
 *
 * (c) Milan Rukavina
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MgKurentoClient\WebRtc;

use Evenement\EventEmitter;
use Exception;
use Psr\Log\LoggerInterface;
use Ratchet\Client\Connector;
use Ratchet\Client\WebSocket;
use Ratchet\RFC6455\Messaging\Frame;
use Ratchet\RFC6455\Messaging\MessageInterface;
use React\EventLoop\LoopInterface;
use React\Promise\Promise;


/**
 * Websocket transport layer implementation
 *
 * @author Milan Rukavina
 */
class Client extends EventEmitter
{

    public const        EVENT_CONNECTING                   = 'connecting';
    public const        EVENT_CONNECTED                    = 'connected';
    public const        EVENT_MESSAGE_RECEIVED             = 'message-received';
    public const        EVENT_CONNECTION_CLOSED            = 'connection-closed';
    public const        EVENT_CONNECTION_CLOSED_ABNORMALLY = 'connection-closed-abnormally';
    public const        EVENT_STREAM_ERROR                 = 'stream-error';
    public const        EVENT_CONNECTION_ERROR             = 'connection-error';

    /**
     * @var LoopInterface
     */
    private $loop = null;

    /**
     * @var LoggerInterface
     */
    private $logger = null;

    /**
     * @var string
     */
    private $websocketUrl;

    /**
     * @var WebSocket
     */
    private $connection = null;


    /**
     * @var bool
     */
    private $connecting = false;


    /**
     *
     * Constructor
     *
     * @param string $websocketUrl
     * @param LoopInterface $loop
     * @param LoggerInterface $logger
     */
    public function __construct($websocketUrl, $loop, $logger)
    {
        $this->websocketUrl = $websocketUrl;
        $this->loop         = $loop;
        $this->logger       = $logger;
    }

    /**
     * @return Promise
     */
    public function connect()
    {
        $this->connecting = true;
        $connector        = new Connector($this->loop);
        $this->emit(self::EVENT_CONNECTING);

        return $connector($this->websocketUrl . '?encoding=text')
            ->then(function (WebSocket $connection) {
                $this->logger->info("Connected");
                $this->connection = $connection;
                $this->emit(self::EVENT_CONNECTED, [$connection]);
                $this->connecting = false;

                $connection->on('message', function (MessageInterface $msg) {
                    $this->logger->debug("Got message: {$msg}");
                    $this->emit(self::EVENT_MESSAGE_RECEIVED, [$msg]);
                });

                $connection->on('closed', function ($code = null, $reason = null) {
                    $this->logger->info("Connection closed ({$code} - {$reason})");
                    $this->emit(self::EVENT_CONNECTION_CLOSED, [$code, $reason]);
                    if ($code === Frame::CLOSE_ABNORMAL) {
                        $this->logger->error("Connection closed abnormally({$code} - {$reason})");
                        $this->emit(self::EVENT_CONNECTION_CLOSED_ABNORMALLY, [$code, $reason]);
                        $this->reconnect();
                    }
                });

                $connection->on('error', function ($error) {
                    $this->logger->error("Error: {$error}");
                    $this->emit(self::EVENT_STREAM_ERROR, [$error]);
                });
            }, function (Exception $e) {
                $this->logger->error("Could not connect: {$e->getMessage()}");
                $this->emit(self::EVENT_CONNECTION_ERROR, [$e]);
                $this->connection = null;
                $this->reconnect();
            });
    }

    /**
     * Send message
     *
     * @param string $message
     */
    public function send($message)
    {
        if (!$this->connection) {
            if (!$this->connecting) {
                $this->connect();
            }
            $this->once(self::EVENT_CONNECTED, function () use ($message) {
                $this->_send($message);
            });
        } else {
            $this->_send($message);
        }
    }

    private function reconnect(): void
    {
        $this->logger->info("Reconnect after 5 seconds");
        $this->loop->addTimer(5, function () {
            $this->logger->info("Reconnecting");
            $this->connect();
        });
    }

    /**
     * @param string $message
     */
    private function _send(string $message): void
    {
        $this->logger->debug("Sending message: {$message}");
        $this->connection->send($message);
    }

    public function isConnected(): bool
    {
        return $this->connection !== null;
    }
}
