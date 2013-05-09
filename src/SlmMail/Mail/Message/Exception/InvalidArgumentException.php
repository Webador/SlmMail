<?php

namespace SlmMail\Mail\Message\Exception;

use InvalidArgumentException as BaseInvalidArgumentException;
use SlmMail\Exception\ExceptionInterface;

class InvalidArgumentException extends BaseInvalidArgumentException implements ExceptionInterface
{
}
