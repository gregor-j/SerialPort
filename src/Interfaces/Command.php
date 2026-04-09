<?php

declare(strict_types=1);

namespace GregorJ\SerialPort\Interfaces;

use GregorJ\SerialPort\Exceptions\ConnectionException;
use GregorJ\SerialPort\Exceptions\InvalidParamException;
use GregorJ\SerialPort\Exceptions\TimeoutException;
use GregorJ\SerialPort\Exceptions\UnexpectedResponseException;
use GregorJ\SerialPort\Exceptions\WriteException;

/**
 * A Command is a string sent to a serial port. Depending on the Command,
 * there can be a Response.
 */
interface Command
{
    /**
     * Invoke this Command on the given communication instance.
     * @param Communication $communication
     * @return Response|null
     * @throws InvalidParamException
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
