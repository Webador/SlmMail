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

use DateTime,
    Zend\Mail\Message,
    Zend\Http\Client,
    Zend\Http\Request,
    Zend\Http\Response,
    Zend\Mail\Exception\RuntimeException;

class AmazonSes extends Amazon
{
    protected $host;
    protected $accessKey;
    protected $secretKey;
    protected $client;

    public function __construct ($host, $access_key, $secret_key)
    {
        $this->host      = $host;
        $this->accessKey = $access_key;
        $this->secretKey = $secret_key;
    }

    /**
     * Composes an email message and immediately queues for sending
     *
     * @link http://docs.amazonwebservices.com/ses/latest/APIReference/API_SendEmail.html
     * @param Message $message
     * @return type
     */
    public function sendEmail (Message $message)
    {
        $params = array(
            'Message.Subject.Data'   => $message->getSubject(),
            'Message.Body.Html.Data' => $message->getBody(),
            'Message.Body.Text.Data' => $message->getBodyText(),
        );

        $i = 1;
        foreach ($message->to() as $address) {
            $params['Destination.ToAddresses.member.' . $i] = $address->getEmail();
            $i++;
        }
        $i = 1;
        foreach ($message->cc() as $address) {
            $params['Destination.CcAddresses.member.' . $i] = $address->getEmail();
            $i++;
        }
        $i = 1;
        foreach ($message->bcc() as $address) {
            $params['Destination.BccAddresses.member.' . $i] = $address->getEmail();
            $i++;
        }

        $from = $message->from();
        if (1 !== count($from)) {
            throw new RuntimeException('Amazon SES requires exactly one from address');
        }
        $from->rewind();
        $from = $from->current();
        $params['Source'] = $from->getEmail();

        $i = 1;
        foreach ($message->replyTo() as $address) {
            $params['ReplyToAddresses.member.' . $i] = $address->getEmail();
            $i++;
        }

        /**
         * @todo Set return path
         *
         * <code>
         * $params['ReturnPath'] = $address->getEmail();
         * </code>
         */

        $response = $this->prepareHttpClient('SendEmail', $params)
                         ->send();

        return $this->parseResponse($response);
    }

    protected function prepareHttpClient ($action, array $data = array())
    {
        // SES has another naming convention, correct email into EmaiLAddress
        if (isset($data['email'])) {
            $data['EmailAddress'] = $data['email'];
            unset($data['email']);
        }
        $data   = $data + array('Action' => $action);
        $data   = array_map(function ($value) {
            str_replace('%7E', '~', rawurlencode($value));
        }, $data);
        sort($data, SORT_STRING);

        $client = $this->getHttpClient()
                       ->setMethod(Request::METHOD_POST)
                       ->setParameterPost($data)
                       ->setUri($this->host);

        $date = new DateTime;
        $date = $date->format('r');

        $auth = 'AWS3-HTTPS AWSAccessKeyId=' . $this->accessKey
              . ',Algorithm=HmacSHA256,Signature=' . $this->sign($date)
              . ',SignedHeaders=Date';

        $client->getRequest()->headers()
               ->addHeaderLine('Content-Type', 'application/x-www-form-urlencoded')
               ->addHeaderLine('Date', $date)
               ->addHeaderLine('X-Amzn-Authorization', $auth);

        return $client;
    }

    protected function sign ($content)
    {
        return base64_encode(hash_hmac('sha256', $content, $this->secretKey, true));
    }

    protected function parseResponse (Response $response)
    {
        var_dump($response);exit;
    }
}