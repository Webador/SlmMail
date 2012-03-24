<?php

namespace SlmMail\Mail\Message;

use Zend\Mail\Message;

class Mailchimp extends Message
{
    protected $trackClicks;
    protected $trackOpens;
    protected $tags;
    
    public function getTrackClicks ()
    {
        return $this->trackClicks;
    }

    public function setTrackClicks ($trackClicks = true)
    {
        $this->trackClicks = (bool) $trackClicks;
    }
    
    public function getTrackOpens ()
    {
        return $this->trackOpens;
    }

    public function setTrackOpens ($trackOpens = true)
    {
        $this->trackOpens = (bool) $trackOpens;
    }

    public function getTags ()
    {
        return $this->tags;
    }

    public function setTags (array $tags)
    {
        $this->tags = $tags;
    }

    public function addTag ($tag)
    {
        if (null === $this->tags) {
            $this->tags = array();
        }
        
        $this->tags[] = $tag;
    }
}