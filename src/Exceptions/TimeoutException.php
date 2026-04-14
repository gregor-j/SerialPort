<?php

declare(strict_types=1);

namespace GregorJ\SerialPort\Exceptions;

use Exception;

/**
 * The TimeoutException is thrown in case the timeout was reached without
 * reaching the goal.
 */
final class TimeoutException extends Exception
{
}
