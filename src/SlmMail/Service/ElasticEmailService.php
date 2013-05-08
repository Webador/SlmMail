<?php

namespace SlmMail\Service;

use SlmMail\Mail\Message\Attachment;
use SlmMail\Mail\Message\Provider\ElasticEmail as ElasticEmailMessage;
use SlmMail\Service\AbstractMailService;
use Zend\Http\Request as HttpRequest;
use Zend\Http\Response as HttpResponse;
use Zend\Mail\Address;
use Zend\Mail\Message;
use SimpleXMLElement;
use DateTime;

class ElasticEmailService extends AbstractMailService
{
    /**
     * API endpoint
     */
    const API_ENDPOINT = 'https://api.elasticemail.com';

    /**
     * Elastic Email username
     *
     * @var string
     */
    protected $username;

    /**
     * Elastic Email API key
     *
     * @var string
     */
    protected $apiKey;

    /**
     * @param string $username
     * @param string $apiKey
     */
    public function __construct($username, $apiKey)
    {
        $this->username = (string) $username;
        $this->apiKey   = (string) $apiKey;
    }

    /**
     * ------------------------------------------------------------------------------------------
     * MESSAGES
     * ------------------------------------------------------------------------------------------
     */

    /**
     * {@inheritDoc}
     * @link   http://elasticemail.com/api-documentation/send
     * @return string The transaction id of the email
     */
    public function send(Message $message)
    {
        $from = $message->getFrom();
        if (count($from) !== 1) {
            throw new Exception\RuntimeException(
                'Elastic Email API requires exactly one from sender'
            );
        }

        $parameters = array(
            'from'      => $from->rewind()->getEmail(),
            'from_name' => $from->rewind()->getName(),
            'subject'   => $message->getSubject(),
            'body_text' => $this->extractText($message),
            'body_html' => $this->extractHtml($message)
        );

        $to = array();
        foreach ($message->getTo() as $address) {
            $to[] = $address->toString();
        }

        foreach ($message->getCc() as $address) {
            $to[] = $address->toString();
        }

        // Elastic Email treats Bcc exactly as To addresses (they are treated separately)
        foreach ($message->getBcc() as $address) {
            $to[] = $address->toString();
        }

        $parameters['to'] = implode(';', $to);

        $replyTo = $message->getReplyTo();
        if (count($replyTo) > 1) {
            throw new Exception\RuntimeException('Elastic Email has only support for one Reply-To address');
        } elseif (count($replyTo)) {
            $parameters['reply_to']      = $replyTo->rewind()->getEmail();
            $parameters['reply_to_name'] = $replyTo->rewind()->getName();
        }

        if ($message instanceof ElasticEmailMessage) {
            $parameters['channel']  = $message->getChannel();
            $parameters['template'] = $message->getTemplate();

            // Attachments are handled using a very strange way in Elastic Email. They must first be uploaded
            // to their API and THEN appended here. Therefore, you should limit how many attachments you have
            $attachmentIds = array();
            foreach ($message->getAttachments() as $attachment) {
                $attachmentIds[] = $this->uploadAttachment($attachment);
            }

            $parameters['attachments'] = implode(';', $attachmentIds);
        }

        $response = $this->prepareHttpClient('/mailer/send', $parameters)
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * Get status about an email (for instance, if it was sent correctly, if it was opened...)
     *
     * @link   http://elasticemail.com/api-documentation/status
     * @param  string $id
     * @throws Exception\RuntimeException
     * @return array
     */
    public function getEmailStatus($id)
    {
        $response = $this->prepareHttpClient('/mailer/status/' . $id)
                         ->send();

        $result = $this->parseResponse($response);

        // ElasticEmail has a strange error handling method: mailer status
        // returns an XML format for a valid call, otherwise a simple message
        // is returned. So check if the message could be XML, if not: exception
        if (strpos($result, '<') !== 0) {
            throw new Exception\RuntimeException(sprintf(
                'An error occurred on ElasticEmail: %s', $result
            ));
        }

        $xml = new SimpleXMLElement($result);
        return array(
            'id'         => (string) $xml->attributes()->id,
            'status'     => (string) $xml->status,
            'recipients' => (int)    $xml->recipients,
            'failed'     => (int)    $xml->failed,
            'delivered'  => (int)    $xml->delivered,
            'pending'    => (int)    $xml->pending,
        );
    }

    /**
     * Upload an attachment to Elastic Email so it can be reused when an email is sent
     *
     * @link   http://elasticemail.com/api-documentation/attachments-upload
     * @param  Attachment $attachment
     * @return int The attachment id
     */
    public function uploadAttachment(Attachment $attachment)
    {
        $request = $this->prepareHttpClient('/attachments/upload', array('file' => $attachment->getName()))
                        ->setMethod(HttpRequest::METHOD_PUT)
                        ->setRawBody($attachment->getContent())
                        ->getRequest();

        $request->getHeaders()->addHeaderLine('Content-Type', 'application/x-www-form-urlencoded')
                              ->addHeaderLine('Content-Length', strlen($attachment->getContent()));

        $response = $this->client->send($request);

        return $this->parseResponse($response);
    }

    /**
     * ------------------------------------------------------------------------------------------
     * ACCOUNTS
     * ------------------------------------------------------------------------------------------
     */

    /**
     * Get details about the user account (like left credit...)
     *
     * @link   http://elasticemail.com/api-documentation/account-details
     * @return array
     */
    public function getAccountDetails()
    {
        $response = $this->prepareHttpClient('/mailer/account-details')
                         ->send();

        $xml = new SimpleXMLElement($this->parseResponse($response));
        return array(
            'id'     => (string) $xml->attributes()->id,
            'credit' => (float)  $xml->credit,
        );
    }

    /**
     * ------------------------------------------------------------------------------------------
     * CHANNELS
     * ------------------------------------------------------------------------------------------
     */

    /**
     * Get a list of active channels
     *
     * @link   http://elasticemail.com/api-documentation/channels
     * @param  string $format can be either 'xml' or 'csv'
     * @return array
     */
    public function getActiveChannels($format = 'xml')
    {
        $response = $this->prepareHttpClient('/mailer/channel/list', array('format' => $format))
                         ->send();

        if ($format === 'csv') {
            return $this->parseResponse($response);
        }

        $xml = new SimpleXMLElement($this->parseResponse($response));

        $channels = array();
        foreach ($xml->children() as $channel) {
            $channels[] = array(
                'date' => new DateTime((string) $channel->attributes()->date),
                'name' => (string) $channel->attributes()->name,
                'info' => (string) $channel->attributes()->info,
            );
        }

        return $channels;
    }

    /**
     * Delete a channel by its name
     *
     * @link   http://elasticemail.com/api-documentation/channels
     * @param  string $name
     * @param  string $format can be either 'xml' or 'csv'
     * @return array
     */
    public function deleteChannel($name, $format = 'xml')
    {
        $response = $this->prepareHttpClient('/mailer/channel/delete', array('name' => $name, 'format' => $format))
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * @param  string $uri
     * @param  array $parameters
     * @throws Exception\RuntimeException if format given is neither "xml" or "csv"
     * @return \Zend\Http\Client
     */
    private function prepareHttpClient($uri, array $parameters = array())
    {
        if (isset($parameters['format']) && !in_array($parameters['format'], array('xml', 'csv'))) {
            throw new Exception\RuntimeException(sprintf(
                'Formats supported by Elastic Email API are either "xml" or "csv", "%s" given',
                $parameters['format']
            ));
        }

        $parameters = array_merge(array('username' => $this->username, 'api_key' => $this->apiKey), $parameters);

        return $this->getClient()->resetParameters()
                                 ->setMethod(HttpRequest::METHOD_GET)
                                 ->setUri(self::API_ENDPOINT . $uri)
                                 ->setParameterGet($this->filterParameters($parameters));
    }

    /**
     * Note that currently, ElasticEmail API only returns 200 status, hence making error handling nearly
     * impossible. That's why as of today, we only return the content body without any error handling. If you
     * have any idea to solve this issue, please add a PR.
     *
     * @param  HttpResponse $response
     * @throws Exception\InvalidCredentialsException
     * @return array
     */
    private function parseResponse(HttpResponse $response)
    {
        $result = $response->getBody();

        if ($result !== 'Unauthorized: ') {
            return $result;
        }

        throw new Exception\InvalidCredentialsException(
            'Authentication error: missing or incorrect Elastic Email API key'
        );
    }
}
