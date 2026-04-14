<?php

declare(strict_types=1);

namespace GregorJ\SerialPort\Interfaces;

use GregorJ\SerialPort\Exceptions\ConnectionException;
use GregorJ\SerialPort\Exceptions\InvalidParamException;
use GregorJ\SerialPort\Exceptions\TimeoutException;
use GregorJ\SerialPort\Exceptions\UnexpectedResponseException;
use GregorJ\SerialPort\Exceptions\WriteException;

/**
 * A communication interface to send Commands and get Responses.
 */
interface Communication
{
    /**
     * String representation of the Communication class type and config for logging.
     * @return string
     */
    public function __toString(): string;

    /**
     * Write the string and read the response.
     * @param string $string
     * @param string $writeTerminator optional termination string to append to the string
     * @param string $readTerminator optional termination string to wait for in the response
     * @return string
     * @throws InvalidParamException
     * @throws ConnectionException
     * @throws WriteException
     * @throws UnexpectedResponseException
     * @throws TimeoutException
     */
    public function query(string $string, string $writeTerminator = '', string $readTerminator = ''): string;

    /**
     * Set the time in seconds to wait for a response.
     * @param float $seconds
     * @return void
     * @throws InvalidParamException
     */
    public function setTimeout(float $seconds): void;

    /**
     * Get the communication log of all write(), read() and setTimeout() calls of this class instance.
     * @return string[]
     */
    public function getLog(): array;
}
