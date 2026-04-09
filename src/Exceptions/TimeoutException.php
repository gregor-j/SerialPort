<?php

declare(strict_types=1);

namespace GregorJ\SerialPort\Exceptions;

use Exception;
use Throwable;

/**
 * The TimeoutException is thrown in case the timeout was reached without
 * reaching the goal.
 */
final class TimeoutException extends Exception
{
    private string $partialResponse;

    /**
     * Construct the exception. Note: The message is NOT binary safe.
     * @link https://php.net/manual/en/exception.construct.php
     * @param string $message [optional] The Exception message to throw.
     * @param int $code [optional] The Exception code.
     * @param null|Throwable $previous [optional] The previous throwable used for the exception chaining.
     * @param string $partialResponse [optional] The partial response before the timeout occurred.
     */
    public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null, string $partialResponse = '')
    {
        $this->partialResponse = $partialResponse;
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the partial response before the timeout occurred.
     * @return string
     */
    public function getPartialResponse(): string
    {
        return $this->partialResponse;
    }
}
