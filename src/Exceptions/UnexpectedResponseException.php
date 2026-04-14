<?php

declare(strict_types=1);

namespace GregorJ\SerialPort\Exceptions;

use Exception;

/**
 * The UnexpectedResponseException is thrown in case the response didn't match
 * the expectation of the class implementing the Response interface.
 */
final class UnexpectedResponseException extends Exception
{
}
