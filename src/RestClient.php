<?php

namespace Iffutsius\LaravelRpc;

use Iffutsius\LaravelRpc\Auth\AuthInterface;
use Iffutsius\LaravelRpc\Exceptions\ClientException;
use Iffutsius\LaravelRpc\Exceptions\NotFoundException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\BadResponseException;
use Illuminate\Support\Str;
use Psr\Http\Message\ResponseInterface;

abstract class RestClient extends BaseClient
{
    /** @var string */
    protected $baseUrl;

    /** @var AuthInterface */
    protected $auth;

    /** @var array */
    protected $headers = ['Content-Type' => 'application/json'];

    /** @var mixed */
    protected $connectionAuth;

    /**
     * @var true|false|string
     *
     * true - enables SSL certificate verification and use the default CA bundle provided by operating system
     * false - disable certificate verification (this is insecure!)
     * string - provide path to a CA bundle to enable verification using a custom certificate
     */
    protected $sslCertVerification = true;

    /**
     * defines the value for curl's CURLOPT_INTERFACE
     *
     * @var false|string
     *
     * false - option is disabled
     * string - ip address, interface name or host name for outgoing network interface
     */
    protected $curlOutputInterface = false;

    /**
     * @return Client
     */
    protected function createConnection()
    {
        $configs = [
            'verify' => $this->sslCertVerification,
        ];

        return new Client($configs);
    }

    /**
     * @return Client
     */
    public function getConnection()
    {
        return parent::getConnection();
    }

    /**
     * @return mixed
     */
    public function getBaseUrl()
    {
        return $this->baseUrl;
    }

    /**
     * @param mixed $value
     */
    public function setConnectionAuth($value)
    {
        $this->connectionAuth = $value;
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
     * @param RestMethod $method
     * @return string
     * @throws ClientException
     */
    protected function getRequestUrlForMethod($method)
    {
        if (!$url = $this->getBaseUrl()) {
            throw new ClientException('Base URL not defined for client [' . $this->getName() . ']', ClientException::BASE_URL_MISSING);
        }

        return rtrim($url, '/') . Str::start($method->getUrlPath(), '/');
    }

    /**
     * @param string $httpMethod
     * @param string $url
     * @param array $content
     * @param string $logName
     * @return mixed|ResponseInterface
     * @throws NotFoundException
     */
    public function send($httpMethod, $url, $content, $logName = 'manual')
    {
        if ($this->curlOutputInterface) {
            $content['curl'][CURLOPT_INTERFACE] = $this->curlOutputInterface;
        }

        try {
            if (LogRpc::enabled()) {
                LogRpc::info('REST >>>>');
                LogRpc::info('  REST [' . $logName . '] called (' . $httpMethod . ') url: ' . $url);
                LogRpc::info('  REST PARAMS: ');
                LogRpc::info(json_encode($content));
            }

            /** @var ResponseInterface $res */
            $res = $this->connection->request($httpMethod, $url, $content);

            if (LogRpc::enabled()) {
                LogRpc::info('  REST [success] RESULT HEADERS [' . $res->getStatusCode() . ']:');
                LogRpc::info(json_encode($res->getHeaders()));
                LogRpc::info('  REST [success] RESULT [' . $res->getStatusCode() . ']:');
                LogRpc::info($res->getBody());
            }

            return $res;
        } catch (BadResponseException $e) {

            if (LogRpc::enabled()) {
                $res = $e->getResponse();
                LogRpc::info('.. REST [error] RESULT HEADERS [' . $res->getStatusCode() . ']:');
                LogRpc::info(json_encode($res->getHeaders()));
                LogRpc::info('.. REST [error] RESULT ERROR [' . $res->getStatusCode() . ']: ' . $res->getReasonPhrase());
                LogRpc::info($res->getBody());
                LogRpc::info('<<<< REST');
            }

            throw $e;
        }
    }

    /**
     * @param RestMethod $method
     * @return mixed|ResponseInterface
     *
     * @throws ClientException
     * @throws NotFoundException
     * @throws BadResponseException
     */
    public function sendMethod($method)
    {
        try {
            $this->prepareAuth($method);

            $url = $this->getRequestUrlForMethod($method);
            $content = $this->prepareContent($method);

            return $this->send($method->getHttpMethod(), $url, $content, get_class($method));

        } catch (BadResponseException $e) {

            if ($e->getCode() == 404) {
                throw new NotFoundException($e->getMessage(), $e->getCode(), $method);
            }
            throw $e; // don't miss the exception
        }
    }

    /**
     * @param RestMethod $method
     */
    protected function prepareAuth($method)
    {
        if (!$method->authEnabled()) {
            return;
        }

        if ($this->auth) {
            $this->auth->authorize($this);
        }
    }

    /**
     * @param RestMethod $method
     * @return array
     */
    protected function prepareHeaders($method)
    {
        return array_merge($this->getHeaders(), $method->getHeaders());
    }

    /**
     * @param RestMethod $method
     * @return array
     */
    protected function prepareContent(RestMethod $method)
    {
        $content['headers'] = $this->prepareHeaders($method);

        $postParams = $this->preparePostParams($method->getPostParams(), $method);
        if (!empty($postParams)) {
            switch ($method->getPostParamMethod()) {
                case RestMethod::POST_PARAMS_METHOD_FORM:
                    $content['form_params'] = $postParams;
                    break;

                case RestMethod::POST_PARAMS_METHOD_JSON:
                    $content['json'] = $postParams;
                    break;

                case RestMethod::POST_PARAMS_METHOD_JSON_UNESCAPED_UNICODE:
                    $content['body'] = json_encode($postParams, JSON_UNESCAPED_UNICODE);
                    $content['headers']['Content-Type'] = 'application/json';
            }
        }

        $queryParams = $this->prepareQueryParams($method->getQueryParams(), $method);
        if (!empty($queryParams)) {
            $content['query'] = $queryParams;
        }

        if ($this->connectionAuth) {
            $content['auth'] = $this->connectionAuth;
        }

        return $content;
    }

    /**
     * @param array $params
     * @param RestMethod $method
     * @return array
     */
    protected function preparePostParams($params, RestMethod $method = null)
    {
        return $this->prepareParams($params, RestMethod::HTTP_POST, $method);
    }

    /**
     * @param array $params
     * @param RestMethod $method
     * @return array
     */
    protected function prepareQueryParams($params, RestMethod $method = null)
    {
        return $this->prepareParams($params, RestMethod::HTTP_GET, $method);
    }

    /**
     * @param array $params
     * @param string $httpMethod
     * @param RestMethod $method
     * @return array
     */
    protected function prepareParams($params, $httpMethod = null, RestMethod $method = null)
    {
        return $params;
    }
}
