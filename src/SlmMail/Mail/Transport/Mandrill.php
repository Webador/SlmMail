<?php

namespace SlmMail\Mail\Transport;

use SlmMail\Mail\Exception;
use SlmMail\Mail\Message\Provider\Mandrill as MandrillMessage;
use Zend\Mail\Message;

class Mandrill extends AbstractHttpTransport
{
    /**
     * @var string
     */
    protected $endpoint = 'https://mandrillapp.com/api/1.0';

    /**
     * @var string
     */
    protected $key;


    /**
     * Constructor
     *
     * @param  string $key
     * @throws Exception\RuntimeException if no key is given
     */
    public function __construct($key)
    {
        if (empty($key)) {
            throw new Exception\RuntimeException('A key is required to use Mandrill API, but none given');
        }

        $this->key = $key;
    }

    /**
     * {@inheritDoc}
     */
    public function send(Message $message)
    {
        if (!$message instanceof MandrillMessage) {
            throw new Exception\RuntimeException(sprintf(
                'Message given to Mandrill transport must be an instance of SlmMail\Mail\Message\Provider\Mandrill, %s given',
                get_class($message)
            ));
        }

        $params = array();

        /**
         * FROM part
         */

        $from = $message->getFrom();
        if (count($from) > 1) {
            throw new Exception\RuntimeException(sprintf(
                'Mandrill currently supports only one From address, %s given',
                count($from)
            ));
        }

        $from = $from->current();

        $params['from_email'] = $from->getName();
        $params['from_name']  = $from->getEmail();

        /**
         * TO part
         */

        foreach ($message->getTo() as $to) {
            $params['to'][] = array(
                'email' => $to->getEmail(),
                'name'  => $to->getName()
            );
        }

        /**
         * Subject part
         */

        $subject = $message->getSubject();
        if (empty($subject)) {
            throw new Exception\RuntimeException('Mandrill expects a subject, none given');
        }

        $params['subject'] = $subject;

        /**
         * Headers
         */

        foreach ($message->getHeaders() as $header) {
            $params['headers'][$header->getFieldName()] = $header->getFieldValue();
        }

        /**
         * Tags
         */

        foreach ($message->getTags() as $tag) {
            $params['tags'][] = $tag;
        }

        /**
         * Attachments
         */

        foreach ($message->getAttachments() as $attachment) {
            $params['attachments'][] = array(
                'type'    => $attachment->getContentType(),
                'name'    => $attachment->getName(),
                'content' => $attachment->getContent()
            );
        }

        /**
         * Optional options specific
         */


        $this->prepareHttpClient('/messages/send.json', $params);
    }

    /**
     * {@inheritDoc}
     */
    function getAuthenticationParameters()
    {
        return array('key' => $this->key);
    }
}
