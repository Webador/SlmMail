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

use SlmMail\Mail\Message\Postmark as PostmarkMessage;
use Zend\Http\Request as HttpRequest;
use Zend\Http\Response as HttpResponse;
use Zend\Mail\Address;
use Zend\Mail\Message;

class PostmarkService extends AbstractMailService
{
    /**
     * API endpoint
     */
    const API_ENDPOINT = 'https://api.postmarkapp.com';

    /**
     * Postmark supports a maximum of 20 recipients per messages
     */
    const RECIPIENT_LIMIT = 20;

    /**
     * Postmark API key
     *
     * @var string
     */
    protected $apiKey;

    /**
     * List of valid Postmark bounce filters
     *
     * @var array
     */
    protected $filters = array(
        'HardBounce',
        'Transient',
        'Unsubscribe',
        'Subscribe',
        'AutoResponder',
        'AddressChange',
        'DnsError',
        'SpamNotification',
        'OpenRelayTest',
        'Unknown',
        'SoftBounce',
        'VirusNotification',
        'ChallengeVerification',
        'BadEmailAddress',
        'SpamComplaint',
        'ManuallyDeactivated',
        'Unconfirmed',
        'Blocked'
    );

    /**
     * @param string $apiKey
     */
    public function __construct($apiKey)
    {
        $this->apiKey = (string) $apiKey;
    }

    /**
     * ------------------------------------------------------------------------------------------
     * MESSAGES
     * ------------------------------------------------------------------------------------------
     */

    /**
     * {@inheritDoc}
     * @link http://developer.postmarkapp.com/developer-build.html
     * @throws Exception\RuntimeException if the mail is sent to more than 20 recipients (Postmark limit)
     * @return array The id and UID of the sent message (if sent correctly)
     */
    public function send(Message $message)
    {
        $from = $message->getFrom();
        if (count($from) !== 1) {
            throw new Exception\RuntimeException(
                'Postmark API requires exactly one from sender'
            );
        }

        $parameters = array(
            'From'     => $from->rewind()->toString(),
            'Subject'  => $message->getSubject(),
            'TextBody' => $this->extractText($message),
            'HtmlBody' => $this->extractHtml($message)
        );

        $countRecipients = count($message->getTo());

        $to = array();
        foreach ($message->getTo() as $address) {
            $to[] = $address->toString();
        }

        $parameters['To'] = implode(',', $to);

        $countRecipients += count($message->getCc());

        $cc = array();
        foreach ($message->getCc() as $address) {
            $cc[] = $address->toString();
        }

        $parameters['Cc'] = implode(',', $cc);

        $countRecipients += count($message->getBcc());

        $bcc = array();
        foreach ($message->getBcc() as $address) {
            $bcc[] = $address->toString();
        }

        $parameters['Bcc'] = implode(',', $bcc);

        if ($countRecipients > self::RECIPIENT_LIMIT) {
            throw new Exception\RuntimeException(sprintf(
                'You have exceeded limitation for Postmark count recipients (%s maximum, %s given)',
                self::RECIPIENT_LIMIT,
                $countRecipients
            ));
        }

        $replyTo = $message->getReplyTo();
        if (count($replyTo) > 1) {
            throw new Exception\RuntimeException('Postmark has only support for one Reply-To address');
        } elseif (count($replyTo)) {
            $parameters['ReplyTo'] = $replyTo->rewind()->toString();
        }

        if ($message instanceof PostmarkMessage) {
            if ($message->getTag()) {
                $parameters['Tag'] = $message->getTag();
            }
        }

        $attachments = $this->extractAttachments($message);
        foreach ($attachments as $attachment) {
            $parameters['Attachments'][] = array(
                'Name'        => $attachment->filename,
                'ContentType' => $attachment->type,
                'Content'     => base64_encode($attachment->getRawContent())
            );
        }

        $response = $this->prepareHttpClient('/email')
                         ->setMethod(HttpRequest::METHOD_POST)
                         ->setRawBody(json_encode($this->filterParameters($parameters)))
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * ------------------------------------------------------------------------------------------
     * BOUNCES AND STATS
     * ------------------------------------------------------------------------------------------
     */

    /**
     * Get a summary of inactive emails and bounces by type
     *
     * @link http://developer.postmarkapp.com/developer-bounces.html#get-delivery-stats
     * @return array
     */
    public function getDeliveryStats()
    {
        $response = $this->prepareHttpClient('/deliverystats')
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * Get a portion of bounces according to the specified input criteria
     *
     * The $count and $offset are mandatory. For type, a specific set of types are available, defined as filter.
     *
     * @see $filters
     * @link http://developer.postmarkapp.com/developer-bounces.html#get-bounces
     * @param int $count
     * @param int $offset
     * @param string $type
     * @param string $inactive
     * @param string $emailFilter
     * @throws Exception\RuntimeException
     * @return array
     */
    public function getBounces($count, $offset, $type = null, $inactive = null, $emailFilter = null)
    {
        if (null !== $type && !in_array($type, $this->filters)) {
            throw new Exception\RuntimeException(sprintf(
                'Type %s is not a supported filter',
                $type
            ));
        }

        $parameters = compact('count', 'offset', 'type', 'inactive', 'emailFilter');
        $response   = $this->prepareHttpClient('/bounces', $parameters)
                           ->send();

        return $this->parseResponse($response);
    }

    /**
     * Get details about a single bounce
     *
     * @link  http://developer.postmarkapp.com/developer-bounces.html#get-a-single-bounce
     * @param  int $id
     * @return array
     */
    public function getBounce($id)
    {
        $response = $this->prepareHttpClient('/bounces/' . $id)
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * Get the raw source of the bounce Postmark accepted
     *
     * @link http://developer.postmarkapp.com/developer-bounces.html#get-bounce-dump
     * @param  int $id
     * @return string
     */
    public function getBounceDump($id)
    {
        $response = $this->prepareHttpClient('/bounces/' . $id . '/dump')
                         ->send();

        $result = $this->parseResponse($response);
        return $result['Body'];
    }

    /**
     * Get a list of tags used for the current Postmark server
     *
     * @link http://developer.postmarkapp.com/developer-bounces.html#get-bounce-tags
     * @return array
     */
    public function getBounceTags()
    {
        $response = $this->prepareHttpClient('/bounces/tags')
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * Activates a deactivated bounce
     *
     * @link http://developer.postmarkapp.com/developer-bounces.html#activate-a-bounce
     * @param  int $id
     * @return array
     */
    public function activateBounce($id)
    {
        $response = $this->prepareHttpClient('/bounces/' . $id . '/activate')
                         ->setMethod(HttpRequest::METHOD_PUT)
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * @param string $uri
     * @param array $parameters
     * @return \Zend\Http\Client
     */
    private function prepareHttpClient($uri, array $parameters = array())
    {
        $client = $this->getClient()->resetParameters();
        $client->getRequest()
               ->getHeaders()
               ->addHeaderLine('Accept', 'application/json')
               ->addHeaderLine('X-Postmark-Server-Token', $this->apiKey);

        return $client->setMethod(HttpRequest::METHOD_GET)
                      ->setUri(self::API_ENDPOINT . $uri)
                      ->setParameterGet($this->filterParameters($parameters));
    }

    /**
     * @param  HttpResponse $response
     * @throws Exception\InvalidCredentialsException
     * @throws Exception\ValidationErrorException
     * @throws Exception\RuntimeException
     * @return array
     */
    private function parseResponse(HttpResponse $response)
    {
        $result = json_decode($response->getBody(), true);

        if ($response->isSuccess()) {
            return $result;
        }

        switch ($response->getStatusCode()) {
            case 401:
                throw new Exception\InvalidCredentialsException('Authentication error: missing or incorrect Postmark API Key header');
            case 422:
                throw new Exception\ValidationErrorException(sprintf(
                    'An error occured on Postmark (error code %s), message: %s', $result['ErrorCode'], $result['Message']
                ), (int) $result['ErrorCode']);
            case 500:
                throw new Exception\RuntimeException('Postmark server error, please try again');
            default:
                throw new Exception\RuntimeException('Unknown error during request to Postmark server');
        }
    }
}
