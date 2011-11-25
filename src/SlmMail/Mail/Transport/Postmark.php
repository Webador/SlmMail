<?php

namespace SlmMail\Mail\Transport;

use Zend\Mail\Transport,
    Zend\Mail\Message,
    SlmMail\Service\Postmark as Service;

class Postmark implements Transport
{
    /**
     * Postmark service for api calls
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