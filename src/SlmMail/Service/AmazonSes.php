<?php

namespace SlmMail\Service;

use Zend\Mail\Message,
    Zend\Http\Client,
    Zend\Http\Request,
    Zend\Http\Response,
    Zend\Mail\Exception\RuntimeException;

class AmazonSes extends Amazon
{
    protected $host;
    protected $accessKey;
    protected $client;
    
    public function __construct ($host, $access_key)
    {
        throw new \RuntimeException('This implementation is not finished, DO NOT USE IT!');
        $this->host = $host;
        $this->accessKey = $access_key;
    }
    
    public function sendEmail (Message $message)
    {
        
    }

    protected function prepareHttpClient ($path, array $data = array())
    {
        
    }
    
    protected function parseResponse (Response $response)
    {
        
    }
}