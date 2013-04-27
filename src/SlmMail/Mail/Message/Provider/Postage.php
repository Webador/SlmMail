<?php

namespace SlmMail\Mail\Message\Provider;

use SlmMail\Mail\Message\ProvidesAttachments;
use Zend\Mail\Message;

class Postage extends Message
{
    use ProvidesAttachments;

    /**
     * @var string
     */
    protected $template;

    /**
     * @var array
     */
    protected $variables = array();

    /**
     * Set Postage template name to use
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
     * Get Postage template name to use
     *
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * Set the template global variables
     *
     * @param  array $variables
     * @return Mandrill
     */
    public function setVariables(array $variables)
    {
        $this->variables = $variables;
        return $this;
    }

    /**
     * Get the template global variables
     *
     * @return array
     */
    public function getVariables()
    {
        return $this->variables;
    }
}
