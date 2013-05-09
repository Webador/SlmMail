<?php

namespace SlmMail\Mail\Message\Provider\Exception;

use InvalidArgumentException as BaseInvalidArgumentException;
use SlmMail\Exception\ExceptionInterface;

class InvalidArgumentException extends BaseInvalidArgumentException implements ExceptionInterface
{
}
