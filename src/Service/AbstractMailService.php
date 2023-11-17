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

use Laminas\Http\Client as HttpClient;
use Laminas\Mail\Message;
use Laminas\Mime\Message as MimeMessage;
use Laminas\Mime\Mime;
use Laminas\Mime\Part;

use function in_array;
use function strpos;

/**
 * Class AbstractMailService
 */
abstract class AbstractMailService implements MailServiceInterface
{
    private const MULTIPART_TYPES = [
        Mime::MULTIPART_ALTERNATIVE,
        Mime::MULTIPART_MIXED,
        Mime::MULTIPART_RELATED,
        Mime::MULTIPART_RELATIVE,
        Mime::MULTIPART_REPORT,
    ];

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
    protected function extractText(Message $message): ?string
    {
        $body = $message->getBody();

        if (is_string($body)) {
            return $body;
        }

        if (!$body instanceof MimeMessage) {
            return null;
        }

        return $this->extractTextFromMimeMessage($body);
    }

    private function extractTextFromMimeMessage(MimeMessage $message): ?string
    {
        foreach ($message->getParts() as $part) {
            if ($this->isType($part, Mime::TYPE_TEXT)) {
                return $part->getContent();
            }
        }
        foreach ($message->getParts() as $part) {
            if (in_array($part->type, self::MULTIPART_TYPES)) {
                return $this->extractTextFromMimeMessage(
                    MimeMessage::createFromMessage($part->getContent(), $part->boundary)
                );
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
    protected function extractHtml(Message $message): ?string
    {
        $body = $message->getBody();

        // If body is not a MimeMessage object, then the body is just the text version
        if (is_string($body) || !$body instanceof MimeMessage) {
            return null;
        }

        return $this->extractHtmlFromMimeMessage($body);
    }

    private function extractHtmlFromMimeMessage(MimeMessage $message): ?string
    {
        foreach ($message->getParts() as $part) {
            if ($this->isType($part, Mime::TYPE_HTML)) {
                return $part->getContent();
            }
        }
        foreach ($message->getParts() as $part) {
            if (in_array($part->type, self::MULTIPART_TYPES)) {
                return $this->extractHtmlFromMimeMessage(
                    MimeMessage::createFromMessage($part->getContent(), $part->boundary)
                );
            }
        }

        return null;
    }

    private function isType(Part $part, string $mimeType): bool
    {
        return strpos($part->type, $mimeType) === 0 && $part->disposition !== Mime::DISPOSITION_ATTACHMENT;
    }

    /**
     * Extract all attachments from a message
     *
     * Attachments are detected in the Mime message where
     * the type of the mime part is not text/plain or
     * text/html.
     *
     * @param  Message $message
     * @return \Laminas\Mime\Part[]
     */
    protected function extractAttachments(Message $message): array
    {
        $body = $message->getBody();

        // If body is not a MimeMessage object, then the body is just the text version
        if (is_string($body) || !$body instanceof MimeMessage) {
            return [];
        }

        $attachments = [];
        foreach ($body->getParts() as $part) {
            if ($this->isAttachment($part)) {
                $attachments[] = $part;
            }
        }

        return $attachments;
    }

    private function isAttachment(Part $part): bool
    {
        if (in_array($part->type, self::MULTIPART_TYPES)) {
            return false;
        }
        if ($part->disposition === Mime::DISPOSITION_ATTACHMENT) {
            return true;
        }
        return strpos($part->type, 'text/') !== 0;
    }

    /**
     * Get HTTP client
     *
     * @return HttpClient
     */
    protected function getClient(): HttpClient
    {
        if (null === $this->client) {
            $this->setClient(new HttpClient());
        }

        return $this->client;
    }

    /**
     * Set HTTP client
     *
     * @param HttpClient $client
     * @return void
     */
    public function setClient(HttpClient $client): void
    {
        $this->client = $client;
    }

    /**
     * Filter parameters recursively (for now, only null parameters and empty strings)
     *
     * @param  array $parameters
     * @return array
     */
    protected function filterParameters(array $parameters): array
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
