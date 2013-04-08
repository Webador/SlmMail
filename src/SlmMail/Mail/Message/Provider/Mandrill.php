<?php


namespace SlmMail\Mail\Message\Provider;

use SlmMail\Mail\Message\Message;
use SlmMail\Mail\Message\ProvidesAttachments;
use SlmMail\Mail\Message\ProvidesTags;

class Mandrill extends Message
{
    use ProvidesAttachments, ProvidesTags;
}
