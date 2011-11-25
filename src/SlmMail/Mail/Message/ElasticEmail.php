<?php

namespace SlmMail\Mail\Message;

use Zend\Mail\Message;

class ElasticEmail extends Message
{
    protected $channel;

    public function getChannel ()
    {
        return $this->channel;
    }
    
    public function setChannel ($channel)
    {
        $this->channel = $channel;
    }
}