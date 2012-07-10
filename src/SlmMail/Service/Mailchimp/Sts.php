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
namespace SlmMail\Service\Mailchimp;

use Zend\Mail\Message,
    Zend\Http\Request,
    Zend\Http\Response,
    Zend\Mail\Exception\RuntimeException,
    SlmMail\Service\Amazon,
    SlmMail\Mail\Message\Mailchimp as MailchimpMessage;

class Sts extends Amazon
{
    const API_URI = 'http://%s.sts.mailchimp.com/1.0/';

    protected $apiKey;
    protected $client;
    protected $host;

    public function __construct ($api_key)
    {
        $this->apiKey = $api_key;
    }

    public function sendEmail (Message $message)
    {
        $params = array(
            'message' => array(
                'subject' => $message->getSubject(),
                'html'    => $message->getBody(),
                'text'    => $message->getBodyText(),
            )
        );

        foreach ($message->getTo() as $address) {
            $params['message']['to_email'][] = $address->getEmail();
            $params['message']['to_name'][]  = $address->getName();
        }
        foreach ($message->getCc() as $address) {
            $params['message']['cc_email'][] = $address->getEmail();
            $params['message']['cc_name'][]  = $address->getName();
        }
        foreach ($message->getBcc() as $address) {
            $params['message']['bcc_email'][] = $address->getEmail();
            $params['message']['bcc_name'][]  = $address->getName();
        }


        $from = $message->getFrom();
        if (1 !== count($from)) {
            throw new RuntimeException('Mailchimp requires exactly one from address');
        }
        $from->rewind();
        $from = $from->current();
        $data['message']['from_email']      = $from->getEmail();
        $data['message']['from_name'] = $from->getName();

        foreach ($message->getReplyTo() as $address) {
            $params['message']['reply_to'][] = $address->getEmail();
        }

        if ($message instanceof MailchimpMessage) {
            if (null !== ($flag = $message->getTrackClicks())) {
                $params['track_clicks'] = $flag;
            }

            if (null !== ($flag = $message->getTrackOpens())) {
                $params['track_opens'] = $flag;
            }

            if (null !== ($tags = $message->getTags())) {
                $params['tags'] = $tags;
            }
        }

        $response = $this->prepareHttpClient('SendEmail', $params)
                         ->send();

        return $this->parseResponse($response);
    }

    public function getBounces ($since = null)
    {
        $params = array();
        if (null !== $since) {
            $params['since'] = $since;
        }

        $response = $this->prepareHttpClient('GetBounces', $params)
                         ->send();

        return $this->parseResponse($response);
    }

    public function getSendStats ($tag_id = null, $since = null)
    {
        $params = compact($tag_id, $since);
        $params = $this->filterNullParams($params);

        $response = $this->prepareHttpClient('GetSendStats', $params)
                         ->send();

        return $this->parseResponse($response);
    }

    public function getTags ()
    {
        $response = $this->prepareHttpClient('GetTags')
                         ->send();

        return $this->parseReponse($response);
    }

    public function getUrlStats ($url_id = null, $since = null)
    {
        $params = compact($url_id, $since);
        $params = $this->filterNullParams($params);

        $response = $this->prepareHttpClient('GetUrlStats', $params)
                         ->send();

        return $this->parseResponse($response);
    }

    public function getUrls ()
    {
        $response = $this->prepareHttpClient('GetUrls')
                         ->send();

        return $this->parseReponse($response);
    }

    protected function prepareHttpClient ($path, array $data = array())
    {
        $data = $data + array('apikey' => $this->apiKey);

        return $this->getHttpClient()
                    ->setMethod(Request::METHOD_POST)
                    ->setUri($this->getHost() . $path . '.php')
                    ->setParameterGet($data);
    }

    protected function parseResponse (Response $response)
    {
        $body = unserialize($response->getBody());

        if (!$response->isOk()) {
            switch ($response->getStatusCode()) {
                case 500:
                    throw new RuntimeException(sprintf(
                            'Could not send request: Mailchimp server error (%s)',
                            $body['message']));
                    break;
                default:
                    throw new RuntimeException('Unknown error during request to Mailchimp server');
            }
        }

        return $body;
    }

    protected function getHost ()
    {
        if (null === $this->host) {
            $this->host = sprintf(self::API_URI, substr($this->apiKey, strpos($this->apiKey, '-') + 1));
        }

        return $this->host;
    }
}