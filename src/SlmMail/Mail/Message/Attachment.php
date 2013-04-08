<?php

namespace SlmMail\Mail\Message;

/**
 * An attachment is a file that is "attached" to a message. Some APIs require a MIME-Type
 *
 * @package SlmMail\Mail\Message
 */
class Attachment
{
    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $content;

    /**
     * @var string
     */
    protected $contentType;


    /**
     * Constructor
     *
     * @param string $name
     * @param string $content
     * @param string $contentType
     */
    public function __construct($name, $content, $contentType = '')
    {
        $this->name        = $name;
        $this->content     = $contentType;
        $this->contentType = $contentType;
    }

    /**
     * Set name of the attachment
     *
     * @param  string $name
     * @return Attachment
     */
    public function setName($name)
    {
        $this->name = $name;
        return $this;
    }

    /**
     * Get the name of the attachment
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param  string $content
     * @return Attachment
     */
    public function setContent($content)
    {
        $this->content = $content;
        return $this;
    }

    /**
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @param  string $contentType
     * @return Attachment
     */
    public function setContentType($contentType)
    {
        $this->contentType = $contentType;
    }

    /**
     * @return string
     */
    public function getContentType()
    {
        return $this->contentType;
    }
}
