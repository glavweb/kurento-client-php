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

use React\Promise\Promise;

class MediaObject implements Interfaces\MediaObject
{
    protected $id       = null;

    protected $pipeline = null;

    public function __construct(Interfaces\MediaPipeline $pipeline)
    {
        $this->pipeline = $pipeline;
    }

    public function release(): Promise
    {
        return $this->remoteRelease();
    }

    public function build(array $params = [], array $properties = []): Promise
    {
        return $this->remoteCreate($this->getRemoteTypeName(), $params, $properties)
            ->then(function () {
                return $this;
            });
    }

    public function remoteCreate($remoteType, array $params = [], array $properties = []): Promise
    {
        $localParams = ($this->pipeline === $this) ? [] : ['mediaPipeline' => $this->pipeline->getId()];

        return $this->pipeline->getJsonRpc()
            ->sendCreate($remoteType, array_merge($localParams, $params), $properties)
            ->then(function ($data) {
                if (isset($data['value'])) {
                    $this->id = $data['value'];
                }

                return $data;
            });
    }

    public function remoteInvoke($operation, $operationParams): Promise
    {
        return $this->pipeline->getJsonRpc()
            ->sendInvoke($this->getId(), $operation, $operationParams);
    }

    public function remoteRelease(): Promise
    {
        return $this->pipeline->getJsonRpc()
            ->sendRelease($this->getId());
    }

    public function remoteUnsubscribe($subscriptionId): Promise
    {
        return $this->pipeline->getJsonRpc()
            ->sendUnsubscribe($subscriptionId);
    }

    public function remoteSubscribe($type, $onEvent): Promise
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
