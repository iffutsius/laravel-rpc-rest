<?php

namespace Iffutsius\LaravelRpc;

use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

abstract class RestMethod extends BaseMethod
{
    const HTTP_GET = 'GET';
    const HTTP_POST = 'POST';
    const HTTP_PUT = 'PUT';
    const HTTP_DELETE = 'DELETE';
    const HTTP_OPTIONS = 'OPTIONS';

    const POST_PARAMS_METHOD_FORM = 'form';
    const POST_PARAMS_METHOD_JSON = 'json';
    const POST_PARAMS_METHOD_JSON_UNESCAPED_UNICODE = 'json-unescaped-unicode';

    /**
     * @var string
     */
    protected $urlPath;

    /**
     * @var string
     */
    protected $httpMethod;

    /**
     * @var RestClient
     */
    protected $client;

    /**
     * Extra headers that might need to be set for the method
     * @var array
     */
    protected $headers = [];

    /**
     * Query params to send with the url (/?...)
     * @var array
     */
    protected $queryParams = [];

    /**
     * Post params sent with POST request
     * @var array
     */
    protected $postParams = [];

    /**
     * Sets how post params must be set, as form data or as raw json.
     * @var string
     */
    protected $postParamsMethod = self::POST_PARAMS_METHOD_JSON;

    /**
     * @var Response
     */
    protected $rawResponse;

    /**
     * @return string
     */
    public function getUrlPath()
    {
        return $this->replaceUrlTokens($this->urlPath);
    }

    /**
     * @param $url
     * @return string
     */
    public function replaceUrlTokens($url)
    {
        // check if we have parameters defined
        if (preg_match_all('/{([^}]+)}/', $this->urlPath, $m)) {
            foreach ($m[1] as $param) {
                if (property_exists($this, $param)) {
                    $url = str_replace('{' . $param . '}', $this->$param, $url);
                }
            }
        }

        return $url;
    }

    /**
     * @return string
     */
    public function getHttpMethod()
    {
        return $this->httpMethod;
    }

    /**
     * @param string $key
     * @param string $value
     * @return $this
     */
    public function addHeader($key, $value)
    {
        $this->headers[$key] = $value;
        return $this;
    }

    /**
     * @param array $headers
     * @return $this
     */
    public function setHeaders(array $headers)
    {
        $this->headers = $headers;
        return $this;
    }

    /**
     * @return array
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * @return boolean
     */
    public function disableAuthToken()
    {
        return false;
    }

    /**
     * Sets individual query param
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function addQueryParam($key, $value)
    {
        $this->queryParams[$key] = $value;
        return $this;
    }

    /**
     * @param array $queryParams
     * @return $this
     */
    public function setQueryParams(array $queryParams)
    {
        $this->queryParams = $queryParams;
        return $this;
    }

    /**
     * @return array
     */
    public function getQueryParams()
    {
        return $this->queryParams;
    }

    /**
     * @return array
     */
    protected function queryParamRules()
    {
        return [];
    }

    /**
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function getQueryParamsValidator()
    {
        return Validator::make($this->getQueryParams(), $this->queryParamRules());
    }

    /**
     * Sets individual post param
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function addPostParam($key, $value)
    {
        $this->postParams[$key] = $value;
        return $this;
    }

    /**
     * Resets all post params to the value provided
     *
     * @param array $postParams
     * @return $this
     */
    public function setPostParams(array $postParams)
    {
        $this->postParams = $postParams;
        return $this;
    }

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function getPostParams($key = null, $default = null)
    {
        return Arr::get($this->postParams, $key, $default);
    }

    /**
     * @return string
     */
    public function getPostParamMethod()
    {
        return $this->postParamsMethod;
    }

    /**
     * @return array
     */
    protected function postParamRules()
    {
        return [];
    }

    /**
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function getPostParamsValidator()
    {
        return Validator::make($this->getPostParams(), $this->postParamRules());
    }

    /**
     * @return $this
     * @throws Exceptions\NotFoundException
     * @throws ValidationException
     * @throws \Exception
     */
    public function send()
    {
        $this->validate();
        $this->rawResponse = $this->client->sendMethod($this);
        $this->setResponse($this->rawResponse);

        return $this;
    }

    /**
     * @param Response $rawResponse
     * @throws \Exception
     */
    protected function setResponse($rawResponse)
    {
        $this->isSent = true;
        $this->responseStatus = $this->extractResponseStatus($rawResponse);
        $this->response = $this->handleResponse($this->formatResponse($rawResponse));
        $this->validateResponse();
    }

    /**
     * @param Response $response
     * @return integer
     */
    protected function extractResponseStatus($response)
    {
        return $response->getStatusCode();
    }

    /**
     * @return Response
     */
    protected function getRawResponse()
    {
        $this->checkResponseCalled();
        return $this->rawResponse;
    }

    /**
     * @return boolean
     * @throws ValidationException
     */
    public function validate()
    {
        if (!empty($this->queryParamRules())) {
            $validator = $this->getQueryParamsValidator();
            if ($validator->fails()) {
                throw new ValidationException($validator);
            }
        }

        if (!empty($this->postParamRules())) {
            $validator = $this->getPostParamsValidator();
            if ($validator->fails()) {
                throw new ValidationException($validator);
            }
        }
        return true;
    }

    /**
     * @throws \Exception
     */
    protected function validateResponse()
    {
        $statusCode = $this->responseStatus();
        if ($statusCode < 200 and $statusCode >= 300) {
            throw new \Exception('Not a valid Response');
        }
    }

    /**
     * @param $response
     * @return array
     */
    protected function formatResponse($response)
    {
        $body = (string)$response->getBody();
        return json_decode($body, true);
    }
}
