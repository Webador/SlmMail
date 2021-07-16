<?php

namespace SlmMail\Mail\Message;

use Laminas\Mail\Address\AddressInterface;
use Laminas\Mail\Message;

class SparkPost extends Message
{
    /**
     * Options that will be passed along with the API call when sending the message
     */
    protected array $options = [];

    /**
     * SparkPost Template ID to be rendered, if specified
     */
    protected ?string $template = null;

    /**
     * Array of global substitution variables for email (template) rendering
     */
    protected array $globalVariables = [];

    /**
     * Array of recipient-specific substitution variables for email (template) rendering
     */
    protected array $variables = [];

    public function __construct(array $options = [])
    {
        $this->setOptions($options);

        // make SparkPost message transactional by default (API defaults to non-transactional)
        if (!array_key_exists('transactional', $options)) {
            $this->setTransactional();
        }
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

    /**
     * Set the value of a single option by name
     */
    public function setOption(string $name, $value): SparkPost
    {
        $this->options[$name] = $value;

        return $this;
    }

    /**
     * Get the value of a single option by name, or null if the option is undefined
     */
    public function getOption(string $name)
    {
        if (array_key_exists($name, $this->options)) {
            return $this->options[$name];
        }

        return null;
    }

    /**
     * Indicate to SparkPost that this is a transactional message
     */
    public function setTransactional(bool $transactional = true): SparkPost
    {
        return $this->setOption('transactional', $transactional);
    }

    /**
     * Returns true when this is a transactional message
     */
    public function isTransactional(): bool
    {
        return $this->getOption('transactional');
    }

    /**
     * Set SparkPost template ID to use
     *
     * @param  string|null $template
     * @return self
     */
    public function setTemplateId(?string $template): SparkPost
    {
        $this->template = $template;
        return $this;
    }

    /**
     * Get SparkPost template ID to use
     *
     * @return string|null
     */
    public function getTemplateId(): ?string
    {
        return $this->template;
    }

    /**
     * Set the global substitution variables to use with the template
     */
    public function setGlobalVariables(array $globalVariables): SparkPost
    {
        $this->globalVariables = $globalVariables;
        return $this;
    }

    /**
     * Get the global substitution variables to use with the template
     */
    public function getGlobalVariables(): array
    {
        return $this->globalVariables;
    }

    /**
     * Set the substitution variables for a given recipient as identified by its email address
     */
    public function setVariables(string $recipient, array $variables): SparkPost
    {
        $this->variables[$recipient] = $variables;
        return $this;
    }

    /**
     * Set the substitution variables for all recipients (indexed array where recipient's email address is the key)
     */
    public function setAllVariables(array $variablesPerRecipient): SparkPost
    {
        $this->variables = $variablesPerRecipient;
        return $this;
    }

    /**
     * Get the substitution variables for all recipients
     *
     * @return array
     */
    public function getVariables(): array
    {
        return $this->variables;
    }

    public function getSender(): ?AddressInterface
    {
        $sender = parent::getSender();

        if (!($sender instanceof AddressInterface)) {
            $from = parent::getFrom();
            if (!count($from)) {
                return null;
            }

            // get first sender from the list
            $from->rewind();
            $sender = $from->current();
        }

        return $sender;
    }
}
