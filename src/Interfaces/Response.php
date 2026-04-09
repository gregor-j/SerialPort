<?php

declare(strict_types=1);

namespace GregorJ\SerialPort\Interfaces;

use GregorJ\SerialPort\Exceptions\NotFoundException;

/**
 * The command defines which Response will be returned. The Response decides
 * how to interpret the raw response of the Command.
 * In case the constructor fails to interpret the raw response, throw an
 * UnexpectedResponseException.
 */
interface Response
{
    /**
     * Get a value from the Response.
     * @param string $name The name of the value.
     * @return mixed
     * @throws NotFoundException
     */
    public function get(string $name): mixed;

    /**
     * Determine whether the Response contains a Value.
     * @param string $name The name of the Value.
     * @return bool
     */
    public function has(string $name): bool;

    /**
     * Transform the Response to a printable string for logging.
     * Non-printable characters are expected to be displayed as printable!
     * @return string
     */
    public function __toString(): string;

    /**
     * Get the raw uninterpreted response for debugging.
     * @return mixed
     */
    public function getRawResponse(): mixed;
}
