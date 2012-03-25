<?php

namespace SlmMail\Service;

use DateTime,
    Zend\Mail\Message,
    Zend\Http\Client,
    Zend\Http\Request,
    Zend\Http\Response,
    Zend\Mail\Exception\RuntimeException;

class AmazonSes extends Amazon
{
    protected $host;
    protected $accessKey;
    protected $secretKey;
    protected $client;
    
    public function __construct ($host, $access_key, $secret_key)
    {
        $this->host      = $host;
        $this->accessKey = $access_key;
        $this->secretKey = $secret_key;
    }
    
    public function sendEmail (Message $message)
    {
        
    }

    protected function prepareHttpClient ($action, array $data = array())
    {
        $data   = $data + array('Action' => $action);
        
        $client = $this->getHttpClient()
                       ->setMethod(Request::METHOD_POST)
                       ->setParameterPost($data)
                       ->setUri($this->host);
        
        $date = new DateTime;
        $date = $date->format('r');
        
        $auth = 'AWS3-HTTPS AWSAccessKeyId=' . $this->accessKey 
              . ',Algorithm=HmacSHA256,Signature=' . $this->sign($date)
              . ',SignedHeaders=Date';
        
        $client->getRequest()->headers()
               ->addHeaderLine('Content-Type', 'application/x-www-form-urlencoded')
               ->addHeaderLine('Date', $date)
               ->addHeaderLine('X-Amzn-Authorization', $auth);
        
        return $client;
    }
    
    protected function sign ($content)
    {
        return base64_encode(hash_hmac('sha256', $content, $this->secretKey, true));
    }
    
    protected function parseResponse (Response $response)
    {
        
    }
}