<?php

namespace SlmMail\Mail\Message\Provider;

use SlmMail\Mail\Message\ProvidesAttachments;
use Zend\Mail\Message;

class SendGrid extends Message
{
    use ProvidesAttachments;
}
