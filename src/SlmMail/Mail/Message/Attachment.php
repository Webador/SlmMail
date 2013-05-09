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
     * Constructor. Note that most API require the content to be encoded in base64. Check the documentation of
     * the API you plan to use to how to use it correctly
     *
     * @param string $name
     * @param string $content
     * @param string $contentType
     */
    public function __construct($name, $content, $contentType = '')
    {
        $this->name        = (string) $name;
        $this->content     = (string) $content;
        $this->contentType = (string) $contentType;
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
     * @return string
     */
    public function getContent()
    {
        return $this->content;
    }

    /**
     * @return string
     */
    public function getContentType()
    {
        return $this->contentType;
    }
}
