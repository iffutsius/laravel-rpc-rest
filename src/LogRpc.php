<?php

namespace Iffutsius\LaravelRpc;

class LogRpc
{
    /**
     * @return boolean
     */
    public static function enabled()
    {
        return config('laravel-rpc-rest.log.enabled', false);
    }

    /**
     * @return string
     */
    public function getLogFile()
    {
        return config('laravel-rpc-rest.log.file');
    }

    /**
     * @param string $str
     */
    public static function info($str)
    {
        (new static)->writeToFile($str);
    }

    /**
     * @param string $text
     */
    protected function writeToFile($text)
    {
        file_put_contents($this->getLogFile(), "\n[" . date('Y-m-d H:i:s') . "] $text\n", FILE_APPEND);
    }
}