<?php

namespace SlmMail\Service;

use Zend\Mail\Message,
    Zend\Http\Client,
    Zend\Http\Request,
    Zend\Http\Response,
    Zend\Mail\Exception\RuntimeException;

class AmazonSes
{
    protected $host;
    protected $accessKey;
    protected $client;
    
    public function setHost ($host)
    {
        $this->host = $host;
    }
    
    public function setAccessKey ($access_key)
    {
        $this->accessKey = $access_key;
    }
    
    public function sendEmail (Message $message) {}
    public function verifyEmailAddress () {}
    public function listVerifiedEmailAddresses () {}
    public function deleteVerifiedEmailAddresses () {}
    public function getSendQuota () {}
    public function getSendStatistics () {}
    
    protected function getHttpClient ()
    {
        if (null === $this->client) {
            $this->client = new Client();
            $this->client->setUri($this->host)
                         ->setMethod(Request::METHOD_GET);
        }
        
        return $this->client;
    }
}