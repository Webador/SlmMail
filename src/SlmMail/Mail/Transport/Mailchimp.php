<?php

namespace SlmMail\Mail\Transport;

use Zend\Mail\Transport,
    Zend\Mail\Message,
    SlmMail\Service\Mailchimp\Sts as Service;

class Mailchimp implements Transport
{
    /**
     * Mailchimp service for api calls
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