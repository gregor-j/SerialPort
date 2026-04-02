<?php

declare(strict_types=1);

namespace GregorJ\SerialPort\Exceptions;

use Throwable;

/**
 * Class TimeoutException
 * The timeout exception is thrown in case the timeout was reached in communication.
 * @package GregorJ\SerialPort\Exceptions
 * @author  Gregor J.
 */
final class TimeoutException extends RuntimeException
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
