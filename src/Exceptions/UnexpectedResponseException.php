<?php

declare(strict_types=1);

namespace GregorJ\SerialPort\Exceptions;

use Throwable;

/**
 * Exception thrown in case the response doesn't match the expectations of the class implementing the Response
 * interface.
 */
final class UnexpectedResponseException extends LogicException
{
    private mixed $rawResponse;

    /**
     * Construct the exception. Note: The message is NOT binary safe.
     * @link https://php.net/manual/en/exception.construct.php
     * @param string $message [optional] The Exception message to throw.
     * @param int $code [optional] The Exception code.
     * @param null|Throwable $previous [optional] The previous throwable used for the exception chaining.
     * @param mixed $rawResponse [optional] The raw/unmodified response that lead to the exception.
     */
    public function __construct(string $message = "", int $code = 0, ?Throwable $previous = null, mixed $rawResponse = null)
    {
        $this->rawResponse = $rawResponse;
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get the raw/unmodified response, that lead to the exception.
     * @return mixed
     */
    public function getRawResponse(): mixed
    {
        return $this->rawResponse;
    }
}
