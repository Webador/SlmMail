<?php

namespace SlmMail\Mail\Transport;

use Zend\Mail\Transport,
    Zend\Mail\Message,
    SlmMail\Service\ElasticEmail as Service;

class ElasticEmail implements Transport
{
    /**
     * ElasticEmail service for api calls
     * 
     * @var Service
     */
    protected $service;
    
    public function __construct (Service $service)
    {
        $this->service = $service;
    }
    
    public function send (Message $message)
    {
        return $this->service->send($message);
    }
}