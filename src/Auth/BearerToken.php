<?php

namespace Iffutsius\LaravelRpc\Auth;

use Iffutsius\LaravelRpc\RestClient;

class BearerToken implements AuthInterface
{
    /** @var string */
    private $token;

    /**
     * @param array|string $settings
     * @return mixed|void
     */
    public static function create($settings)
    {
        $token = is_array($settings) ? ($settings['token'] ?? null) : $settings;
        return new static($token);
    }

    /**
     * BearerToken constructor.
     * @param string $token
     */
    public function __construct($token)
    {
        $this->token = $token;
    }

    /**
     * @param RestClient $client
     * @return mixed|void
     */
    public function authorize(RestClient $client)
    {
        $client->addHeader('Authorization', 'Bearer ' . $this->token);
    }
}