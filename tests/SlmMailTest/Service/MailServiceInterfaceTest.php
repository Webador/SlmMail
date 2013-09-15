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

namespace SlmMailTest\Service;

use PHPUnit_Framework_TestCase;
use ReflectionMethod;
use SlmMailTest\Asset\SimpleMailService;
use Zend\Mail\Message;
use Zend\Mime\Message as MimeMessage;
use Zend\Mime\Mime;
use Zend\Mime\Part as MimePart;

class MailServiceInterfaceTest extends PHPUnit_Framework_testCase
{
    /**
     * @var SimpleMailService
     */
    protected $simpleMailService;

    public function setUp()
    {
        $this->simpleMailService = new SimpleMailService();
    }

    public function testCanExtractText()
    {
        // Make the extractText method accessible
        $reflMethod = new ReflectionMethod($this->simpleMailService, 'extractText');
        $reflMethod->setAccessible(true);

        // First: using the body
        $message = new Message();
        $message->setBody('An interesting body');

        $result = $reflMethod->invoke($this->simpleMailService, $message);
        $this->assertEquals('An interesting body', $result);

        // Second: using a multipart without text part
        $htmlPart = new MimePart('<html><body><h1>Hello world</h1></body></html>');
        $htmlPart->type = 'text/html';

        $body = new MimeMessage();
        $body->setParts(array($htmlPart));

        $message->setBody($body);
        $this->assertNull($reflMethod->invoke($this->simpleMailService, $message));

        // Third: using a multipart with text part
        $textPart = new MimePart('An interesting body');
        $textPart->type = 'text/plain';

        $body->addPart($textPart);

        $result = $reflMethod->invoke($this->simpleMailService, $message);
        $this->assertEquals('An interesting body', $result);
    }

    public function testTextCanBeSetAsAttachment()
    {
        // Make the extractText method accessible
        $reflMethod = new ReflectionMethod($this->simpleMailService, 'extractText');
        $reflMethod->setAccessible(true);

        $message = new Message();

        $textPart              = new MimePart('Plain text as attachment');
        $textPart->type        = 'text/plain';
        $textPart->disposition = Mime::DISPOSITION_ATTACHMENT;

        $body = new MimeMessage();
        $body->setParts(array($textPart));

        $message->setBody($body);

        $this->assertNull($reflMethod->invoke($this->simpleMailService, $message));

        $reflMethod = new ReflectionMethod($this->simpleMailService, 'extractAttachments');
        $reflMethod->setAccessible(true);

        $result   = $reflMethod->invoke($this->simpleMailService, $message);
        $mimePart = $result[0];

        $this->assertCount(1, $result);
        $this->assertEquals('text/plain', $mimePart->type);
    }

    public function testCanExtractHtml()
    {
        // Make the extractHtml method accessible
        $reflMethod = new ReflectionMethod($this->simpleMailService, 'extractHtml');
        $reflMethod->setAccessible(true);

        $htmlPart = new MimePart('<html><body><h1>Hello world</h1></body></html>');
        $htmlPart->type = 'text/html';

        $body = new MimeMessage();
        $body->setParts(array($htmlPart));

        $message = new Message();
        $message->setBody($body);

        $result = $reflMethod->invoke($this->simpleMailService, $message);
        $this->assertEquals('<html><body><h1>Hello world</h1></body></html>', $result);
    }
}
