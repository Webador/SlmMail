<?php

namespace SlmMail\Service\Exception;

use RuntimeException;
use SlmMail\Exception\ExceptionInterface;

/**
 * This exception is thrown whenever the API returns that credentials are wrong
 */
class InvalidCredentialsException extends RuntimeException implements ExceptionInterface
{
}
