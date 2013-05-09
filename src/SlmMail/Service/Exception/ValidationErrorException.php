<?php

namespace SlmMail\Service\Exception;

use InvalidArgumentException;
use SlmMail\Exception\ExceptionInterface;

/**
 * This exception is thrown if the API returned validation errors
 */
class ValidationErrorException extends InvalidArgumentException implements ExceptionInterface
{
}
