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

use SlmMail\Mail\Message\AlphaMail as AlphaMailMessage;
use SlmMail\Service\AbstractMailService;
use Zend\Http\Request as HttpRequest;
use Zend\Http\Response as HttpResponse;
use Zend\Mail\Address;
use Zend\Mail\Message;

class AlphaMailService extends AbstractMailService
{
    /**
     * API endpoint
     */
    const API_ENDPOINT = 'http://api.amail.io/v1';

    /**
     * AlphaMail API key
     *
     * @var string
     */
    protected $apiKey;

    /**
     * @param string $apiKey
     */
    public function __construct($apiKey)
    {
        $this->apiKey   = (string) $apiKey;
    }

    /**
     * ------------------------------------------------------------------------------------------
     * MESSAGES
     * ------------------------------------------------------------------------------------------
     */

    /**
     * {@inheritDoc}
     * @link   http://app.amail.io/#/docs/api/
     * @return string The transaction id of the email
     */
    public function send(Message $message)
    {
        // Contrary to other services, you ABSOLUTELY need a template created on AlphaMail side,
        // you cannot directly send a message, therefore we need to enforce that we really have an
        // AlphaMailMessage instance
        if (!$message instanceof AlphaMailMessage) {
            throw new Exception\RuntimeException(sprintf(
                'AlphaMail does not support to send messages directly. It needs a template hosted on ' .
                'AlphaMail servers. Hence, you need to pass an AlphaMailMessage instance ("%s" given)',
                get_class($message)
            ));
        }

        $from = $message->getFrom();
        $to   = $message->getTo();
        if (count($from) !== 1 || count($to) !== 1) {
            throw new Exception\RuntimeException(
                'AlphaMail API requires exactly one from sender and one to receiver'
            );
        }

        $parameters = array(
            'project_id'     => $message->getProject(),
            'sender_name'    => $from->rewind()->getName() ?: $from->rewind()->getEmail(),
            'sender_email'   => $from->rewind()->getEmail(),
            'receiver_name'  => $to->rewind()->getName() ?: $to->rewind()->getEmail(),
            'receiver_email' => $to->rewind()->getEmail(),
            'body'           => json_encode($message->getVariables())
        );

        $response = $this->prepareHttpClient('/email/queue')
                         ->setMethod(HttpRequest::METHOD_POST)
                         ->setRawBody(json_encode($this->filterParameters($parameters)))
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * Get status about a sent message
     *
     * @link   http://app.amail.io/#/docs/api/
     * @param  int $messageId
     * @return array
     */
    public function getEmailStatus($messageId)
    {
        $response = $this->prepareHttpClient('/email/queue/' . $messageId . '/status')
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * ------------------------------------------------------------------------------------------
     * PROJECTS
     * ------------------------------------------------------------------------------------------
     */

    /**
     * Create a new project
     *
     * @link   http://app.amail.io/#/docs/api/
     * @param  string   $name
     * @param  string   $subject
     * @param  int      $templateId
     * @param  int      $signatureId
     * @return array
     */
    public function createProject($name, $subject, $templateId, $signatureId = 0)
    {
        $parameters = array(
            'name'         => $name,
            'subject'      => $subject,
            'template_id'  => (int) $templateId,
            'signature_id' => (int) $signatureId
        );

        $response = $this->prepareHttpClient('/projects')
                         ->setMethod(HttpRequest::METHOD_POST)
                         ->setRawBody(json_encode($this->filterParameters($parameters)))
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * Delete a single project
     *
     * @link   http://app.amail.io/#/docs/api/
     * @param  int $id
     * @return void
     */
    public function deleteProjects($id)
    {
        $response = $this->prepareHttpClient('/projects/' . (int)$id)
                         ->setMethod(HttpRequest::METHOD_DELETE)
                         ->send();

        $this->parseResponse($response);
    }

    /**
     * Retrieve all projects
     *
     * @link   http://app.amail.io/#/docs/api/
     * @return array
     */
    public function getProjects()
    {
        $response = $this->prepareHttpClient('/projects')
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * Retrieve details for a single project
     *
     * @link   http://app.amail.io/#/docs/api/
     * @param  int $id
     * @return array
     */
    public function getProject($id)
    {
        $response = $this->prepareHttpClient('/projects/' . (int)$id)
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * ------------------------------------------------------------------------------------------
     * TEMPLATES
     * ------------------------------------------------------------------------------------------
     */

    /**
     * Create a new template to use with projects
     *
     * @link   http://app.amail.io/#/docs/api/
     * @param  string $name
     * @param  string $text
     * @param  string $html
     * @return array
     */
    public function createTemplate($name, $text = '', $html = '')
    {
        $parameters = array(
            'name'    => $name,
            'content' => array(
                'text' => $text,
                'html' => $html
            )
        );

        $response = $this->prepareHttpClient('/templates')
                         ->setMethod(HttpRequest::METHOD_POST)
                         ->setRawBody(json_encode($this->filterParameters($parameters)))
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * Delete a single template
     *
     * @link   http://app.amail.io/#/docs/api/
     * @param  int $id
     * @return void
     */
    public function deleteTemplate($id)
    {
        $response = $this->prepareHttpClient('/templates/' . (int)$id)
                         ->setMethod(HttpRequest::METHOD_DELETE)
                         ->send();

        $this->parseResponse($response);
    }

    /**
     * Retrieve all the templates
     *
     * @link   http://app.amail.io/#/docs/api/
     * @return array
     */
    public function getTemplates()
    {
        $response = $this->prepareHttpClient('/templates')
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * Retrieve details for a single template
     *
     * @link   http://app.amail.io/#/docs/api/
     * @param  int $id
     * @return array
     */
    public function getTemplate($id)
    {
        $response = $this->prepareHttpClient('/templates/' . (int)$id)
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * ------------------------------------------------------------------------------------------
     * SIGNATURES
     * ------------------------------------------------------------------------------------------
     */

    /**
     * Create a new signature to use with your mails
     *
     * @link   http://app.amail.io/#/docs/api/
     * @param  string $name
     * @param  string $domain
     * @return array
     */
    public function createSignature($name, $domain)
    {
        $parameters = array(
            'name'       => $name,
            'dns_record' => array(
                'domain' => $domain
            )
        );

        $response = $this->prepareHttpClient('/signatures')
                         ->setMethod(HttpRequest::METHOD_POST)
                         ->setRawBody(json_encode($this->filterParameters($parameters)))
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * Delete a single signature
     *
     * @link   http://app.amail.io/#/docs/api/
     * @param  int $id
     * @return void
     */
    public function deleteSignature($id)
    {
        $response = $this->prepareHttpClient('/signatures/' . (int)$id)
                         ->setMethod(HttpRequest::METHOD_DELETE)
                         ->send();

        $this->parseResponse($response);
    }

    /**
     * Retrieve all the signatures
     *
     * @link   http://app.amail.io/#/docs/api/
     * @return array
     */
    public function getSignatures()
    {
        $response = $this->prepareHttpClient('/signatures')
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * Retrieve details for a single signature
     *
     * @link   http://app.amail.io/#/docs/api/
     * @param  int $id
     * @return array
     */
    public function getSignature($id)
    {
        $response = $this->prepareHttpClient('/signatures/' . (int)$id)
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * ------------------------------------------------------------------------------------------
     * TOKENS
     * ------------------------------------------------------------------------------------------
     */

    /**
     * Retrieve all the tokens (they determine API access to your AlphaMail account)
     *
     * @link  http://app.amail.io/#/docs/api
     * @return array
     */
    public function getTokens()
    {
        $response = $this->prepareHttpClient('/tokens')
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * @param  string $uri
     * @param  array $parameters
     * @return \Zend\Http\Client
     */
    private function prepareHttpClient($uri, array $parameters = array())
    {
        $client = $this->getClient()->resetParameters();
        $client->getRequest()
               ->getHeaders()
               ->addHeaderLine('Authorization', 'Basic ' . base64_encode(':' . $this->apiKey));

         $client->setMethod(HttpRequest::METHOD_GET)
                ->setUri(self::API_ENDPOINT . $uri)
                ->setParameterGet($this->filterParameters($parameters));

        return $client;
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
            return isset($result['result']) ? $result['result'] : array();
        }

        switch ($response->getStatusCode()) {
            case 401:
            case 403:
                throw new Exception\InvalidCredentialsException(sprintf(
                    'Authentication error: missing or incorrect AlphaMail API key: %s', $result['message']
                ));
            case 400:
            case 405:
                throw new Exception\ValidationErrorException(sprintf(
                    'Validation error on AlphaMail (code %s): %s', $result['error_code'], $result['message']
                ));
            default:
                throw new Exception\RuntimeException(sprintf(
                    'Unknown error on AlphaMail (code %s): %s', $result['error_code'], $result['message']
                ));
        }
    }
}
