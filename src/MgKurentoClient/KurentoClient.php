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

use Psr\Log\LoggerInterface;
use React\EventLoop\LoopInterface;
use React\Promise\PromiseInterface;

/**
 * KurentoClient
 *
 * @author Milan Rukavina
 */
class KurentoClient
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
     */
    public function __construct($websocketUrl, $loop, $logger)
    {
        $this->logger  = $logger;
        $this->jsonRpc = new JsonRpc\Client($websocketUrl, $loop, $this->logger);
    }

    /**
     * Creates Client object
     *
     * @return PromiseInterface
     */
    public function connect(): PromiseInterface
    {
        return $this->jsonRpc->connect()->then(function () {
            return $this;
        });
    }

    /**
     * Creates a new {MediaPipeline} in the media server
     *
     * @param array $params
     * @return PromiseInterface
     */
    public function createMediaPipeline(array $params = []): PromiseInterface
    {
        return (new MediaPipeline($this->jsonRpc))->build($params);
    }
}
