<?php

declare(strict_types=1);

namespace SlmMailTest\Service;

use Laminas\Mail\Message;
use Laminas\Mime\Message as MimeMessage;
use Laminas\Mime\Mime;
use Laminas\Mime\Part;
use SlmMail\Service\AbstractMailService;
use PHPUnit\Framework\TestCase;

/**
 * @covers \SlmMail\Service\AbstractMailService
 */
final class AbstractMailServiceTest extends TestCase
{
    private AbstractMailService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new class () extends AbstractMailService {
            public ?string $text = null;
            public ?string $html = null;
            public array $attachments = [];

            public function send(Message $message)
            {
                $this->text = $this->extractText($message);
                $this->html = $this->extractHtml($message);
                $this->attachments = $this->extractAttachments($message);
            }
        };
    }

    public function testExtractTextFromStringBodyReturnsString(): void
    {
        $expected = 'Foo';
        $message = new Message();
        $message->setBody($expected);

        $this->service->send($message);
        self::assertSame($expected, $this->service->text);
    }

    public function testExtractTextFromEmptyBodyReturnsNull(): void
    {
        $message = new Message();

        $this->service->send($message);
        self::assertNull($this->service->text);
    }

    public function testExtractTextFromTwoPartMessageReturnsString(): void
    {
        $expected = 'Foo';
        $message = new Message();
        $body = new MimeMessage();
        $body->addPart(new Part(''));
        $body->addPart(
            (new Part($expected))
                ->setType(Mime::TYPE_TEXT)
        );
        $message->setBody($body);

        $this->service->send($message);
        self::assertSame($expected, $this->service->text);
    }

    public function testExtractTextFromTextAttachmentReturnsNull(): void
    {
        $message = new Message();
        $body = new MimeMessage();
        $body->addPart(
            (new Part('Foo'))
                ->setType(Mime::TYPE_TEXT)
                ->setDisposition(Mime::DISPOSITION_ATTACHMENT)
        );
        $message->setBody($body);

        $this->service->send($message);
        self::assertNull($this->service->text);
    }

    public function testExtractTextFromMultipartMessageReturnsString(): void
    {
        $expected = 'Foo';
        $message = new Message();
        $body = new MimeMessage();
        $contentPart = new MimeMessage();
        $contentPart->addPart(new Part());
        $contentPart->addPart(
            (new Part($expected))
                ->setType(Mime::TYPE_TEXT)
        );
        $body->addPart(
            (new Part($contentPart->generateMessage()))
                ->setType(Mime::MULTIPART_ALTERNATIVE)
                ->setBoundary($contentPart->getMime()->boundary())
        );
        $message->setBody($body);

        $this->service->send($message);
        self::assertSame($expected, trim($this->service->text));
    }

    public function testExtractHtmlFromStringBodyReturnsNull(): void
    {
        $message = new Message();
        $message->setBody('Foo');

        $this->service->send($message);
        self::assertNull($this->service->html);
    }

    public function testExtractHtmlFromEmptyBodyReturnsNull(): void
    {
        $message = new Message();

        $this->service->send($message);
        self::assertNull($this->service->html);
    }

    public function testExtractHtmlFromTwoPartMessageReturnsString(): void
    {
        $expected = 'Foo';
        $message = new Message();
        $body = new MimeMessage();
        $body->addPart(new Part(''));
        $body->addPart(
            (new Part($expected))
                ->setType(Mime::TYPE_HTML)
        );
        $message->setBody($body);

        $this->service->send($message);
        self::assertSame($expected, $this->service->html);
    }

    public function testExtractHtmlFromHtmlAttachmentReturnsNull(): void
    {
        $message = new Message();
        $body = new MimeMessage();
        $body->addPart(
            (new Part('Foo'))
                ->setType(Mime::TYPE_HTML)
                ->setDisposition(Mime::DISPOSITION_ATTACHMENT)
        );
        $message->setBody($body);

        $this->service->send($message);
        self::assertNull($this->service->html);
    }

    public function testExtractHtmlFromMultipartMessageReturnsString(): void
    {
        $expected = 'Foo';
        $message = new Message();
        $body = new MimeMessage();
        $contentPart = new MimeMessage();
        $contentPart->addPart(new Part());
        $contentPart->addPart(
            (new Part($expected))
                ->setType(Mime::TYPE_HTML)
        );
        $body->addPart(
            (new Part($contentPart->generateMessage()))
                ->setType(Mime::MULTIPART_ALTERNATIVE)
                ->setBoundary($contentPart->getMime()->boundary())
        );
        $message->setBody($body);

        $this->service->send($message);
        self::assertSame($expected, trim($this->service->html));
    }
}
