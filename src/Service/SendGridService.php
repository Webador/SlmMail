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

use Laminas\Http\Client   as HttpClient;
use Laminas\Http\Request  as HttpRequest;
use Laminas\Http\Response as HttpResponse;
use Laminas\Mail\Address;
use Laminas\Mail\Message;

class SendGridService extends AbstractMailService
{
    /**
     * API endpoint
     */
    protected const API_ENDPOINT = 'https://sendgrid.com/api';

    /**
     * SendGrid username
     *
     * @var string
     */
    protected $username;

    /**
     * SendGrid API key
     *
     * @var string
     */
    protected $apiKey;


    /**
     * @param string $username
     * @param string $apiKey
     */
    public function __construct(string $username, string $apiKey)
    {
        $this->username = $username;
        $this->apiKey   = $apiKey;
    }

    /**
     * ------------------------------------------------------------------------------------------
     * MESSAGES
     * ------------------------------------------------------------------------------------------
     */

    /**
     * {@inheritDoc}
     * @link http://sendgrid.com/docs/API_Reference/Web_API/mail.html
     * @return array
     */
    public function send(Message $message): array
    {
        $from = $message->getFrom();
        if (count($from) !== 1) {
            throw new Exception\RuntimeException(
                'SendGrid API requires exactly one from sender'
            );
        }

        if (count($message->getCc())) {
            throw new Exception\RuntimeException('SendGrid does not support CC addresses');
        }

        $parameters = [
            'from'     => $from->rewind()->getEmail(),
            'fromname' => $from->rewind()->getName(),
            'subject'  => $message->getSubject(),
            'text'     => $this->extractText($message),
            'html'     => $this->extractHtml($message)
        ];

        foreach ($message->getTo() as $address) {
            $parameters['to'][] = $address->getEmail();
        }

        foreach ($message->getBcc() as $address) {
            $parameters['bcc'][] = $address->getEmail();
        }

        $replyTo = $message->getReplyTo();
        if (count($replyTo) > 1) {
            throw new Exception\RuntimeException('SendGrid has only support for one Reply-To address');
        } elseif (count($replyTo)) {
            $parameters['replyto'] = $replyTo->rewind()->getEmail();
        }

        $client = $this->prepareHttpClient('/mail.send.json');
        // Set Parameters as POST, since prepareHttpClient() put only GET parameters
        $client->setParameterPost($parameters);

        // Eventually add files. This cannot be done before prepareHttpClient call because prepareHttpClient
        // reset all parameters (response, request...), therefore we would loose the file upload
        $post        = $client->getRequest()->getPost();
        $attachments = $this->extractAttachments($message);
        foreach ($attachments as $attachment) {
            $post->set('files[' . $attachment->filename . ']', $attachment->getRawContent());
        }

        $response = $client->setMethod(HttpRequest::METHOD_POST)
                           ->setEncType(HttpClient::ENC_FORMDATA)
                           ->send();

        return $this->parseResponse($response);
    }

    /**
     * Get a list of bounces with addresses and response codes, optionally with dates
     *
     * @param  int    $date      must be 1 if you want to retrieve dates
     * @param  string $startDate if specified, must be in YYYY-MM-DD format and < $endDate
     * @param  string $endDate   if specified, must be in YYYY-MM-DD format and > $startDate
     * @param  bool   $aggregate true if you are interested in all-time totals
     * @return array
     */
    public function getStatistics(
        int $date = 1,
        string $startDate = '',
        string $endDate = '',
        bool $aggregate = false
    ): array {
        $parameters = ['date' => $date, 'start_date' => $startDate, 'end_date' => $endDate, 'aggregate' => (int)$aggregate];

        $response = $this->prepareHttpClient('/stats.get.json', $parameters)
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * ------------------------------------------------------------------------------------------
     * BOUNCES
     * ------------------------------------------------------------------------------------------
     */

    /**
     * Get a list of bounces with addresses and response codes, optionally with dates
     *
     * @param  int    $date      must be 1 if you want to retrieve dates
     * @param  int    $days      if specified, must be superior to 0
     * @param  string $startDate if specified, must be in YYYY-MM-DD format and < $endDate
     * @param  string $endDate   if specified, must be in YYYY-MM-DD format and > $startDate
     * @param  string $email     optional email to search for
     * @param  int $limit        optional field to limit the number of returned results
     * @param  int $offset       optional beginning point to retrieve results
     * @return array
     */
    public function getBounces(
        int $date = 1,
        int $days = 1,
        string $startDate = '',
        string $endDate = '',
        string $email = '',
        int $limit = 100,
        int $offset = 0
    ): array {
        $parameters = ['date' => $date, 'days' => $days, 'start_date' => $startDate, 'end_date' => $endDate,
                            'email' => $email, 'limit' => $limit, 'offset' => $offset];

        $response = $this->prepareHttpClient('/bounces.get.json', $parameters)
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * Delete an address from the Bounce list. Note that if no parameters are specified the ENTIRE list will be deleted.
     *
     * @param  string $startDate if specified, must be in YYYY-MM-DD format and < $endDate
     * @param  string $endDate   if specified, must be in YYYY-MM-DD format and > $startDate
     * @param  string $email     optional email to search for
     * @return array
     */
    public function deleteBounces(string $startDate = '', string $endDate = '', string $email = ''): array
    {
        $parameters = ['start_date' => $startDate, 'end_date' => $endDate, 'email' => $email];

        $response = $this->prepareHttpClient('/bounces.delete.json', $parameters)
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * @param  string $startDate if specified, must be in YYYY-MM-DD format and < $endDate
     * @param  string $endDate   if specified, must be in YYYY-MM-DD format and > $startDate
     * @return array
     */
    public function countBounces(string $startDate = '', string $endDate = ''): array
    {
        $parameters = ['start_date' => $startDate, 'end_date' => $endDate];

        $response = $this->prepareHttpClient('/bounces.count.json', $parameters)
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * ------------------------------------------------------------------------------------------
     * SPAMS
     * ------------------------------------------------------------------------------------------
     */

    /**
     * Retrieve and delete entries in the Spam Reports list
     *
     * @param  int    $date      must be 1 if you want to retrieve dates
     * @param  int    $days      if specified, must be superior to 0
     * @param  string $startDate if specified, must be in YYYY-MM-DD format and < $endDate
     * @param  string $endDate   if specified, must be in YYYY-MM-DD format and > $startDate
     * @param  string $email     optional email to search for
     * @param  int $limit        optional field to limit the number of returned results
     * @param  int $offset       optional beginning point to retrieve results
     * @return array
     */
    public function getSpamReports(
        int $date = 1,
        int $days = 1,
        string $startDate = '',
        string $endDate = '',
        string $email = '',
        int $limit = 100,
        int $offset = 0
    ): array {
        $parameters = ['date' => $date, 'days' => $days, 'start_date' => $startDate, 'end_date' => $endDate,
                            'email' => $email, 'limit' => $limit, 'offset' => $offset];

        $response = $this->prepareHttpClient('/spamreports.get.json', $parameters)
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * Delete an address from the Spam Reports list
     *
     * @param  string $email email to search for
     * @return array
     */
    public function deleteSpamReport(string $email = ''): array
    {
        $response = $this->prepareHttpClient('/spamreports.delete.json', ['email' => $email])
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * ------------------------------------------------------------------------------------------
     * BLOCKS
     * ------------------------------------------------------------------------------------------
     */

    /**
     * Get a list of blocks with addresses and response codes, optionally with dates
     *
     * @link http://sendgrid.com/docs/API_Reference/Web_API/blocks.html
     * @param  int    $date      must be 1 if you want to retrieve dates
     * @param  int    $days      if specified, must be superior to 0
     * @param  string $startDate if specified, must be in YYYY-MM-DD format and < $endDate
     * @param  string $endDate   if specified, must be in YYYY-MM-DD format and > $startDate
     * @return array
     */
    public function getBlocks(
        int $date = 1,
        int $days = 1,
        string $startDate = '',
        string $endDate = ''
    ): array {
        $parameters = [
            'date'       => $date,
            'days'       => $days,
            'start_date' => $startDate,
            'end_date'   => $endDate
        ];

        $response = $this->prepareHttpClient('/blocks.get.json', $parameters)
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * Delete an address from the block list
     *
     * @link http://sendgrid.com/docs/API_Reference/Web_API/blocks.html
     * @param  string $email
     * @return array
     */
    public function deleteBlock(string $email): array
    {
        $response = $this->prepareHttpClient('/blocks.delete', ['email' => $email])
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * @param string $uri
     * @param array $parameters
     * @return \Laminas\Http\Client
     */
    private function prepareHttpClient(string $uri, array $parameters = []): HttpClient
    {
        $parameters = array_merge(['api_user' => $this->username, 'api_key' => $this->apiKey], $parameters);

        return $this->getClient()
                    ->resetParameters()
                    ->setMethod(HttpRequest::METHOD_GET)
                    ->setUri(self::API_ENDPOINT . $uri)
                    ->setParameterGet($this->filterParameters($parameters));
    }

    /**
     * @param  HttpResponse $response
     * @throws Exception\RuntimeException
     * @return array
     */
    private function parseResponse(HttpResponse $response): array
    {
        $result = json_decode($response->getBody(), true);

        if (!is_array($result)) {
            throw new Exception\RuntimeException(sprintf(
                'An error occured on SendGrid (http code %s), could not interpret result as JSON. Body: %s',
                $response->getStatusCode(),
                $response->getBody()
            ));
        }

        if ($response->isSuccess()) {
            return $result;
        }

        // There is a 4xx error
        if ($response->isClientError()) {
            if (isset($result['errors']) && is_array($result['errors'])) {
                $message = implode(', ', $result['errors']);
            } elseif (isset($result['error'])) {
                $message = $result['error'];
            } else {
                $message = 'Unknown error';
            }

            throw new Exception\RuntimeException(sprintf(
                'An error occured on SendGrid (http code %s), message: %s',
                $response->getStatusCode(),
                $message
            ));
        }

        // There is a 5xx error
        throw new Exception\RuntimeException('SendGrid server error, please try again');
    }
}
