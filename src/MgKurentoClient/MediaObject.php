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

use React\Promise\PromiseInterface;

class MediaObject implements Interfaces\MediaObject
{
    protected $id = null;

    protected $pipeline = null;

    public function __construct(Interfaces\MediaPipeline $pipeline)
    {
        $this->pipeline = $pipeline;
    }

    public function release(): PromiseInterface
    {
        return $this->remoteRelease();
    }

    public function build(array $params = []): PromiseInterface
    {
        return $this->remoteCreate($this->getRemoteTypeName(), $params)
            ->then(function () {
                return $this;
            });
    }

    public function remoteCreate($remoteType, array $params = []): PromiseInterface
    {
        $localParams = ($this->pipeline === $this) ? [] : ['mediaPipeline' => $this->pipeline->getId()];

        return $this->pipeline->getJsonRpc()
            ->sendCreate($remoteType, array_merge($localParams, $params))
            ->then(function ($data) {
                if (isset($data['value'])) {
                    $this->id = $data['value'];
                }

                return $data;
            });
    }

    public function remoteInvoke($operation, $operationParams): PromiseInterface
    {
        return $this->pipeline->getJsonRpc()
            ->sendInvoke($this->getId(), $operation, $operationParams);
    }

    public function remoteRelease(): PromiseInterface
    {
        return $this->pipeline->getJsonRpc()
            ->sendRelease($this->getId());
    }

    public function remoteUnsubscribe($subscriptionId): PromiseInterface
    {
        return $this->pipeline->getJsonRpc()
            ->sendUnsubscribe($subscriptionId);
    }

    public function remoteSubscribe($type, $onEvent): PromiseInterface
    {
        return $this->pipeline->getJsonRpc()
            ->sendSubscribe($this->getId(), $type, $onEvent);
    }

    public function getId()
    {
        return $this->id;
    }

    public function getMediaPipeline()
    {
        return $this->pipeline;
    }

    public function getParent()
    {
    }

    protected function getRemoteTypeName()
    {
        $fullName = get_class($this);
        $parts    = explode("\\", $fullName);

        return $parts[count($parts) - 1];
    }
}
