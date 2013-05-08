<?php

namespace SlmMail\Mail\Message\Provider;

use SlmMail\Mail\Message\ProvidesAttachments;
use Zend\Mail\Message;

class AlphaMail extends Message
{
    /**
     * Identifier of AlphaMail project id to use
     *
     * @var int
     */
    protected $project;

    /**
     * Variables to send to the project (they call it "payload")
     *
     * @var array
     */
    protected $variables;

    /**
     * Set the project id to use
     *
     * @param  int $project
     * @return self
     */
    public function setProject($project)
    {
        $this->project = (int) $project;
        return $this;
    }

    /**
     * Get the porject id to use
     *
     * @return string
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * Set variables
     *
     * @param  array $variables
     * @return self
     */
    public function setVariables(array $variables)
    {
        $this->variables = $variables;
        return $this;
    }

    /**
     * Get variables
     *
     * @return array
     */
    public function getVariables()
    {
        return $this->variables;
    }
}
