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
     * @var array
     */
    protected $validOptions = array(
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
        'global_merge_vars',
        'merge_vars',
        'google_analytics_domains',
        'google_analytics_campaign',
        'metadata',
        'recipient_metadata'
    );

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

        $params['message']['from_email'] = $from->getName();
        $params['message']['from_name']  = $from->getEmail();

        /**
         * TO part
         */

        foreach ($message->getTo() as $to) {
            $params['message']['to'][] = array(
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

        $params['message']['subject'] = $subject;

        /**
         * Content
         */

        $params['message']['html'] = $message->getBodyText();

        /**
         * Headers
         */

        foreach ($message->getHeaders() as $header) {
            $params['message']['headers'][$header->getFieldName()] = $header->getFieldValue();
        }

        /**
         * Tags
         */

        foreach ($message->getTags() as $tag) {
            $params['message']['tags'][] = $tag;
        }

        /**
         * Attachments
         */

        foreach ($message->getAttachments() as $attachment) {
            $params['message']['attachments'][] = array(
                'type'    => $attachment->getContentType(),
                'name'    => $attachment->getName(),
                'content' => $attachment->getContent()
            );
        }

        /**
         * Optional options specific
         */

        foreach ($message->getOptions() as $key => $value) {
            if (array_key_exists($key, $this->validOptions)) {
                $params['message'][$key] = $value;
            }
        }

        $this->prepareHttpClient('/messages/send.json', $params);
    }

    /**
     * @param  string $path
     * @param  array $params
     * @return \Zend\Http\Client
     */
    protected function prepareHttpClient($path, array $params)
    {
        $client = parent::prepareHttpClient($path, $params);

        $client->getRequest()
               ->getHeaders()
               ->addHeaderLine('Content-Type', 'application/json');
    }

    /**
     * {@inheritDoc}
     */
    function getAuthenticationParameters()
    {
        return array('key' => $this->key);
    }
}
