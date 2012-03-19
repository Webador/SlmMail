<?php

namespace SlmMail\Mail\Message;

use Zend\Mail\Message;

class Postage extends Message
{
    protected $template;
    protected $variables;

    public function getTemplate ()
    {
        return $this->template;
    }
    
    public function setTemplate ($template)
    {
        $this->template = $template;
    }
    
    public function getVariables ()
    {
        return $this->variables;
    }
    
    public function setVariables (array $variables)
    {
        $this->variables = $variables;
    }
    
    public function addVariable ($name, $value)
    {
        if (null === $this->variables) {
            $this->variables = array();
        }
        
        $this->variables[$name] = $value;
    }
    
    public function clearVariables ()
    {
        unset($this->variables);
    }
}