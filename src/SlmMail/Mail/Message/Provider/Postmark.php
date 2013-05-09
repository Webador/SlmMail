<?php

namespace SlmMail\Mail\Message\Provider;

use SlmMail\Mail\Message\ProvidesTags;
use Zend\Mail\Message;

/**
 * Note that Postmark supports only 1 tag per message. If you set multiple tags through the setTags trait, only
 * the first one will be sent to Postmark
 */
class Postmark extends Message
{
    use ProvidesTags;
}
