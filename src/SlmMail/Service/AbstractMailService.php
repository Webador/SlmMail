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

use Zend\Http\Client as HttpClient;
use Zend\Mail\Message;
use Zend\Mime\Message as MimeMessage;
use Zend\Mime\Mime;

/**
 * Class AbstractMailService
 */
abstract class AbstractMailService implements MailServiceInterface
{
    /**
     * @var HttpClient
     */
    protected $client;

    /**
     * Extract text part from a message
     *
     * @param  Message $message
     * @return string|null
     */
    protected function extractText(Message $message)
    {
        $body = $message->getBody();

        if (is_string($body)) {
            return $body;
        }

        if (!$body instanceof MimeMessage) {
            return null;
        }

        foreach ($body->getParts() as $part) {
            if ($part->type === 'text/plain' && $part->disposition !== Mime::DISPOSITION_ATTACHMENT) {
                return $part->getContent();
            }
        }

        return null;
    }

    /**
     * Extract a HTML part from a message
     *
     * @param  Message $message
     * @return string|null
     */
    protected function extractHtml(Message $message)
    {
        $body = $message->getBody();

        // If body is not a MimeMessage object, then the body is just the text version
        if (is_string($body) || !$body instanceof MimeMessage) {
            return null;
        }

        foreach ($body->getParts() as $part) {
            if ($part->type === 'text/html' && $part->disposition !== Mime::DISPOSITION_ATTACHMENT) {
                return $part->getContent();
            }
        }

        return null;
    }

    /**
     * Extract all attachments from a message
     *
     * Attachments are detected in the Mime message where
     * the type of the mime part is not text/plain or
     * text/html.
     *
     * @param  Message $message
     * @return \Zend\Mime\Part[]|array
     */
    protected function extractAttachments(Message $message)
    {
        $body = $message->getBody();

        // If body is not a MimeMessage object, then the body is just the text version
        if (is_string($body) || !$body instanceof MimeMessage) {
            return array();
        }

        $filter      = array('text/plain', 'text/html');
        $attachments = array();
        foreach ($body->getParts() as $part) {
            if (!in_array($part->type, $filter) || $part->disposition === Mime::DISPOSITION_ATTACHMENT) {
                $attachments[] = $part;
            }
        }

        return $attachments;
    }

    /**
     * Get HTTP client
     *
     * @return HttpClient
     */
    protected function getClient()
    {
        if (null === $this->client) {
            $this->setClient(new HttpClient);
        }

        return $this->client;
    }

    /**
     * Set HTTP client
     *
     * @param HttpClient $client
     * @return void
     */
    public function setClient(HttpClient $client)
    {
        $this->client = $client;
    }

    /**
     * Filter parameters recursively (for now, only null parameters and empty strings)
     *
     * @param  array $parameters
     * @return array
     */
    protected function filterParameters(array $parameters)
    {
        foreach ($parameters as &$value) {
            if (is_array($value)) {
                $value = $this->filterParameters($value);
            }
        }

        return array_filter($parameters, function ($value) {
            return $value !== null && $value !== '';
        });
    }
}
