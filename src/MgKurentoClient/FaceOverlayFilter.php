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

class FaceOverlayFilter extends MediaElement implements Interfaces\FaceOverlayFilter
{
    public function setOverlayedImage($uri, $offsetXPercent, $offsetYPercent, $widthPercent, $heightPercent): Promise
    {
        return $this->remoteInvoke('setOverlayedImage', [
            "uri"            => $uri,
            "offsetXPercent" => $offsetXPercent,
            "offsetYPercent" => $offsetYPercent,
            "widthPercent"   => $widthPercent,
            "heightPercent"  => $heightPercent
        ]);
    }
}
