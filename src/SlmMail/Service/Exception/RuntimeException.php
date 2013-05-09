<?php

namespace SlmMail\Service\Exception;

use RuntimeException as BaseRuntimeException;
use SlmMail\Exception\ExceptionInterface;

/**
 * This exception is thrown for exceptions that cannot be classified
 */
class RuntimeException extends BaseRuntimeException implements ExceptionInterface
{
}
