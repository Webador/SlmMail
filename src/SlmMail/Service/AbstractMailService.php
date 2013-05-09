<?php

namespace SlmMail\Service;

use Zend\Http\Client as HttpClient;
use Zend\Mail\Message;
use Zend\Mime\Message as MimeMessage;
use Zend\Mime\Mime;

/**
 * Class AbstractMailService
 */
abstract class AbstractMailService implements MailServiceInterface
{
    /**
     * @var HttpClient
     */
    protected $client;

    /**
     * Extract text part from a message
     *
     * @param  Message $message
     * @return string
     */
    protected function extractText(Message $message)
    {
        $body = $message->getBody();

        if (is_string($body)) {
            return $body;
        }

        if (!$body instanceof MimeMessage) {
            return '';
        }

        foreach ($body->getParts() as $part) {
            if ($part->type === 'text/plain' && $part->disposition !== Mime::DISPOSITION_ATTACHMENT) {
                return $part->getContent();
            }
        }
    }

    /**
     * Extract a HTML part from a message
     *
     * @param  Message $message
     * @return string
     */
    protected function extractHtml(Message $message)
    {
        $body = $message->getBody();

        // If body is not a MimeMessage object, then the body is just the text version
        if (is_string($body) || !$body instanceof MimeMessage) {
            return '';
        }

        foreach ($body->getParts() as $part) {
            if ($part->type === 'text/html' && $part->disposition !== Mime::DISPOSITION_ATTACHMENT) {
                return $part->getContent();
            }
        }
    }

    /**
     * Extract all attachments from a message
     *
     * Attachments are detected in the Mime message where
     * the type of the mime part is not text/plain or
     * text/html.
     *
     * @param  Message $message
     * @return array
     */
    protected function extractAttachments(Message $message)
    {
        $body = $message->getBody();

        // If body is not a MimeMessage object, then the body is just the text version
        if (is_string($body) || !$body instanceof MimeMessage) {
            return array();
        }

        $filter      = array('text/plain', 'text/html');
        $attachments = array();
        foreach ($body->getParts() as $part) {
            if (!in_array($part->type, $filter) || $part->disposition === Mime::DISPOSITION_ATTACHMENT) {
                $attachments[] = $part;
            }
        }

        return $attachments;
    }

    /**
     * Get HTTP client
     *
     * @return HttpClient
     */
    protected function getClient()
    {
        if (null === $this->client) {
            $this->setClient(new HttpClient);
        }

        return $this->client;
    }

    /**
     * Set HTTP client
     *
     * @param HttpClient $client
     * @return void
     */
    public function setClient(HttpClient $client)
    {
        $this->client = $client;
    }

    /**
     * Filter parameters recursively (for now, only null parameters and empty strings)
     *
     * @param  array $parameters
     * @return array
     */
    protected function filterParameters(array $parameters, array $whitelist = array())
    {
        foreach ($parameters as $key => &$value) {
            if (is_array($value) && !in_array($key, $whitelist)) {
                $value = $this->filterParameters($value, $whitelist);
            }
        }

        return array_filter($parameters, function($value) {
            return $value !== null && $value !== '';
        });
    }
}
