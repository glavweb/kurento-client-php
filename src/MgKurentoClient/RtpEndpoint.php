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
use function React\Promise\reject;

class RtpEndpoint extends MediaElement implements Interfaces\RtpEndpoint
{
    public function generateOffer()
    {
        // TODO: Implement generateOffer() method.
    }

    public function processAnswer($answer): Promise
    {
        // TODO: Implement processAnswer() method.
        return reject();

    }

    public function processOffer($offer): Promise
    {
        // TODO: Implement processOffer() method.
        return reject();
    }

    public function getLocalSessionDescriptor()
    {
        // TODO: Implement getLocalSessionDescriptor() method.
    }

    public function getRemoteSessionDescriptor()
    {
        // TODO: Implement getRemoteSessionDescriptor() method.
    }
}
