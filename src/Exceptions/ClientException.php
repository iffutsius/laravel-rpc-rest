<?php

namespace Iffutsius\LaravelRpc\Exceptions;

use Iffutsius\LaravelRpc\BaseClient;

class ClientException extends \Exception
{
    const BASE_URL_MISSING = 100;

    /** @var BaseClient */
    public $client;

    public function __construct($message, $code, $client = null)
    {
        $this->client = $client;

        parent::__construct($message, $code);
    }
}
