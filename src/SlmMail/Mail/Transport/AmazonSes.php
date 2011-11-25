<?php

namespace SlmMail\Mail\Transport;

use Zend\Mail\Transport,
    Zend\Mail\Message,
    SlmMail\Service\AmazonSes as Service;

class AmazonSes implements Transport
{
    /**
     * AmazonSes service for api calls
     * 
     * @var Service
     */
    protected $service;
    
    public function setService (Service $service)
    {
        $this->service = $service;
    }
    
    public function send (Message $message)
    {
        return $this->service->sendEmail($message);
    }
}