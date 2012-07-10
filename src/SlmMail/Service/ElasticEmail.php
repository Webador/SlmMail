<?php
/**
 * Copyright (c) 2012 Jurian Sluiman.
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
 * @package     SlmMail
 * @subpackage  Service
 * @author      Jurian Sluiman <jurian@juriansluiman.nl>
 * @copyright   2012 Jurian Sluiman.
 * @license     http://www.opensource.org/licenses/bsd-license.php  BSD License
 * @link        http://juriansluiman.nl
 */
namespace SlmMail\Service;

use Zend\Mail\Message,
    Zend\Http\Client,
    Zend\Http\Request,
    Zend\Http\Response,
    Zend\Mail\Exception\RuntimeException,
    SlmMail\Mail\Message\ElasticEmail as ElasticEmailMessage;

class ElasticEmail
{
    const API_URI = 'https://api.elasticemail.com/';

    protected $apiKey;
    protected $username;
    protected $client;
    protected $statuses = array(0, 1, 2, 4, 5, 6, 7, 8, 9);

    /**
     * Constructor
     *
     * @param string $username
     * @param string $api_key
     */
    public function __construct ($username, $api_key)
    {
        $this->username = $username;
        $this->apiKey = $api_key;
    }

    /**
     * Send message to ElasticEmail service
     *
     * @link http://elasticemail.com/api-documentation/send
     * @param Message $message
     * @return string
     */
    public function send (Message $message)
    {
        $data = array(
            'subject'   => $message->getSubject(),
            'body_html' => $message->getBody(),
            'body_text' => $message->getBodyText(),
        );

        $to = array();
        foreach ($message->getTo() as $address) {
            $to[] = $address->toString();
        }
        foreach ($message->getCc() as $address) {
            $to[] = $address->toString();
        }
        foreach ($message->getBcc() as $address) {
            $to[] = $address->toString();
        }
        $data['to'] = implode(';', $to);

        $from = $message->getFrom();
        if (1 !== count($from)) {
            throw new RuntimeException('Elastic Email requires exactly one from address');
        }
        $from->rewind();
        $from = $from->current();
        $data['from']      = $from->getEmail();
        $data['from_name'] = $from->getName();

        $replyTo = $message->getReplyTo();
        if (1 < count($replyTo)) {
            throw new RuntimeException('Elastic Email has only support for one reply-to address');
        } elseif (count($replyTo)) {
            $replyTo->rewind();
            $replyTo = $replyTo->current();

            $data['reply_to']      = $replyTo->getEmail();
            $data['reply_to_name'] = $replyTo->getName();
        }

        if ($message instanceof ElasticEmailMessage
            && null !== ($channel = $message->getChannel())
        ) {
            $data['channel'] = $channel;
        }

        /**
         * @todo Handling attachments for emails
         *
         * Example code how that possibly might work:
         *
         * <code>
         * if ($hasAttachment) {
         *      $attachments = array();
         *      foreach ($message->getAttachmentCollection() as $attachment) {
         *          $attachments[] = $this->uploadAttachment($attachment->getName(), $attachment->getContent());
         *      }
         *      $data['attachments'] = implode(';', $attachments);
         *  }
         * </code>
         */

        $response = $this->prepareHttpClient('/mailer/send', $data)
                         ->setMethod(Request::METHOD_POST)
                         ->send();
        return $this->parseResponse($response);
    }

    public function uploadAttachment ($name, $content)
    {
        /** @todo Implement uploading */
    }

    /**
     * Get status for a transaction
     *
     * @todo Add flags for "showstats", "showdetails", "showdelivered", "showfailed" and "showpending"
     * @link http://elasticemail.com/api-documentation/status
     * @param string $id
     * @return string
     */
    public function getStatus ($id)
    {
        $response = $this->prepareHttpClient('/mailer/status/' . $id)
                         ->send();
        return $this->parseResponse($response);
    }

    /**
     * Get detailed information from activity log on the emails that have been sent
     *
     * @todo Accept $from and $to as \DateTime objects
     *
     * @link http://elasticemail.com/api-documentation/log
     * @param string $format 'xml'|'csv'
     * @param string $compress 'true'|'false'
     * @param int $status
     * @param string $channel
     * @param string $from Format '5/19/2011 10:54:20 PM'
     * @param string $to Format '5/19/2011 10:54:20 PM'
     * @return string
     */
    public function getLog ($format = 'xml', $compress = null, $status = null, $channel = null, $from = null, $to = null)
    {
        if (!in_array($format, array('xml', 'csv'))) {
            throw new RuntimeException(sprintf(
                'Format %s is not a supported format',
                $format
            ));
        }else if (null !== $status &&!in_array($status, $this->statuses)) {
            throw new RuntimeException(sprintf(
                'Status %s is not a supported status',
                $status
            ));
        }

        $params   = compact('format', 'compress', 'status', 'channel', 'from', 'to');
        $params   = $this->filterNullParams($params);

        $response = $this->prepareHttpClient('/mailer/status/log', $params)
                         ->send();
        return $this->parseResponse($response);
    }

    /**
     * Get the amount of credit left on your account
     *
     * Example of return string:
     * <code>
     * <account id="username">
     *   <credit>3.31</credit>
     * </account>
     * </code>
     *
     * @link http://elasticemail.com/api-documentation/account-details
     * @return string
     */
    public function getAccountDetails ()
    {
        $response = $this->prepareHttpClient('/mailer/account-details')
                         ->send();
        return $this->parseResponse($response);
    }

    /**
     * Get list of email addresses which are currently in bounce list
     *
     * Example of return string:
     * <code>
     * <recipients>
     *   <recipient>address1@yahoo.com</recipient>
     *   <recipient>address2@gmail.com</recipient>
     *   <recipient>address3@hotmail.com</recipient>
     * </recipients>
     * </code>
     *
     * @link http://elasticemail.com/api-documentation/bounced
     * @param bool $detailed
     * @return string
     */
    public function getBounced ($detailed = false)
    {
        $params = array();
        if ($detailed) {
            $params['detailed'] = true;
        }

        $response = $this->getHttpClient('/mailer/list/bounced', $params)
                         ->send();
        return $this->parseResponse($response);
    }

    /**
     * Get list of email addresses which are currently in unsubscribers list
     *
     * Example of return string:
     * <code>
     * <recipients>
     *   <recipient>address1@yahoo.com</recipient>
     *   <recipient>address2@gmail.com</recipient>
     *   <recipient>address3@hotmail.com</recipient>
     * </recipients>
     * </code>
     *
     * @link http://elasticemail.com/api-documentation/bounced
     * @param bool $detailed
     * @return string
     */
    public function getUnsubscribed ($detailed = false)
    {
        $params = array();
        if ($detailed) {
            $params['detailed'] = true;
        }

        $response = $this->getHttpClient('/mailer/list/unsubscribed', $params)
                         ->send();
        return $this->parseResponse($response);
    }

    public function getHttpClient ()
    {
        if (null === $this->client) {
            $this->client = new Client;
        }

        return $this->client;
    }

    public function setHttpClient (Client $client)
    {
        $this->client = $client;
    }

    /**
     * Get a http client instance
     *
     * @param string $path
     * @return Client
     */
    protected function prepareHttpClient ($path, array $params = array())
    {
        $params = $params + array('username' => $this->username, 'api_key' => $this->apiKey);

        return $this->getHttpClient()
                    ->setMethod(Request::METHOD_GET)
                    ->setUri(self::API_URI . $path)
                    ->setParameterGet($params);
    }

    /**
     * Filter null values from the array
     *
     * Because parameters get interpreted when they are send, remove them
     * from the list before the request is sent.
     *
     * @param array $params
     * @param array $exceptions
     * @return array
     */
    protected function filterNullParams (array $params, array $exceptions = array())
    {
        $return = array();
        foreach ($params as $key => $value) {
            if (null !== $value || in_array($key, $exceptions)) {
                $return[$key] = $value;
            }
        }

        return $return;
    }

    /**
     * Parse a Reponse object and check for errors
     *
     * @param Response $response
     * @return StdClass
     */
    protected function parseResponse (Response $response)
    {
        if (!$response->isOk()) {
            var_dump($response);exit;
            throw new RuntimeException('Unknown error during request to Elastic Email server');
        }

        return $response->getBody();
    }
}