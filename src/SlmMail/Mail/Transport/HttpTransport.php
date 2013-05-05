<?php

namespace SlmMail\Mail\Transport;

use SlmMail\Service\MailServiceInterface;
use Zend\Mail\Transport\TransportInterface;
use Zend\Mail\Message;

class HttpTransport implements TransportInterface
{
    /**
     * @var AbstractMailService
     */
    protected $service;

    /**
     * @param AbstractMailService $service
     */
    public function __construct(MailServiceInterface $service)
    {
        $this->service = $service;
    }

    /**
     * {@inheritDoc}
     */
    public function send(Message $message)
    {
        $this->service->send($message);
    }
}
