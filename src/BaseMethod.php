<?php

namespace Iffutsius\LaravelRpc;

use Iffutsius\LaravelRpc\Exceptions\MethodException;
use Iffutsius\LaravelRpc\Traits\KnowsOwnName;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

abstract class BaseMethod
{
    use KnowsOwnName;

    /**
     * @var BaseClient
     */
    protected $client;

    /** @var integer */
    protected $cacheMinutes = null;

    /**
     * @var boolean
     */
    protected $isSent = false;

    /**
     * @var array
     */
    protected $response;

    /**
     * @var integer
     */
    protected $responseStatus;

    /**
     * @return static
     */
    public static function create()
    {
        return new static(...\func_get_args());
    }

    /**
     * AbstractMethod constructor.
     */
    public function __construct()
    {
        $this->init();
    }

    /**
     * Initialize method information
     */
    protected function init()
    {
        $this->client = $this->createClient();
    }

    /**
     * Initialize client connection for method
     * @return BaseClient
     */
    abstract protected function createClient();

    /**
     * @return $this
     */
    abstract public function send();

    /**
     * @return string
     */
    protected function getCacheKey()
    {
        return $this->composeCacheKey();
    }

    /**
     * @param string|array $keys
     * @param string $glue
     * @return string
     */
    protected function composeCacheKey($keys = null, $glue = '-')
    {
        $key = Str::after(static::class, 'App\\Http\\Rpc\\');
        if (!empty($keys)) {
            $key.= $glue . (is_array($keys) ? implode($glue, $keys) : $keys);
        }
        return str_replace(['\\', ' '], $glue, $key);
    }

    /**
     * @return boolean
     */
    public function clearCache()
    {
        return Cache::forget($this->getCacheKey());
    }

    /**
     * Formats response so that it will always be as array.
     *
     * @param mixed $response
     * @return array
     */
    protected function formatResponse($response)
    {
        return $response;
    }

    /**
     * Handles response, adds extra information, converts it for specific method needs.
     *
     * @param array $response
     * @return array
     */
    protected function handleResponse($response)
    {
        return $response;
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     * @throws MethodException
     */
    public function response($key = null, $default = null)
    {
        $this->checkResponseCalled();

        if (is_null($key)) {
            return $this->response ?? value($default);
        }

        return Arr::get($this->response, $key, $default);
    }

    /**
     * Initiates method sending, when it hasn't been sent already
     */
    protected function checkResponseCalled()
    {
        if (!$this->hasBeenCalled()) {
            $this->send();
        }
    }

    /**
     * @return integer
     */
    public function responseStatus()
    {
        $this->checkResponseCalled();
        return $this->responseStatus;
    }

    /**
     * @return string
     */
    public function getError()
    {
        return '';
    }

    /**
     * @return boolean
     */
    protected function hasBeenCalled()
    {
        return $this->isSent;
    }

    /**
     * Fixes string booleans from response to be proper booleans.
     *
     * @param array $arr
     * @param array $fields list of boolean fields
     */
    protected function fixBooleans(&$arr, $fields = [])
    {
        $fields = array_flip($fields);
        foreach ($arr as $key => $val) {
            if (empty($fields) || isset($fields[$key])) {
                $arr[$key] = ($val === 'true');
                unset($fields[$key]);
            }
        }

        // add non-found fields as 'false'
        foreach (array_keys($fields) as $field) {
            $arr[$field] = false;
        }
    }

    /**
     * Makes sure all keys are in camelCase format.
     *
     * @param array $arr
     * @return array
     */
    protected function fixKeys($arr)
    {
        $new = [];
        foreach ($arr as $key => $val) {
            $newKey = Str::camel($key);
            $new[$newKey] = is_array($val) ? $this->fixKeys($val) : $val;
        }
        return $new;
    }
}
