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

use React\Promise\PromiseInterface;

interface SdpEndpoint extends SessionEndpoint
{
    public function generateOffer();

    public function processAnswer($answer): PromiseInterface;

    public function processOffer($offer): PromiseInterface;

    public function getLocalSessionDescriptor();

    public function getRemoteSessionDescriptor();
}
