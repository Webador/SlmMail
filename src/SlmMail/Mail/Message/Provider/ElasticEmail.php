<?php

namespace SlmMail\Mail\Message\Provider;

use Zend\Mail\Message;

class ElasticEmail extends Message
{
    /**
     * Channel id for the message
     *
     * @var string
     */
    protected $channel;

    /**
     * Name of Elastic Email template to use
     *
     * @var string
     */
    protected $template;

    /**
     * Set the channel id to use when the mail is sent
     *
     * @param  string $channel
     * @return self
     */
    public function setChannel($channel)
    {
        $this->channel = (string) $channel;
        return $this;
    }

    /**
     * Get channel id to use when the mail is sent
     *
     * @return string
     */
    public function getChannel()
    {
        return $this->channel;
    }

    /**
     * Set the template name to use
     *
     * @param  string $template
     * @return self
     */
    public function setTemplate($template)
    {
        $this->template = (string) $template;
        return $this;
    }

    /**
     * Get the template name to use
     *
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }
}
