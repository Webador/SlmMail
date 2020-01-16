<?php

namespace SlmMail\Mail\Message;

use Laminas\Mail\Message;


class SparkPost extends Message
{

    /**
     * SMTP array config
     *
     * @var array
     */
    protected $options = [];

    /**
     * Constructor.
     *
     * @param array $options Optional
     */
    public function __construct($options = null)
    {
        $this->setOptions($options);
    }

    /**
     * Set options
     *
     * @param array $options
     *
     * @return self
     */
    public function setOptions($options)
    {
        $this->options = $options;

        return $this;
    }

    /**
     * Get options
     *
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }
}