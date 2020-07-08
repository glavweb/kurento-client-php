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

class MediaElement extends MediaObject implements Interfaces\MediaElement
{
    protected $sinks = [];

    protected $sources = [];

    public function connect(Interfaces\MediaElement $sink): Promise
    {
        return $this->remoteInvoke('connect', ['sink' => $sink->getId()])
            ->then(function ($data) use ($sink) {
                $this->sinks[] = $sink;
                $sink->addSource($this);

                return $data;
            });
    }

    public function addSource(Interfaces\MediaElement $source)
    {
        $this->sources[] = $source;
    }

    public function getMediaSinks()
    {
        return $this->sinks;
    }

    public function getMediaSources()
    {
        return $this->sources;
    }
}
