<?php

namespace SlmMail\Mail\Message;

/**
 * Trait for messages that allow to have specific API options
 */
trait ProvidesOptions
{
    /**
     * @var array
     */
    protected $options = array();

    /**
     * Add options to the message
     *
     * @param  array $options
     * @return self
     */
    public function setOptions(array $options)
    {
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
}
