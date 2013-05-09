<?php

namespace SlmMail\Mail\Message\Provider;

use SlmMail\Mail\Message\ProvidesOptions;
use SlmMail\Mail\Message\ProvidesTags;
use Zend\Mail\Message;

class Mailgun extends Message
{
    use ProvidesOptions, ProvidesTags;
}
