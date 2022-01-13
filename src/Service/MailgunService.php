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

use DateTime;
use SlmMail\Mail\Message\Mailgun as MailgunMessage;
use Laminas\Http\Client as HttpClient;
use Laminas\Http\Request as HttpRequest;
use Laminas\Http\Response as HttpResponse;
use Laminas\Mail\Message;

class MailgunService extends AbstractMailService
{
    /**
     * Mailgun domain to use
     *
     * @var string
     */
    protected $domain;

    /**
     * Mailgun API key
     *
     * @var string
     */
    protected $apiKey;

    /**
     * Mailgun API endpoint
     *
     * @var string
     */
    protected $apiEndpoint;

    /**
     * @param string $domain
     * @param string $apiKey
     * @param string $apiEndpoint
     */
    public function __construct(string $domain, string $apiKey, string $apiEndpoint)
    {
        $this->domain = $domain;
        $this->apiKey = $apiKey;
        $this->apiEndpoint = $apiEndpoint;
    }

    /**
     * ------------------------------------------------------------------------------------------
     * MESSAGES
     * ------------------------------------------------------------------------------------------
     */

    /**
     * {@inheritDoc}
     * @link http://documentation.mailgun.com/api-sending.html
     * @return string id of message
     */
    public function send(Message $message): string
    {
        $from = $message->getFrom();
        if (count($from) !== 1) {
            throw new Exception\RuntimeException(
                'Postage API requires exactly one from sender'
            );
        }

        $parameters = [
            'from' => $from->rewind()->toString(),
            'subject' => $message->getSubject(),
            'text' => $this->extractText($message),
            'html' => $this->extractHtml($message)
        ];

        $to = [];
        foreach ($message->getTo() as $address) {
            $to[] = $address->toString();
        }

        $parameters['to'] = implode(',', $to);

        $cc = [];
        foreach ($message->getCc() as $address) {
            $cc[] = $address->toString();
        }

        $parameters['cc'] = implode(',', $cc);

        $bcc = [];
        foreach ($message->getBcc() as $address) {
            $bcc[] = $address->toString();
        }

        $parameters['bcc'] = implode(',', $bcc);

        $replyTo = $message->getReplyTo();
        if (count($replyTo) > 1) {
            throw new Exception\RuntimeException('Mailgun has only support for one Reply-To address');
        } elseif (count($replyTo)) {
            $parameters['h:reply-to'] = $replyTo->rewind()->toString();
        }

        $attachments = $this->extractAttachments($message);
        foreach ($attachments as $attachment) {
            $parameters['attachment'][] = [
                'filename' => $attachment->filename
            ];
        }

        if ($message instanceof MailgunMessage) {
            $options = $message->getValidOptions();
            foreach ($message->getOptions() as $key => $value) {
                $parameters[$options[$key]] = $value;
            }

            $tags = $message->getTags();
            if (count($tags) > 0) {
                $parameters['o:tag'] = $tags;
            }

            $variables = $message->getRecipientVariables();
            if (count($variables)) {
                // It is only possible to add variables for recipients that exist in the To: field
                foreach ($variables as $recipient => $variable) {
                    if (!$message->getTo()->has($recipient)) {
                        throw new Exception\RuntimeException(sprintf(
                            'The email "%s" must be added as a receiver before you can add recipient variables',
                            $recipient
                        ));
                    }
                }

                $parameters['recipient-variables'] = json_encode($variables);
            }
        }

        $client = $this->prepareHttpClient('/messages', $parameters);

        // Eventually add files. This cannot be done before prepareHttpClient call because prepareHttpClient
        // reset all parameters (response, request...), therefore we would loose the file upload
        $attachments = $this->extractAttachments($message);
        foreach ($attachments as $attachment) {
            $client->setFileUpload(
                $attachment->filename,
                $attachment->disposition,
                $attachment->getRawContent(),
                $attachment->type
            );
        }
        $client->setEncType(HttpClient::ENC_FORMDATA);

        $response = $client->send();

        $result = $this->parseResponse($response);
        return $result['id'];
    }

    /**
     * @param string $uri
     * @param array $parameters
     * @param bool $perDomain
     * @return HttpClient
     */
    private function prepareHttpClient(string $uri, array $parameters = [], bool $perDomain = true): HttpClient
    {
        $client = $this->getClient()->resetParameters();
        $client->getRequest()
            ->getHeaders()
            ->addHeaderLine('Authorization', 'Basic ' . base64_encode('api:' . $this->apiKey));

        if ($perDomain) {
            $client->setUri($this->apiEndpoint . '/' . $this->domain . $uri);
        } else {
            $client->setUri($this->apiEndpoint . $uri);
        }

        return $client->setMethod(HttpRequest::METHOD_POST)
            ->setParameterPost($this->filterParameters($parameters));
    }

    /**
     * @param  HttpResponse $response
     * @throws Exception\InvalidCredentialsException
     * @throws Exception\ValidationErrorException
     * @throws Exception\RuntimeException
     * @return array
     */
    private function parseResponse(HttpResponse $response): array
    {
        $result = json_decode($response->getBody(), true);

        if ($response->isSuccess()) {
            return $result;
        }

        switch ($response->getStatusCode()) {
            case 400:
                throw new Exception\ValidationErrorException(sprintf(
                    'An error occured on Mailgun, reason: %s, details: %s',
                    $response->getReasonPhrase(),
                    $response->getBody()
                ));
            case 401:
                throw new Exception\InvalidCredentialsException('Authentication error: missing or incorrect Mailgun authorization');
            case 402:
                throw new Exception\RuntimeException(sprintf(
                    'An error occured on Mailgun, reason: %s, details: %s',
                    $response->getReasonPhrase(),
                    $response->getBody()
                ));
            case 500:
            case 502:
            case 503:
            case 504:
                throw new Exception\RuntimeException('Mailgun server error, please try again');
            default:
                throw new Exception\RuntimeException(sprintf(
                    'Unknown error during request to Mailgun server, reason: %s, details: %s',
                    $response->getReasonPhrase(),
                    $response->getBody()
                ));
        }
    }


    /**
     * ------------------------------------------------------------------------------------------
     * SPAM
     * ------------------------------------------------------------------------------------------
     */

    /**
     * Get log entries
     *
     * @link   http://documentation.mailgun.com/api-logs.html
     * @param  int $limit
     * @param  int $offset
     * @return array
     */
    public function getLogs(int $limit = 100, int $offset = 0): array
    {
        $parameters = ['limit' => $limit, 'skip' => $offset];

        $response = $this->prepareHttpClient('/log')
            ->setMethod(HttpRequest::METHOD_GET)
            ->setParameterGet($this->filterParameters($parameters))
            ->send();

        return $this->parseResponse($response);
    }

    /**
     * Get events entries
     *
     * @link  http://documentation.mailgun.com/api-events.html
     * @param Datetime $begin
     * @param Datetime|null $end
     * @param boolean $ascending
     * @param int $limit
     * @param array $fields
     * @return array
     */
    public function getEvents(
        DateTime $begin,
        ?DateTime $end = null,
        bool $ascending = true,
        int $limit = 300,
        array $fields = []
    ): array {
        // Date format like https://documentation.mailgun.com/api-intro.html#date-format
        $parameters = [
            'begin' => $begin->format(DateTime::RFC2822),
            'limit' => $limit,
        ];

        if (!empty($end)) {
            $parameters['end'] = $end->format(DateTime::RFC2822);
        }

        if ($ascending) {
            $parameters['ascending'] = 'yes';
        } else {
            $parameters['ascending'] = 'no';
        }

        // Additional fields to filter by
        if (!empty($fields)) {
            foreach ($fields as $key => $value) {
                $parameters[$key] = $value;
            }
        }

        // Get response
        $response = $this->prepareHttpClient('/events')
            ->setMethod(HttpRequest::METHOD_GET)
            ->setParameterGet($this->filterParameters($parameters))
            ->send();

        return $this->parseResponse($response);
    }

    /**
     * Get spam complaints (this happens when recipients click "report spam")
     *
     * @link   http://documentation.mailgun.com/api-complaints.html
     * @param  int $limit
     * @param  int $offset
     * @return array
     */
    public function getSpamComplaints(int $limit = 100, int $offset = 0): array
    {
        $parameters = ['limit' => $limit, 'skip' => $offset];

        $response = $this->prepareHttpClient('/complaints')
            ->setMethod(HttpRequest::METHOD_GET)
            ->setParameterGet($this->filterParameters($parameters))
            ->send();

        return $this->parseResponse($response);
    }

    /**
     * Get a single spam complaint by a given email address. This is useful to check if a particular
     * user has complained
     *
     * @link   http://documentation.mailgun.com/api-complaints.html
     * @param  string $address
     * @return array
     */
    public function getSpamComplaint(string $address): array
    {
        $response = $this->prepareHttpClient('/complaints/' . $address)
            ->setMethod(HttpRequest::METHOD_GET)
            ->send();

        return $this->parseResponse($response);
    }

    /**
     * ------------------------------------------------------------------------------------------
     * BOUNCES
     * ------------------------------------------------------------------------------------------
     */

    /**
     * Add an address to the complaints table
     *
     * @link   http://documentation.mailgun.com/api-complaints.html
     * @param  string $address
     * @return array
     */
    public function addSpamComplaint(string $address): array
    {
        $response = $this->prepareHttpClient('/complaints', ['address' => $address])
            ->send();

        return $this->parseResponse($response);
    }

    /**
     * Delete an address to the complaints table
     *
     * @link   http://documentation.mailgun.com/api-complaints.html
     * @param  string $address
     * @return array
     */
    public function deleteSpamComplaint(string $address): array
    {
        $response = $this->prepareHttpClient('/complaints/' . $address)
            ->setMethod(HttpRequest::METHOD_DELETE)
            ->send();

        return $this->parseResponse($response);
    }

    /**
     * Get bounces emails
     *
     * @link   http://documentation.mailgun.com/api-bounces.html
     * @param  int $limit
     * @param  int $offset
     * @return array
     */
    public function getBounces(int $limit = 100, int $offset = 0): array
    {
        $parameters = ['limit' => $limit, 'skip' => $offset];

        $response = $this->prepareHttpClient('/bounces')
            ->setMethod(HttpRequest::METHOD_GET)
            ->setParameterGet($this->filterParameters($parameters))
            ->send();

        return $this->parseResponse($response);
    }

    /**
     * Get a single bounce event by a given email address
     *
     * @link   http://documentation.mailgun.com/api-bounces.html
     * @param  string $address
     * @return array
     */
    public function getBounce(string $address): array
    {
        $response = $this->prepareHttpClient('/bounces/' . $address)
            ->setMethod(HttpRequest::METHOD_GET)
            ->send();

        return $this->parseResponse($response);
    }

    /**
     * ------------------------------------------------------------------------------------------
     * ROUTES
     * ------------------------------------------------------------------------------------------
     */

    /**
     * Add a bounce
     *
     * @link   http://documentation.mailgun.com/api-bounces.html
     * @param  string $address
     * @param  int $code
     * @param  string $error
     * @return array
     */
    public function addBounce(string $address, int $code = 550, string $error = ''): array
    {
        $response = $this->prepareHttpClient('/bounces', compact('address', 'code', 'error'))
            ->send();

        return $this->parseResponse($response);
    }

    /**
     * Delete a bounce
     *
     * @link   http://documentation.mailgun.com/api-bounces.html
     * @param  string $address
     * @return array
     */
    public function deleteBounce(string $address): array
    {
        $response = $this->prepareHttpClient('/bounces/' . $address)
            ->setMethod(HttpRequest::METHOD_DELETE)
            ->send();

        return $this->parseResponse($response);
    }

    /**
     * Add a new route (expression and action must be valid according to Mailgun syntax)
     *
     * @link http://documentation.mailgun.com/api-routes.html
     * @param  string $description A description for the route
     * @param  string $expression A filter expression
     * @param  string|array $actions A single or multiple actions
     * @param  int $priority Optional priority (smaller number indicates higher priority)
     * @return array
     */
    public function addRoute(string $description, string $expression, $actions, int $priority = 0): array
    {
        $actions = (array)$actions;

        $parameters = [
            'description' => $description,
            'expression' => $expression,
            'action' => array_reverse($actions), // For unknown reasons, Mailgun API saves
            // routes in the opposite order as you specify
            // them, hence the array_reverse
            'priority' => $priority
        ];

        $response = $this->prepareHttpClient('/routes', $parameters, false)
            ->send();

        return $this->parseResponse($response);
    }

    /**
     * Delete an existing route
     *
     * @link http://documentation.mailgun.com/api-routes.html
     * @param  string $id
     * @return array
     */
    public function deleteRoute(string $id): array
    {
        $response = $this->prepareHttpClient('/routes/' . $id, [], false)
            ->setMethod(HttpRequest::METHOD_DELETE)
            ->send();

        return $this->parseResponse($response);
    }

    /**
     * Get all the routes
     *
     * @link http://documentation.mailgun.com/api-routes.html
     * @param  int $limit
     * @param  int $offset
     * @return array
     */
    public function getRoutes(int $limit = 100, int $offset = 0): array
    {
        $parameters = ['limit' => $limit, 'skip' => $offset];

        $response = $this->prepareHttpClient('/routes', [], false)
            ->setMethod(HttpRequest::METHOD_GET)
            ->setParameterGet($this->filterParameters($parameters))
            ->send();

        return $this->parseResponse($response);
    }

    /**
     * Get route details
     *
     * @link http://documentation.mailgun.com/api-routes.html
     * @param  string $id
     * @return array
     */
    public function getRoute(string $id): array
    {
        $response = $this->prepareHttpClient('/routes/' . $id, [], false)
            ->setMethod(HttpRequest::METHOD_GET)
            ->send();

        return $this->parseResponse($response);
    }

    /**
     * Update an existing route (expression and action must be valid according to Mailgun syntax)
     *
     * @link http://documentation.mailgun.com/api-routes.html
     * @param  string $id Identifier of the route
     * @param  string $description A description for the route
     * @param  string $expression A filter expression
     * @param  string|array $actions A single or multiple actions
     * @param  int $priority Optional priority (smaller number indicates higher priority)
     * @return array
     */
    public function updateRoute(string $id, string $description = '', string $expression = '', $actions = [], int $priority = 0): array
    {
        $actions = (array)$actions;

        $parameters = [
            'description' => $description,
            'expression' => $expression,
            'action' => array_reverse($actions), // For unknown reasons, Mailgun API saves
            // routes in the opposite order as you specify
            // them, hence the array_reverse
            'priority' => $priority
        ];

        $response = $this->prepareHttpClient('/routes/' . $id, $parameters, false)
            ->setMethod(HttpRequest::METHOD_PUT)
            ->send();

        return $this->parseResponse($response);
    }
}
