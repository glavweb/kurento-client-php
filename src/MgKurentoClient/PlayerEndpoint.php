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

class PlayerEndpoint extends MediaElement implements Interfaces\PlayerEndpoint
{
    public function play(): PromiseInterface
    {
        return $this->remoteInvoke('play', []);
    }

    public function addEndOfStreamListener(callable $listener): PromiseInterface
    {
        return $this->remoteSubscribe('EndOfStream', $listener);
    }
}
