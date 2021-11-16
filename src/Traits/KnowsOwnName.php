<?php

namespace Iffutsius\LaravelRpc\Traits;

trait KnowsOwnName
{
    /**
     * @return string
     */
    public function getName()
    {
        try {
            return (new \ReflectionClass($this))->getShortName();
        } catch (\ReflectionException $e) {
            return basename(get_class($this));
        }
    }

}