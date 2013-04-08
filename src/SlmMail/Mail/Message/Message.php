<?php

namespace SlmMail\Mail\Message;

use Zend\Mail\Message as BaseMessage;

/**
 * Extend the base message class so that the user can set options specific to mail providers
 */
class Message extends BaseMessage
{
    /**
     * @var array
     */
    protected $options;


    /**
     * Set options
     *
     * @param  array $options
     * @return Message
     */
    public function setOptions($options)
    {
        foreach ($options as $key => $value) {
            $this->setOption($key, $value);
        }

        return $this;
    }

    /**
     * Set a single option
     *
     * @param string $key
     * @param string $value
     */
    public function setOption($key, $value)
    {
        $this->options[$key] = $value;
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
