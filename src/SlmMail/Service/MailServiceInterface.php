<?php

namespace SlmMail\Service;

use Zend\Mail\Message;

interface MailServiceInterface
{
    /**
     * Send a message
     *
     * @param  Message $message
     * @return mixed
     */
    public function send(Message $message);
}
