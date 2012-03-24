<?php

namespace SlmMail\Service;

use Zend\Mail\Message,
    Zend\Http\Client,
    Zend\Http\Request,
    Zend\Http\Response,
    Zend\Mail\Exception\RuntimeException;

abstract class Amazon
{
    protected $client;
    
    abstract public function sendEmail (Message $message);
    
    public function verifyEmailAddress ($email)
    {
        $params   = array('email' => $email);
        $response = $this->prepareHttpClient('VerifyEmailAddress', $params)
                         ->send();
        
        return $this->parseReponse($response);
    }
    
    public function listVerifiedEmailAddresses ()
    {
        $response = $this->prepareHttpClient('ListVerifiedEmailAddresses')
                         ->send();
        
        return $this->parseReponse($response);
    }
    
    public function deleteVerifiedEmailAddresses ($email)
    {
        $params   = array('email' => $email);
        $response = $this->prepareHttpClient('DeleteVerifiedEmailAddress', $params)
                         ->send();
        
        return $this->parseReponse($response);
    }
    
    public function getSendQuota ()
    {
        $response = $this->prepareHttpClient('GetSendQuota')
                         ->send();
        
        return $this->parseReponse($response);
    }
    
    public function getSendStatistics ()
    {
        $response = $this->prepareHttpClient('GetSendStatistics')
                         ->send();
        
        return $this->parseReponse($response);
    }
    
    public function getHttpClient ()
    {
        if (null === $this->client) {
            $this->client = new Client;
        }
        
        return $this->client;
    }
    
    public function setHttpClient (Client $client)
    {
        $this->client = $client;
    }
    
    abstract protected function prepareHttpClient ($path, array $data = array());
    abstract protected function parseResponse (Response $response);
    
    /**
     * Filter null values from the array
     * 
     * Because parameters get interpreted when they are send, remove them 
     * from the list before the request is sent.
     * 
     * @param array $params
     * @param array $exceptions
     * @return array
     */
    protected function filterNullParams (array $params, array $exceptions = array())
    {
        $return = array();
        foreach ($params as $key => $value) {
            if (null !== $value || in_array($key, $exceptions)) {
                $return[$key] = $value;
            }
        }
        
        return $return;
    }
}