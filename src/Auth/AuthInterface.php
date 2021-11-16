<?php

namespace Iffutsius\LaravelRpc\Auth;

use Iffutsius\LaravelRpc\RestClient;

interface AuthInterface
{
    /**
     * @param array $settings
     * @return mixed
     */
    public static function create($settings);

    /**
     * @param RestClient $client
     * @return mixed
     */
    public function authorize(RestClient $client);
}