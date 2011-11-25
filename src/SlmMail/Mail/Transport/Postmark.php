<?php

namespace SlmMail\Mail\Transport;

use Zend\Mail\Transport,
    Zend\Mail\Message,
    SlmMail\Mail\Service\Postmark as Service;

class Postmark implements Transport
{
    protected $service;
    
    public function setService (Service $service)
    {
        $this->service = $service;
        return $this;
    }
    
    public function send (Message $message)
    {
        return $this->service->sendEmail($message);
    }
}