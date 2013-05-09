<?php

namespace SlmMail\Service\Exception;

use RuntimeException as BaseRuntimeException;
use SlmMail\Exception\ExceptionInterface;

/**
 * This exception is thrown whenever the API returns that credentials are wrong
 */
class InvalidCredentialsException extends BaseRuntimeException implements ExceptionInterface
{
}
