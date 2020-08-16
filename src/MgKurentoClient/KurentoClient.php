<?php

/*
 * This file is part of the Kurento Client php package.
 *
 * (c) Milan Rukavina
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MgKurentoClient;

use Evenement\EventEmitter;
use MgKurentoClient\WebRtc\Client as WsClient;
use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use React\Promise\Promise;
use React\Stream\Util;

/**
 * KurentoClient
 *
 * @author Milan Rukavina
 */
class KurentoClient extends EventEmitter
{

    /**
     *
     * @var JsonRpc\Client;
     */
    private $jsonRpc = null;


    private $logger = null;

    /**
     * KurentoClient constructor.
     *
     * @param string $websocketUrl
     * @param LoopInterface $loop
     * @param LoggerInterface $logger
     * @param integer $timeout
     */
    public function __construct($websocketUrl, $loop, $logger, $timeout = 30)
    {
        $this->logger  = $logger;
        $this->jsonRpc = new JsonRpc\Client($websocketUrl, $loop, $this->logger, $timeout);

        Util::forwardEvents($this->jsonRpc, $this, [
            WsClient::EVENT_CONNECTING,
            WsClient::EVENT_CONNECTED,
            WsClient::EVENT_CONNECTION_CLOSED,
            WsClient::EVENT_CONNECTION_CLOSED_ABNORMALLY,
            WsClient::EVENT_STREAM_ERROR,
            WsClient::EVENT_CONNECTION_ERROR
        ]);
    }

    /**
     * Creates Client object
     *
     * @return Promise
     */
    public function connect(): Promise
    {
        return $this->jsonRpc->connect()->then(function () {
            return $this;
        });
    }

    /**
     * Creates a new {MediaPipeline} in the media server
     *
     * @param array $params
     * @return Promise
     */
    public function createMediaPipeline(array $params = []): Promise
    {
        return (new MediaPipeline($this->jsonRpc))->build($params);
    }
}
