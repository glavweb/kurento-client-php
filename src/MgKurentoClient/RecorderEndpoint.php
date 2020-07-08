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

class RecorderEndpoint extends MediaElement implements Interfaces\RecorderEndpoint
{
    public function record(): Promise
    {
        return $this->remoteInvoke('record', []);
    }
}
