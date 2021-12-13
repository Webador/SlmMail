<?php

namespace SlmMail\Mail\Message;

use Laminas\Mail\Address\AddressInterface;
use Laminas\Mail\Message;

class SparkPost extends Message
{
    /**
     * Options that will be passed along with the API call when sending the message
     * @var array $options
     */
    protected $options = [];

    /**
     * SparkPost Template ID to be rendered, if specified
     * @var string|null $template
     */
    protected $template = null;

    /**
     * Array of global substitution variables for email (template) rendering
     * @var array $globalVariables
     */
    protected $globalVariables = [];

    /**
     * Array of recipient-specific substitution variables for email (template) rendering
     * @var array $variables
     */
    protected $variables = [];

    /**
     * Name of the campaign. Maximum length - 64 bytes
     * @var string|null $campaignId
     */
    protected $campaignId = null;

    /**
     * @var array $attachments Array of attachments. Each attachment has a name, type and base64-encoded data-string without line breaks.
     */
    protected $attachments = [];

    /**
     * @var string|null $returnPath
     */
    protected $returnPath = null;

    /**
     * @param array $options
     */
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
     * Set SparkPost campaign ID to use. Maximum length is 64 bytes, and the input
     * will be truncated if it exceeds that. Empty strings are nullified and hence
     * ignored.
     *
     * @param  string|null $campaignId
     * @return self
     */
    public function setCampaignId(?string $campaignId): SparkPost
    {
        $this->campaignId = (
            is_string($campaignId)
            ? (substr($campaignId, 0, 64) ?: null)
            : null
        );

        return $this;
    }

    /**
     * Get SparkPost campaign ID to use
     *
     * @return string|null
     */
    public function getCampaignId(): ?string
    {
        return $this->campaignId;
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

    public function getAttachments(): array
    {
        return $this->attachments;
    }

    /**
     * @param string $name The filename of the attachment. This is inserted into the filename parameter of the Content-Disposition header. Maximum length - 255 bytes
     * @param string $type The MIME type of the attachment. The value will apply as-is to the Content-Type header of the generated MIME part for the attachment.
    Include the charset parameter, if needed (e.g. text/html; charset="UTF-8")
     * @param string $data The content of the attachment as a Base64 encoded string.
     */
    public function addAttachment(string $name, string $type, string $data): void
    {
        $this->attachments[] = [
            'name' => substr($name, 0, 255),
            'type' => $type,
            'data' => str_replace(["\r", "\n"], '', $data),
        ];
    }

    /**
     * Set the return path for this message. SparkPost will overwrite the local part (blabla@...)
     * of the address, unless you have an enterprise account.
     * @param string|null $returnPath Must be valid email address.
     * @return $this
     */
    public function setReturnPath(?string $returnPath): SparkPost
    {
        $this->returnPath = $returnPath ?: null;
        return $this;
    }

    /**
     * Get the return path for this message.
     * @return string|null The configured return path, or `null` if no return path was set
     */
    public function getReturnPath(): ?string
    {
        return $this->returnPath;
    }
}
