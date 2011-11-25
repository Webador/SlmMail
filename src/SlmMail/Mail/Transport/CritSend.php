<?php

namespace SlmMail\Mail\Transport;

use Zend\Mail\Transport\Smtp,
    Zend\Mail\Message;

class CritSend extends Smtp
{
    const HOST = 'smtp.critsend.com';
    
    public function __construct ($options)
    {
        parent::__construct(self::HOST, $options);
    }
    
    public function send (Message $message)
    {
        return parent::send($message);
    }
}