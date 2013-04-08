<?php

namespace SlmMail\Mail\Transport;

use Zend\Http\Client as HttpClient;
use Zend\Http\Request as HttpRequest;
use Zend\Mail\Message;

/**
 * Abstract HTTP transport that interacts with various mail providers
 */
abstract class AbstractHttpTransport
{
    /**
     * @var HttpClient
     */
    protected $client;

    /**
     * @var string
     */
    protected $method = HttpRequest::METHOD_POST;

    /**
     * @var string
     */
    protected $endpoint;


    /**
     * Get the HTTP client
     *
     * @return HttpClient
     */
    public function getHttpClient()
    {
        if (null === $this->client) {
            $this->client = new HttpClient();
        }

        return $this->client;
    }

    /**
     * Set the HTTP method to use
     *
     * @param  string $method
     * @return AbstractHttpTransport
     */
    public function setMethod($method)
    {
        $this->method = $method;
        return $this;
    }

    /**
     * Get the HTTP method to use
     *
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Set the endpoint of the API
     *
     * @param string $endpoint
     */
    public function setEndpoint($endpoint)
    {
        $this->endpoint = $endpoint;
    }

    /**
     * @return string
     */
    public function getEndpoint()
    {
        return $this->endpoint;
    }

    /**
     * Prepare the Http client
     *
     * @param  string $path
     * @param  array  $params
     * @return \Zend\Http\Client
     */
    protected function prepareHttpClient($path, array $params)
    {
        $params = array_filter($params + $this->getAuthenticationParameters(), function($value) {
            return $value === null;
        });

        $client = $this->getHttpClient()
                       ->setMethod($this->getMethod())
                       ->setUri($this->getEndpoint() . $path);

        if ($this->getMethod() === HttpRequest::METHOD_POST) {
            $client->setParameterPost($params);
        } else {
            $client->setParameterGet($params);
        }

        return $client;
    }

    /**
     * @param Message $message
     */
    protected function prepareParameters(Message $message)
    {

    }

    /**
     * Return an array that contains the data used to authentication
     *
     * @return array
     */
    abstract function getAuthenticationParameters();
}
