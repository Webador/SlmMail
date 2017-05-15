<?php
/**
 * Copyright (c) 2012-2013 Jurian Sluiman.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 *   * Redistributions of source code must retain the above copyright
 *     notice, this list of conditions and the following disclaimer.
 *
 *   * Redistributions in binary form must reproduce the above copyright
 *     notice, this list of conditions and the following disclaimer in
 *     the documentation and/or other materials provided with the
 *     distribution.
 *
 *   * Neither the names of the copyright holders nor the names of the
 *     contributors may be used to endorse or promote products derived
 *     from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @author      Jurian Sluiman <jurian@juriansluiman.nl>
 * @copyright   2012-2013 Jurian Sluiman.
 * @license     http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link        http://juriansluiman.nl
 */

namespace SlmMail\Service;

use SlmMail\Mail\Message\ElasticEmail as ElasticEmailMessage;
use Zend\Http\Request as HttpRequest;
use Zend\Http\Response as HttpResponse;
use Zend\Mail\Address;
use Zend\Mail\Message;
use Zend\Mime\Part;
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
        }

        // Attachments are handled using a very strange way in Elastic Email. They must first be uploaded
        // to their API and THEN appended here. Therefore, you should limit how many attachments you have
        $attachmentIds = array();
        $attachments   = $this->extractAttachments($message);
        foreach ($attachments as $attachment) {
            $attachmentIds[] = $this->uploadAttachment($attachment);
        }
        if (count($attachmentIds)) {
            $parameters['attachments'] = implode(';', $attachmentIds);
        }

        $response = $this->prepareHttpClient('/mailer/send', $parameters, HttpRequest::METHOD_POST)
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
     * @param  Part $attachment
     * @return int The attachment id
     */
    public function uploadAttachment(Part $attachment)
    {
        $request = $this->prepareHttpClient('/attachments/upload', array('file' => $attachment->filename))
                        ->setMethod(HttpRequest::METHOD_PUT)
                        ->setRawBody($attachment->getRawContent())
                        ->getRequest();

        // Elastic Email handles the content type of the message itself. Based on the extension of
        // the file, Elastic Email determines the content type. The attachment must be uploaded to
        // the server with always the application/x-www-form-urlencoded content type.
        //
        // More information: http://support.elasticemail.com/discussions/questions/1486-how-to-set-content-type-of-an-attachment
        $request->getHeaders()->addHeaderLine('Content-Type', 'application/x-www-form-urlencoded')
                              ->addHeaderLine('Content-Length', strlen($attachment->getRawContent()));

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
     * @param string $method
     * @return \Zend\Http\Client if format given is neither "xml" or "csv"
     */
    private function prepareHttpClient($uri, array $parameters = array(), $method = HttpRequest::METHOD_GET)
    {
        if (isset($parameters['format']) && !in_array($parameters['format'], array('xml', 'csv'))) {
            throw new Exception\RuntimeException(sprintf(
                'Formats supported by Elastic Email API are either "xml" or "csv", "%s" given',
                $parameters['format']
            ));
        }

        $parameters = array_merge(array('username' => $this->username, 'api_key' => $this->apiKey), $parameters);

        // Some endpoints such as /send uses post method.
        if ($method === HttpRequest::METHOD_GET) {
            $client = $this->getClient()->resetParameters()
                ->setMethod($method)
                ->setUri(self::API_ENDPOINT . $uri)
                ->setParameterGet($this->filterParameters($parameters));
        } else {
            $client = $this->getClient()->resetParameters()
                ->setMethod(HttpRequest::METHOD_POST)
                ->setUri(self::API_ENDPOINT . $uri)
                ->setParameterPost($this->filterParameters($parameters));
        }

        return $client;
    }

    /**
     * Note that currently, ElasticEmail API only returns 200 status, hence making error handling nearly
     * impossible. That's why as of today, we only return the content body without any error handling. If you
     * have any idea to solve this issue, please add a PR.
     *
     * @param  HttpResponse $response
     * @throws Exception\InvalidCredentialsException
     * @return string
     */
    private function parseResponse(HttpResponse $response)
    {
        $result = $response->getBody();

        if (stripos($result, 'Unauthorized:') !== false) {
            throw new Exception\InvalidCredentialsException(
                'Authentication error: missing or incorrect Elastic Email API key'
            );
        }

        return $result;
    }
}
