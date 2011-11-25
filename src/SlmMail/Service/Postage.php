<?php

namespace SlmMail\Service;

use Zend\Mail\Message,
    Zend\Http\Client,
    Zend\Http\Request,
    Zend\Http\Response,
    Zend\Json\Json,
    Zend\Mail\Exception\RuntimeException;

class Postage
{
    const API_URI     = 'https://api.postageapp.com/';
    const API_VERSION = 'v.1.0';
    
    protected $apiKey;
    protected $client;
    
    public function setApiKey ($api_key)
    {
        $this->apiKey = $api_key;
    }
    
    public function sendMessage (Message $message) {}
    public function getMessageReceipt () {}
    public function getMethodList () {}
    public function getAccountInfo () {}
    public function getProjectInfo () {}
    
    protected function getHttpClient ($path)
    {
        if (null === $this->client) {
            $this->client = new Client();
            $this->client->setUri(self::API_URI)
                         ->setMethod(Request::METHOD_GET);
        }
        
        $this->client->getUri()->setPath(self::API_VERSION . '/' . $path);
        return $this->client;
    }
    
    protected function parseResponse (Response $response)
    {
        // @todo look for errors
        
        return Json::decode($response->getBody());
    }
}