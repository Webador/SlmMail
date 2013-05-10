<?php

namespace SlmMail\Mail\Message;

use Zend\Mail\Message;
use Zend\Mime\Part;

class Mandrill extends Message
{
    /**
     * @var array
     */
    protected $tags = array();

    /**
     * @var array
     */
    protected $options = array();

    /**
     * @var array
     */
    protected $validOptions = array(
        'important',
        'track_opens',
        'track_clicks',
        'auto_text',
        'auto_html',
        'inline_css',
        'url_strip_qs',
        'preserve_recipients',
        'tracking_domain',
        'signing_domain',
        'merge',
        'google_analytics_domains',
        'google_analytics_campaign'
    );

    /**
     * @var string
     */
    protected $template;

    /**
     * @var array
     */
    protected $templateContent = array();

    /**
     * @var array
     */
    protected $globalVariables = array();

    /**
     * @var array
     */
    protected $variables = array();

    /**
     * @var Part[]|array
     */
    protected $images = array();

    /**
     * Get all tags for this message
     *
     * @return array
     */
    public function getTags()
    {
        return $this->tags;
    }

    /**
     * Set all tags for this message
     *
     * @param  array $tags
     * @return self
     */
    public function setTags(array $tags)
    {
        $this->tags = $tags;
        return $this;
    }

    /**
     * Add a tag to this message
     *
     * @param string $tag
     * @return self
     */
    public function addTag($tag)
    {
        $this->tags[] = (string) $tag;
        return $this;
    }

    /**
     * Add options to the message
     *
     * @param  array $options
     * @throws Exception\InvalidArgumentException
     * @return self
     */
    public function setOptions(array $options)
    {
        foreach ($options as $key => $value) {
            if (!array_key_exists($key, $this->validOptions)) {
                throw new Exception\InvalidArgumentException(sprintf(
                    'Invalid option %s given', $key
                ));
            }
        }

        $this->options = $options;
        return $this;
    }

    /**
     * Set an option to the message
     *
     * @param  string $key
     * @param  string $value
     * @throws Exception\InvalidArgumentException
     * @return self
     */
    public function setOption($key, $value)
    {
        if (!in_array($key, $this->validOptions)) {
            throw new Exception\InvalidArgumentException(sprintf(
                'Invalid option %s given', $key
            ));
        }

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

    /**
     * Set Mandrill template name to use
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
     * Get Mandrill template name to use
     *
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * Set template content to inject
     *
     * @param  array $templateContent
     * @return Mandrill
     */
    public function setTemplateContent(array $templateContent)
    {
        $this->templateContent = $templateContent;
        return $this;
    }

    /**
     * Get template content to inject
     *
     * @return array
     */
    public function getTemplateContent()
    {
        return $this->templateContent;
    }

    /**
     * Set the global parameters to use with the template
     *
     * @param  array $globalVariables
     * @return self
     */
    public function setGlobalVariables(array $globalVariables)
    {
        $this->globalVariables = $globalVariables;
        return $this;
    }

    /**
     * Get the global parameters to use with the template
     *
     * @return array
     */
    public function getGlobalVariables()
    {
        return $this->globalVariables;
    }

    /**
     * Set the template parameters for a given recipient address
     *
     * @param  string $recipient
     * @param  array  $variables
     * @return Mandrill
     */
    public function setVariables($recipient, array $variables)
    {
        $this->variables[$recipient] = $variables;
        return $this;
    }

    /**
     * Get the template parameters for all recipients
     *
     * @return array
     */
    public function getVariables()
    {
        return $this->variables;
    }

    /**
     * Set attachments to the message
     *
     * @param  Part[]|array $images
     * @return self
     */
    public function setImages(array $images)
    {
        $this->images = $images;
        return $this;
    }

    /**
     * Add image to the message
     *
     * @param  Part $image
     * @return self
     */
    public function addImage(Part $image)
    {
        $this->images[] = $image;
        return $this;
    }

    /**
     * Get images of the message
     *
     * @return array|Part[]
     */
    public function getImages()
    {
        return $this->images;
    }
}
