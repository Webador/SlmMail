<?php

namespace SlmMail\Mail\Transport;

use Zend\Mail\Transport,
    Zend\Mail\Message,
    SlmMail\Service\SendGrid as Service;

class SendGrid implements Transport
{
    /**
     * SendGrid service for api calls
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
        return $this->service->sendMail($message);
    }
}