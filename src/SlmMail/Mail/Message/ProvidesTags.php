<?php

namespace SlmMail\Mail\Message;

/**
 * Trait for messages that allow to have tags (for statistics purposes)
 */
trait ProvidesTags
{
    /**
     * @var string[]
     */
    protected $tags = array();

    /**
     * Add tags to the message
     *
     * @param  array $tags
     * @return self
     */
    public function setTags(array $tags)
    {
        $this->tags = $tags;
        return $this;
    }

    /**
     * Add a tag to the message
     *
     * @param  string $tag
     * @return self
     */
    public function addTag($tag)
    {
        $this->tags[] = (string) $tag;
        return $this;
    }

    /**
     * Get all the tags of the message
     *
     * @return string[]
     */
    public function getTags()
    {
        return $this->tags;
    }
}
