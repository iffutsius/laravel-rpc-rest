<?php

namespace Iffutsius\LaravelRpc\Exceptions;

use Iffutsius\LaravelRpc\BaseMethod;

class MethodException extends \Exception
{
    const RESPONSE_CALLED_BEFORE_METHOD_EXECUTION = 1;

    /** @var BaseMethod */
    public $method;

    public function __construct($message, $code, $method = null)
    {
        $this->method = $method;

        parent::__construct($message, $code);
    }
}
