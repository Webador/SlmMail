<?php

namespace SlmMail\Service;

use Zend\Mail\Message,
    Zend\Http\Client,
    Zend\Http\Request,
    Zend\Http\Response;

class ElasticEmail
{
    const API_URI = 'https://api.elasticemail.com/';

    protected $apiKey;
    protected $username;
    protected $client;
    
    public function setApiKey ($api_key)
    {
        $this->apiKey = $api_key;
    }
    
    public function setUsername ($username)
    {
        $this->username = $username;
    }
    
    public function send (Message $message) {}
    public function uploadAttachment () {}
    public function getStatus () {}
    public function getLog () {}
    public function getAccountDetails () {}
    public function getBounced () {}
    public function getUnsubscribed () {}
    
    protected function getHttpClient ($path)
    {
        if (null === $this->client) {
            $this->client = new Client();
            $this->client->setUri(self::API_URI)
                         ->setMethod(Request::METHOD_GET);
        }
        
        $this->client->getUri()->setPath($path);
        return $this->client;
    }
}