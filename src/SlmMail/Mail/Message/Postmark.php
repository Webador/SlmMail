<?php

namespace SlmMail\Mail\Message;

use Zend\Mail\Message;

class Postmark extends Message
{
    /**
     * @var string
     */
    protected $tag;

    /**
     * Get tag for the message
     *
     * @return string
     */
    public function getTag()
    {
        return $this->tag;
    }

    /**
     * Set tag for the message
     *
     * @param  string $tag Value to set
     * @return self
     */
    public function setTag($tag)
    {
        $this->tag = (string) $tag;
        return $this;
    }
}
