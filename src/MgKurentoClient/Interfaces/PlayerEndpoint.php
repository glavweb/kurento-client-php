<?php
/*
 * This file is part of the Kurento Client php package.
 *
 * (c) Milan Rukavina
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MgKurentoClient\Interfaces;

use React\Promise\Promise;

interface PlayerEndpoint extends UriEndpoint
{
    public function play(): Promise;

    public function addEndOfStreamListener(callable $listener): Promise;
}
