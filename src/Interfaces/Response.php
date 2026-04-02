<?php

declare(strict_types=1);

namespace GregorJ\SerialPort\Interfaces;

use GregorJ\SerialPort\Exceptions\NotFoundException;

/**
 * The Command needs to know which values will be returned and assigns
 * these values to names. A value from the response can be queried by its name.
 * Use Command class constants for value names.
 * In case the constructor fails to interpret the raw response, throw an UnexpectedResponseException.
 * @package GregorJ\SerialPort\Interfaces
 * @author  Gregor J.
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
