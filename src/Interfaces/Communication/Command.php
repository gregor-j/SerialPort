<?php

namespace GregorJ\SerialPort\Interfaces\Communication;

use GregorJ\SerialPort\Exceptions\ReadException;
use GregorJ\SerialPort\Exceptions\WriteStreamException;
use GregorJ\SerialPort\Interfaces\Stream;

/**
 * A Command is a string sent to a serial port. Depending on the Command,
 * there can be a Container containing Values. Therefore the Command not only
 * defines the command string, but also which Values to expect, how to read
 * them, and how long to wait for them.
 * @package GregorJ\SerialPort\Interfaces
 * @author  Gregor J.
 */
interface Command
{
    /**
     * Invoke this Command on the given stream.
     * @param Stream $stream
     * @return Container|null
     * @throws WriteStreamException
     * @throws ReadException
     */
    public function invoke(Stream $stream): ?Container;

    /**
     * Transform the Command to a printable string for logging.
     * Non-printable characters are expected to be displayed as printable!
     * @return string
     */
    public function __toString(): string;
}
