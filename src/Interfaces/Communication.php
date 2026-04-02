<?php

declare(strict_types=1);

namespace GregorJ\SerialPort\Interfaces;

use GregorJ\SerialPort\Exceptions\ConnectionException;
use GregorJ\SerialPort\Exceptions\InvalidValueException;
use GregorJ\SerialPort\Exceptions\ReadException;
use GregorJ\SerialPort\Exceptions\TimeoutException;
use GregorJ\SerialPort\Exceptions\UnexpectedResponseException;
use GregorJ\SerialPort\Exceptions\WriteException;

/**
 * A communication interface to send commands and get responses.
 * @package GregorJ\SerialPort\Interfaces
 * @author  Gregor J.
 */
interface Communication
{
    /**
     * Write the string and append an optional termination character to that string.
     * @param string $string
     * @param string $terminator optional termination string to append
     * @return void
     * @throws InvalidValueException
     * @throws ConnectionException
     * @throws WriteException
     */
    public function write(string $string, string $terminator = ''): void;

    /**
     * Set the time in seconds to wait for a response.
     * @param float $seconds
     * @return void
     * @throws InvalidValueException
     */
    public function setTimeout(float $seconds): void;

    /**
     * Read the response.
     * In case a terminator string is given, read until that string appears.
     * @param string $terminator
     * @return string
     * @throws InvalidValueException
     * @throws ReadException
     * @throws ConnectionException
     * @throws TimeoutException
     * @throws UnexpectedResponseException
     */
    public function read(string $terminator = ''): string;
}
