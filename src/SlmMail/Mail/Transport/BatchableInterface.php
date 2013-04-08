<?php

namespace SlmMail\Mail\Transport;

/**
 * Transport that allow to send multiple messages at once can implement this interface
 */
interface BatchableInterface
{
    /**
     * Send batch messages
     *
     * @param  \Zend\Mail\Message[]|array $messages
     * @return mixed
     */
    public function sendBatch(array $messages);
}
