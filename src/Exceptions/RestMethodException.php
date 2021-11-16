<?php

namespace Iffutsius\LaravelRpc\Exceptions;

class RestMethodException extends MethodException
{
    const ATTRIBUTES_VALIDATION_FAILED = 1;
    const PARAMS_VALIDATION_FAILED = 2;
    const QUERY_PARAMS_VALIDATION_FAILED = 3;
    const METHOD_PAUSED = 10;
    const EXTERNAL_API_ERROR = 400;

    /**
     * @return boolean
     */
    public function isValidationError()
    {
        return in_array($this->getCode(), [
            static::ATTRIBUTES_VALIDATION_FAILED,
            static::PARAMS_VALIDATION_FAILED,
            static::QUERY_PARAMS_VALIDATION_FAILED,
        ]);
    }
}
