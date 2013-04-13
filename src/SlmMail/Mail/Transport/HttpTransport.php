<?php

namespace SlmMail\Mail\Transport;

use SlmMail\Service\AbstractMailService;
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
    public function __construct(AbstractMailService $service)
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
