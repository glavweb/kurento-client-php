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

interface MediaObject
{
    public function build(array $params = []): PromiseInterface;

    public function release(): PromiseInterface;

    public function getId();

    public function getMediaPipeline();

    public function getParent();
}
