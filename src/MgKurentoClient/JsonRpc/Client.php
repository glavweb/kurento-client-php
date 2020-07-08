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

use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use React\EventLoop\TimerInterface;
use React\Promise\Deferred;
use React\Promise\Promise;

/**
 * JSON RPC implementation
 *
 * @author Milan Rukavina
 *
 */
class Client
{
    protected $id = 0;

    /**
     *
     * @var \MgKurentoClient\WebRtc\Client
     */
    protected $wsClient;

    protected $sessionId = null;

    protected $deferred = [];

    protected $subscriptions = [];

    protected $logger = null;

    /**
     * @var LoopInterface
     */
    private $loop;

    /**
     * @var TimerInterface
     */
    private $pingTimer;

    /**
     * Client constructor.
     *
     * @param string $websocketUrl
     * @param LoopInterface $loop
     * @param LoggerInterface $logger
     */
    public function __construct($websocketUrl, $loop, $logger)
    {
        $this->loop     = $loop;
        $this->logger   = $logger;
        $this->wsClient = new \MgKurentoClient\WebRtc\Client($websocketUrl, $loop, $this->logger);
        $this->wsClient->on('message', function ($data) {
            $this->receive(json_decode($data, true));
        });
        $this->wsClient->once('connect', function () {
            $this->startPing();
        });
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
     * @throws Exception
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
            $deferred->reject($data['error']);
            unset($this->deferred[$data['id']]);

            return;
        }

        throw new Exception('Json deferred not found');
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

        return $deferred->promise();
    }

    private function startPing()
    {
        $this->pingTimer = $this->loop->addPeriodicTimer(60, function () {
            if ($this->wsClient->isConnected()) {
                $this->send('ping', []);
            }
        });
    }
}
