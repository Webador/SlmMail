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

use SlmMail\Mail\Message\Postage as PostageMessage;
use Zend\Http\Request as HttpRequest;
use Zend\Http\Response as HttpResponse;
use Zend\Mail\Address;
use Zend\Mail\Message;

class PostageService extends AbstractMailService
{
    /**
     * API endpoint
     */
    const API_ENDPOINT = 'https://api.postageapp.com/v.1.0';

    /**
     * Postage API key
     *
     * @var string
     */
    protected $apiKey;

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
     * @link http://help.postageapp.com/kb/api/send_message
     * @return array The id and UID of the sent message (if sent correctly)
     */
    public function send(Message $message)
    {
        $from = $message->getFrom();
        if (count($from) !== 1) {
            throw new Exception\RuntimeException(
                'Postage API requires exactly one from sender'
            );
        }

        if (count($message->getCc())) {
            throw new Exception\RuntimeException('Postage does not support CC addresses');
        }

        if (count($message->getBcc())) {
            throw new Exception\RuntimeException('Postage does not support BCC addresses');
        }

        $parameters = array(
            'uid'       => sha1(json_encode(uniqid())),
            'arguments' => array(
                'headers' => array(
                    'subject' => $message->getSubject(),
                    'from'    => $from->rewind()->toString()
                ),
                'content' => array(
                    'text/plain' => $this->extractText($message),
                    'text/html'  => $this->extractHtml($message)
                )
            )
        );

        $to = array();
        foreach ($message->getTo() as $address) {
            $to[] = $address->toString();
        }

        $parameters['arguments']['recipients'] = implode(',', $to);

        $replyTo = $message->getReplyTo();
        if (count($replyTo) > 1) {
            throw new Exception\RuntimeException('Postage has only support for one Reply-To address');
        } elseif (count($replyTo)) {
            $parameters['headers']['reply-to'] = $replyTo->rewind()->toString();
        }

        if ($message instanceof PostageMessage) {
            if ($message->getTemplate() !== '') {
                $parameters['arguments']['template']  = $message->getTemplate();
                $parameters['arguments']['variables'] = $message->getVariables();
            }
        }

        $attachments = $this->extractAttachments($message);
        foreach ($attachments as $attachment) {
            $parameters['arguments']['attachments'][$attachment->filename] = array(
                'content_type' => $attachment->type,
                'content'      => base64_encode($attachment->getRawContent())
            );
        }

        $response =  $this->prepareHttpClient('/send_message.json', $parameters)
                          ->send();

        $data = $this->parseResponse($response);

        return array(
            'uid' => $parameters['uid'],
            'id'  => $data['message']['id']
        );
    }

    /**
     * Get receipt of message by its UID
     *
     * The Postage apps lets verify message if they are known in the project. This is done with the UID. When the
     * message is known, it returns its message id (independant from the UID). If not, an exception is thrown
     * because of an invalid message UID.
     *
     * @link   http://help.postageapp.com/kb/api/get_message_receipt
     * @param  string $uid
     * @return array Id and url of message
     */
    public function getMessageReceipt($uid)
    {
        $response = $this->prepareHttpClient('/get_message_receipt.json', array('uid' => $uid))
                         ->send();

        $result = $this->parseResponse($response);
        return $result['message'];
    }

    /**
     * Get data on individual recipients' delivery and open status
     *
     * @link http://help.postageapp.com/kb/api/get_message_transmissions
     * @param  string $uid
     * @return array
     */
    public function getMessageTransmissions($uid)
    {
        $response = $this->prepareHttpClient('/get_message_transmissions.json', array('uid' => $uid))
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * Get metrics for a project
     *
     * @link http://help.postageapp.com/kb/api/get_metrics
     * @return array
     */
    public function getMetrics()
    {
        $response = $this->prepareHttpClient('/get_metrics.json')
                         ->send();

        $result = $this->parseResponse($response);
        return $result['metrics'];
    }

    /**
     * Get a list of all API methods
     *
     * @link http://help.postageapp.com/kb/api/get_method_list
     * @return array
     */
    public function getMethodList()
    {
        $response = $this->prepareHttpClient('/get_method_list.json')
                         ->send();

        $result = $this->parseResponse($response);

        return explode(', ', $result['methods']);
    }

    /**
     * Get the information about the connected account
     *
     * @link http://help.postageapp.com/kb/api/get_account_info
     * @return array
     */
    public function getAccountInfo()
    {
        $response = $this->prepareHttpClient('/get_account_info.json')
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * Get the information about the project
     *
     * @link http://help.postageapp.com/kb/api/get_project_info
     * @return array
     */
    public function getProjectInfo()
    {
        $response = $this->prepareHttpClient('/get_project_info.json')
                         ->send();

        return $this->parseResponse($response);
    }

    /**
     * Get a list of all message UIDs within your project, for subsequent use in collection statistics or open rates
     * for example
     *
     * @link http://help.postageapp.com/kb/api/get_messages
     * @return array
     */
    public function getMessageUids()
    {
        $response = $this->prepareHttpClient('/get_messages.json')
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
        $parameters = array_merge(array('api_key' => $this->apiKey), $parameters);

        $client = $this->getClient()->resetParameters();
        $client->getRequest()
               ->getHeaders()
               ->addHeaderLine('Content-Type', 'application/json');

        return $client->setMethod(HttpRequest::METHOD_POST)
                      ->setUri(self::API_ENDPOINT . $uri)
                      ->setRawBody(json_encode($this->filterParameters($parameters)));
    }

    /**
     * @param  HttpResponse $response
     * @throws Exception\RuntimeException if an error occurred on Postage side
     * @return array
     */
    private function parseResponse(HttpResponse $response)
    {
        $result = json_decode($response->getBody(), true);

        if ($response->isSuccess()) {
            return isset($result['data']) ? $result['data'] : array();
        }

        if ($result['response']['status'] !== 'ok') {
            $errors = false;
            if (isset($result['data']) && isset($result['data']['errors'])) {
                $errors = implode(', ', $result['data']['errors']);
            }

            if (isset($result['response']['message'])) {
                throw new Exception\RuntimeException(sprintf(
                    'An error occurred on Postage, message: %s%s', $result['response']['message'],
                    ($errors) ? ' (' . $errors . ')' : ''
                ));
            } else {
                throw new Exception\RuntimeException(sprintf(
                    'An error occurred on Postage, status code: %s%s', $result['response']['status'],
                    ($errors) ? ' (' . $errors . ')' : ''
                ), (int) $result['response']['status']);
            }
        }

        // We need to return an array and not throw an exception because of the poor Postage API
        // error handling, it may returns an empty array with just status === 'ok'
        return array();
    }
}
