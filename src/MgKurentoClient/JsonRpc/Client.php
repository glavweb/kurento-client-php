<?php
/*
 * This file is part of the Kurento Client php package.
 *
 * (c) Milan Rukavina
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MgKurentoClient\JsonRpc;

use Evenement\EventEmitter;
use MgKurentoClient\WebRtc\Client as WsClient;
use Psr\Log\LoggerInterface;
use Ratchet\RFC6455\Messaging\MessageInterface;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;
use React\Promise\Deferred;
use React\Promise\Promise;
use React\Stream\Util;
use function json_decode;

/**
 * JSON RPC implementation
 *
 * @author Milan Rukavina
 *
 */
class Client extends EventEmitter
{
    protected $id = 0;

    /**
     *
     * @var WsClient
     */
    protected $wsClient;

    protected $sessionId     = null;

    protected $deferred      = [];

    protected $subscriptions = [];

    protected $logger        = null;

    /**
     * @var LoopInterface
     */
    private $loop;

    /**
     * @var TimerInterface
     */
    private $pingTimer;

    /**
     * @var int
     */
    private $timeout;

    /**
     * @var string
     */
    private $websocketUrl;

    /**
     * Client constructor.
     *
     * @param string $websocketUrl
     * @param LoopInterface $loop
     * @param LoggerInterface $logger
     * @param int $timeout
     */
    public function __construct($websocketUrl, $loop, $logger, $timeout)
    {
        $this->websocketUrl = $websocketUrl;
        $this->loop     = $loop;
        $this->logger   = $logger;
        $this->timeout      = $timeout;

        $this->wsClient = new WsClient($websocketUrl, $loop, $this->logger);
        $this->wsClient->on(WsClient::EVENT_MESSAGE_RECEIVED, function (MessageInterface $data) {
            $this->receive(json_decode($data, true));
        });
        $this->wsClient->on(WsClient::EVENT_CONNECTED, function () {
            $this->startPing();
        });
        $this->wsClient->on(WsClient::EVENT_CONNECTION_CLOSED, function () {
            $this->stopPing();
            $this->rejectAllPromises();
        });

        Util::forwardEvents($this->wsClient, $this, [
            WsClient::EVENT_CONNECTING,
            WsClient::EVENT_CONNECTED,
            WsClient::EVENT_CONNECTION_CLOSED,
            WsClient::EVENT_CONNECTION_CLOSED_ABNORMALLY,
            WsClient::EVENT_STREAM_ERROR,
            WsClient::EVENT_CONNECTION_ERROR
        ]);
    }

    /**
     * @return Promise
     */
    public function connect()
    {
        return $this->wsClient->connect();
    }

    /**
     * Receive data
     *
     * @param array $data
     * @return mixed
     * @throws KurentoClientException
     */
    public function receive($data)
    {
        //set session
        if (isset($data['result']['sessionId'])) {
            $this->sessionId = $data['result']['sessionId'];
        }
        //onEvent?
        if (isset($data['method']) && $data['method'] == 'onEvent') {
            $value = $data['params']['value'];
            $key   = $value['object'] . '__' . $value['type'];
            if (isset($this->subscriptions[$key])) {
                $onEvent = $this->subscriptions[$key]['callback'];
                $onEvent($value['data']);
            }

            return;
        }
        /** @var Deferred $deferred */
        if (array_key_exists('result', $data) && isset($data['id']) && isset($this->deferred[$data['id']])) {
            $deferred = $this->deferred[$data['id']];
            $deferred->resolve($data['result']);
            unset($this->deferred[$data['id']]);

            return;
        }
        if (isset($data['error']) && isset($data['id']) && isset($this->deferred[$data['id']])) {
            $deferred = $this->deferred[$data['id']];
            $error    = $data['error'];
            $deferred->reject(new KurentoClientException($error['message'] ?? '', $error['code'] ?? 0, $error['data'] ?? null));
            unset($this->deferred[$data['id']]);

            return;
        }

        throw new KurentoClientException('Json deferred not found');
    }

    /**
     * Create method
     *
     * @param string $type
     * @param array $creationParams
     * @return Promise
     */
    public function sendCreate($type, $creationParams): Promise
    {
        $message = [
            'type' => $type
        ];
        if (isset($creationParams) && count($creationParams)) {
            $message['constructorParams'] = $creationParams;
        }

        return $this->send('create', $message);
    }

    /**
     * Invoke method
     *
     * @param string $object
     * @param string $operation
     * @param array $operationParams
     * @return Promise
     */
    public function sendInvoke($object, $operation, $operationParams): Promise
    {
        return $this->send('invoke', [
            'object'          => $object,
            'operation'       => $operation,
            'operationParams' => $operationParams
        ]);
    }

    /**
     * Release method
     *
     * @param string $object
     * @return Promise
     */
    public function sendRelease($object): Promise
    {
        return $this->send('release', [
            'object' => $object
        ]);
    }

    /**
     * Subscribe method
     *
     * @param string $object
     * @param string $type
     * @param string $onEvent
     * @return Promise
     */
    public function sendSubscribe($object, $type, $onEvent): Promise
    {
        return $this->send('subscribe', [
            'object' => $object,
            'type'   => $type
        ])
            ->then(function ($data) use ($object, $type, $onEvent) {
                $key                       = $object . '__' . $type;
                $this->subscriptions[$key] = [
                    'id'       => $data['value'],
                    'callback' => $onEvent
                ];

                return $data['value'];
            });
    }

    /**
     * Unsubscribe method
     *
     * @param string $subscriptionId
     * @return Promise
     */
    public function sendUnsubscribe($subscriptionId): Promise
    {
        return $this->send('unsubscribe', [
            'subscription' => $subscriptionId
        ])
            ->then(function ($data) use ($subscriptionId) {
                foreach ($this->subscriptions as $key => $subscription) {
                    if ($subscription['id'] === $subscriptionId) {
                        unset($this->subscriptions[$key]);
                        break;
                    }
                }

                return $data;
            });
    }

    /**
     * Send method
     *
     * @param string $method
     * @param array $params
     * @return Promise
     */
    protected function send($method, $params): Promise
    {
        $this->id++;
        if (isset($this->sessionId)) {
            $params['sessionId'] = $this->sessionId;
        }

        $data = [
            "jsonrpc" => "2.0",
            "id"      => $this->id,
            "method"  => $method,
            "params"  => $params
        ];
        $this->wsClient->send(json_encode($data, JSON_UNESCAPED_SLASHES));

        $deferred                  = new Deferred();
        $this->deferred[$this->id] = $deferred;

        $timer = $this->loop->addTimer($this->timeout, function () use ($deferred) {
            $deferred->reject(new KurentoClientException('RPC timeout'));
        });

        return $deferred->promise()
            ->always(function () use ($timer) {
                $this->loop->cancelTimer($timer);
            });
    }

    private function startPing()
    {
        $this->pingTimer = $this->loop->addPeriodicTimer(60, function () {
            if ($this->wsClient->isConnected()) {
                $this->send('ping', []);
            }
        });
    }

    private function stopPing()
    {
        $this->loop->cancelTimer($this->pingTimer);
    }

    private function rejectAllPromises()
    {
        /** @var Deferred $deferredItem */
        foreach ($this->deferred as $deferredItem) {
            $deferredItem->reject(new KurentoClientException('Connection closed'));
        }
        $this->deferred = [];
    }
}
