<?php

namespace SlmMail\Mail\Message\Provider;

use Zend\Mail\Message;

class Mailgun extends Message
{
    const TAG_LIMIT = 3;

    /**
     * @var array
     */
    protected $tags = array();

    /**
     * @var array
     */
    protected $options = array();

    /**
     * @var array
     */
    protected $validOptions = array(
        'dkim'            => 'o:dkim',
        'delivery_time'   => 'o:deliverytime',
        'test_mode'       => 'o:testmode',
        'tracking'        => 'o:tracking',
        'tracking_clicks' => 'o:tracking-clicks',
        'tracking_opens'  => 'o:tracking-opens'
    );

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

    /**
     * Add options to the message
     *
     * @param  array $options
     * @return self
     */
    public function setOptions(array $options)
    {
        foreach ($options as $key => $value) {
            if (!array_key_exists($key, $this->getValidOptions())) {
                throw new Exception\InvalidArgumentException(sprintf(
                    'Invalid option %s given', $key
                ));
            }
        }

        $this->options = $options;
        return $this;
    }

    /**
     * Set an option to the message
     *
     * @param  string $key
     * @param  mixed   $value
     * @return self
     */
    public function setOption($key, $value)
    {
        if (!array_key_exists($key, $this->getValidOptions())) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Invalid option %s given', $key
            ));
        }

        $this->options[$key] = $value;
        return $this;
    }

    /**
     * Get all the options of the message
     *
     * @return string[]
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Get list of supported options
     *
     * @return array
     */
    public function getValidOptions()
    {
        return $this->validOptions;
    }
}
