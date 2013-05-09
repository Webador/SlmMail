<?php

namespace SlmMail\Mail\Transport;

use SlmMail\Service\MailServiceInterface;
use Zend\Mail\Transport\TransportInterface;
use Zend\Mail\Message;

class HttpTransport implements TransportInterface
{
    /**
     * @var MailServiceInterface
     */
    protected $service;

    /**
     * @param MailServiceInterface $service
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
