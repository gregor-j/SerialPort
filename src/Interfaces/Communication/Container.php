<?php

namespace GregorJ\SerialPort\Interfaces\Communication;

use GregorJ\SerialPort\Exceptions\NotFoundException;

/**
 * The Command needs to know which values will be returned and assigns
 * these values to names. A Values from the Reponse can be queried by its name.
 * Use Command class constants for Value names.
 * @package GregorJ\SerialPort\Interfaces
 * @author  Gregor J.
 */
interface Container
{
    /**
     * Get a Value from the Container.
     * @param string $name The name of the Value.
     * @return Value
     * @throws NotFoundException
     */
    public function get(string $name): Value;

    /**
     * Determine whether the Container contains a Value.
     * @param string $name The name of the Value.
     * @return bool
     */
    public function has(string $name): bool;

    /**
     * Transform the Container to a printable string for logging.
     * Non-printable characters are expected to be displayed as printable!
     * @return string
     */
    public function __toString(): string;
}
