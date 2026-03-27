<?php

namespace GregorJ\SerialPort\Interfaces\Communication;

use GregorJ\SerialPort\Exceptions\NotFoundException;

/**
 * The Command needs to know which values will be returned and assigns
 * these values to names. A value from the response can be queried by its name.
 * Use Command class constants for value names.
 * @package GregorJ\SerialPort\Interfaces
 * @author  Gregor J.
 */
interface Response
{
    /**
     * Get a value from the Response.
     * @param string $name The name of the value.
     * @return float|bool|int|string
     * @throws NotFoundException
     */
    public function get(string $name): float|bool|int|string;

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
}
