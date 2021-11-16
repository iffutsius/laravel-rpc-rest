<?php

namespace Iffutsius\LaravelRpc\Auth;

use Iffutsius\LaravelRpc\RestClient;
use Illuminate\Support\Arr;

class BasicAuth implements AuthInterface
{
    /** @var string */
    private $user;

    /** @var string */
    private $secret;

    /**
     * @param array|string $settings
     * @return mixed|void
     */
    public static function create($settings)
    {
        $user = Arr::get($settings, 'user');
        $secret = Arr::get($settings, 'secret');

        return new static($user, $secret);
    }

    /**
     * BasicAuth constructor.
     * @param string $user
     * @param string $secret
     */
    public function __construct($user, $secret)
    {
        $this->user = $user;
        $this->secret = $secret;
    }

    /**
     * @param RestClient $client
     * @return mixed|void
     */
    public function authorize(RestClient $client)
    {
        $client->setConnectionAuth([$this->user, $this->secret]);
    }
}