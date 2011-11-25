<?php

namespace SlmMail\Mail\Message;

use Zend\Mail\Message;

class Postmark extends Message
{
    protected $tag;

    public function getTag ()
    {
        return $this->tag;
    }
    
    public function setTag ($tag)
    {
        $this->tag = $tag;
    }
}