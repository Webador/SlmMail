<?php

namespace SlmMail\Mail\Message\Provider;

use SlmMail\Mail\Message\ProvidesOptions;
use Zend\Mail\Message;

class Mailgun extends Message
{
    use ProvidesOptions;

    const TAG_LIMIT = 3;

    /**
     * @var array
     */
    protected $tags = array();

    /**
     * Get all tags for this message
     *
     * @return array
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * Set all tags for this message
     *
     * @param  array $tags
     * @return self
     */
    public function setTags(array $tags)
    {
        if (count($tags) > self::TAG_LIMIT) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Mailgun only allows up to %s tags', self::TAG_LIMIT
            ));
        }
        $this->tags = $tags;
        return $this;
    }

    /**
     * Add a tag to this message
     *
     * @param string $tag
     * @return self
     */
    public function addTag($tag)
    {
        if (count($this->tags)+1 > self::TAG_LIMIT) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Mailgun only allows up to %s tags', self::TAG_LIMIT
            ));
        }

        $this->tags[] = (string) $tag;
        return $this;
    }
}
