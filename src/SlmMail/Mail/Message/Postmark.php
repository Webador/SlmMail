<?php

namespace SlmMail\Mail\Message;

use Zend\Mail\Message;

/**
 * Note that Postmark supports only 1 tag per message. If you set multiple tags through the setTags trait, only
 * the first one will be sent to Postmark
 */
class Postmark extends Message
{
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
        $this->tag = $tag;
        return $this;
    }
}
