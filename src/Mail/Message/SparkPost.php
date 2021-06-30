<?php

namespace SlmMail\Mail\Message;

use Laminas\Mail\Message;

class SparkPost extends Message
{
    /**
     * @var array
     */
    protected $options = [];

    public function __construct(array $options = [])
    {
        $this->setOptions($options);
    }

    public function setOptions(array $options): SparkPost
    {
        $this->options = $options;

        return $this;
    }

    public function getOptions(): array
    {
        return $this->options;
    }
}
