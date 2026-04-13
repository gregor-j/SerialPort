<?php

declare(strict_types=1);

namespace GregorJ\SerialPort\Interfaces;

use GregorJ\SerialPort\Exceptions\ConnectionException;
use GregorJ\SerialPort\Exceptions\InvalidParamException;
use GregorJ\SerialPort\Exceptions\UnexpectedResponseException;
use GregorJ\SerialPort\Exceptions\WriteException;

/**
 * A stream interface to write to and read from.
 *
 * Bluntly copied and adapted from Peter Gribanovs example:
 * @link https://github.com/jupeter/clean-code-php/issues/178
 */
interface Stream
{
    /**
     * String representation of the Stream class type and config for logging.
     * @return string
     */
    public function __toString(): string;

    /**
     * Writes the contents of the string to the stream.
     * @param string $string The string that is to be written.
     * @param null|float $timeoutSeconds The maximum time to wait for the write operation to complete.
     * @return int returns the number of bytes written
     * @throws InvalidParamException
     * @throws ConnectionException
     * @throws WriteException
     */
    public function write(string $string, float $timeoutSeconds = null): int;

    /**
     * Read a single character from the stream.
     * @return string|null Returns a string containing a single character read
     *                     from the stream. Returns NULL on EOF.
     * @throws ConnectionException
     */
    public function readChar(): ?string;

    /**
     * Set timeout period on the stream.
     * @param float $seconds The seconds part of the timeout to be set.
     * @return bool Returns TRUE on success or FALSE on failure.
     * @throws InvalidParamException
     * @throws ConnectionException
     */
    public function setTimeout(float $seconds): bool;

    /**
     * Check whether the read or write command timed out.
     * @return bool
     * @throws ConnectionException
     * @throws UnexpectedResponseException
     */
    public function timedOut(): bool;

    /**
     * Retrieves status response from the stream with additional information.
     * Use has() and get() methods to query status details.
     * @return Response
     * @throws ConnectionException
     * @throws UnexpectedResponseException
     */
    public function getStatus(): Response;
}
