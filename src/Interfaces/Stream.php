<?php

namespace GregorJ\SerialPort\Interfaces;

use GregorJ\SerialPort\Exceptions\ConnectionException;
use GregorJ\SerialPort\Exceptions\InvalidValueException;
use GregorJ\SerialPort\Exceptions\StreamStateException;
use GregorJ\SerialPort\Exceptions\UnexpectedResponseException;
use GregorJ\SerialPort\Exceptions\WriteStreamException;
use GregorJ\SerialPort\Interfaces\Communication\Response;

/**
 * A stream interface to write to and read from.
 *
 * Bluntly copied and adapted from Peter Gribanovs example:
 * @link https://github.com/jupeter/clean-code-php/issues/178
 *
 * @package GregorJ\SerialPort\Interfaces
 * @author  Gregor J.
 * @author  Peter Gribanov
 */
interface Stream
{
    /**
     * Has the stream already been opened?
     * @return bool
     */
    public function isOpen(): bool;

    /**
     * Opens a stream
     * @throws ConnectionException
     * @throws StreamStateException
     */
    public function open(): void;

    /**
     * Closes a stream
     */
    public function close(): void;

    /**
     * Writes the contents of the string to the stream.
     * @param string $string The string that is to be written.
     * @return int returns the number of bytes written
     * @throws StreamStateException
     * @throws WriteStreamException
     * @throws InvalidValueException
     */
    public function write(string $string): int;

    /**
     * Read a single character from the stream.
     * @return string|null Returns a string containing a single character read
     *                     from the stream. Returns NULL on EOF.
     * @throws StreamStateException
     */
    public function readChar(): ?string;

    /**
     * Set timeout period on the stream.
     * @param float $seconds The seconds part of the timeout to be set.
     * @return bool Returns TRUE on success or FALSE on failure.
     * @throws StreamStateException
     * @throws InvalidValueException
     */
    public function setTimeout(float $seconds): bool;

    /**
     * Set blocking/non-blocking mode on a stream.
     * @param bool $blocking If mode is FALSE, the given stream will be switched to non-blocking mode, and if TRUE, it
     *                       will be switched to blocking mode. This affects calls like fgets and fread that read from
     *                       the stream. In non-blocking mode an fgets call will always return right away while in
     *                       blocking mode it will wait for data to become available on the stream.
     * @return bool true on success or false on failure.
     * @throws StreamStateException
     */
    public function setBlocking(bool $blocking): bool;

    /**
     * Retrieves status response from the stream with additional information.
     * Use has() and get() methods to query status details.
     * @return Response
     * @throws StreamStateException
     * @throws UnexpectedResponseException
     */
    public function getStatus(): Response;
}
