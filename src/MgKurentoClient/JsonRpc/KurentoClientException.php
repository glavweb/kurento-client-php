<?php
/*
 * This file is part of the Kurento Client php package.
 *
 * (c) Milan Rukavina
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MgKurentoClient\JsonRpc;

class KurentoClientException extends \Exception
{
    /**
     * @var mixed
     */
    private $data;

    /**
     * Exception constructor.
     *
     * @param $message
     * @param $code
     * @param mixed $data
     */
    public function __construct($message, $code = 0, $data = null)
    {
        $this->data = $data;

        return parent::__construct($message, $code);
    }


    /**
     * @return mixed|null
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param mixed|null $data
     */
    public function setData($data)
    {
        $this->data = $data;
    }

}
