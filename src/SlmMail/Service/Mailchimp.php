<?php

namespace SlmMail\Service;

use Zend\Mail\Message,
    Zend\Http\Client,
    Zend\Http\Request,
    Zend\Http\Response,
    Zend\Json\Json,
    Zend\Mail\Exception\RuntimeException;

abstract class Mailchimp
{
    const API_URI = '';
    
    protected $apiKey;
    protected $client;
    
    public function setApiKey ($api_key)
    {
        $this->apiKey = $api_key;
    }    
    
    protected function getHttpClient ()
    {
        if (null === $this->client) {
            $code = substr($this->apiKey, strpos('-')+1);
            $uri  = sprintf(static::API_URI, $code);
            
            $this->client = new Client();
            $this->client->setUri($uri)
                         ->setMethod(Request::METHOD_GET);
        }
        
        return $this->client;
    }
    
    protected function parseResponse (Response $response)
    {
        // @todo look for errors
        
        return Json::decode($response->getBody());
    }
}