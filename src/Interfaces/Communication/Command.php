<?php

declare(strict_types=1);

namespace GregorJ\SerialPort\Interfaces\Communication;

use GregorJ\SerialPort\Exceptions\ConnectionException;
use GregorJ\SerialPort\Exceptions\InvalidValueException;
use GregorJ\SerialPort\Exceptions\ReadException;
use GregorJ\SerialPort\Exceptions\TimeoutException;
use GregorJ\SerialPort\Exceptions\UnexpectedResponseException;
use GregorJ\SerialPort\Exceptions\WriteException;
use GregorJ\SerialPort\Interfaces\Communication;

/**
 * A Command is a string sent to a serial port. Depending on the Command,
 * there can be a Response containing Values. Therefore, the Command not only
 * defines the command string, but also which Values to expect, how to read
 * them, and how long to wait for them.
 * @package GregorJ\SerialPort\Interfaces
 * @author  Gregor J.
 */
interface Command
{
    /**
     * Invoke this Command on the given communication instance.
     * @param Communication $communication
     * @return Response|null
     * @throws InvalidValueException
     * @throws ReadException
     * @throws ConnectionException
     * @throws TimeoutException
     * @throws UnexpectedResponseException
     * @throws WriteException
     */
    public function invoke(Communication $communication): ?Response;

    /**
     * Transform the Command to a printable string for logging.
     * Non-printable characters are expected to be displayed as printable!
     * @return string
     */
    public function __toString(): string;
}
