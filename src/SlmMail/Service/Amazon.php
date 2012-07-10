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
    Zend\Mail\Exception\RuntimeException;

abstract class Amazon
{
    protected $client;

    abstract public function sendEmail (Message $message);

    public function verifyEmailAddress ($email)
    {
        $params   = array('email' => $email);
        $response = $this->prepareHttpClient('VerifyEmailAddress', $params)
                         ->send();

        return $this->parseReponse($response);
    }

    public function listVerifiedEmailAddresses ()
    {
        $response = $this->prepareHttpClient('ListVerifiedEmailAddresses')
                         ->send();

        return $this->parseReponse($response);
    }

    public function deleteVerifiedEmailAddresses ($email)
    {
        $params   = array('email' => $email);
        $response = $this->prepareHttpClient('DeleteVerifiedEmailAddress', $params)
                         ->send();

        return $this->parseReponse($response);
    }

    public function getSendQuota ()
    {
        $response = $this->prepareHttpClient('GetSendQuota')
                         ->send();

        return $this->parseReponse($response);
    }

    public function getSendStatistics ()
    {
        $response = $this->prepareHttpClient('GetSendStatistics')
                         ->send();

        return $this->parseReponse($response);
    }

    /**
     * Http client getter
     *
     * @return Client
     */
    public function getHttpClient ()
    {
        if (null === $this->client) {
            $this->client = new Client;
        }

        return $this->client;
    }

    /**
     * Http client setter
     *
     * @param Client $client
     */
    public function setHttpClient (Client $client)
    {
        $this->client = $client;
    }

    abstract protected function prepareHttpClient ($path, array $data = array());
    abstract protected function parseResponse (Response $response);

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
}