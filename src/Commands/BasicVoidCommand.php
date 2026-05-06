<?php

declare(strict_types=1);

namespace GregorJ\SerialPort\Commands;

use GregorJ\SerialPort\Exceptions\InvalidParamException;
use GregorJ\SerialPort\Interfaces\Command;
use GregorJ\SerialPort\Interfaces\Communication;
use GregorJ\SerialPort\Interfaces\Response;
use GregorJ\ToString\ToString;

/**
 * Invoke a command on a communication, that doesn't return anything.
 *
 * @implements Command<null>
 */
final class BasicVoidCommand implements Command
{
    public const DEFAULT_TIMEOUT = 0.1;
    private string $command;
    private string $commandTerminator;
    private float $timeout;

    /**
     * Define a string command its terminator and sets a timeout for this command.
     * @param string $command
     * @param string $commandTerminator
     * @param float|null $timeoutSeconds
     * @throws InvalidParamException
     */
    public function __construct(string $command, string $commandTerminator = '', float $timeoutSeconds = null)
    {
        $this->command = $command;
        $this->commandTerminator = $commandTerminator;
        // set default timeout in case no timeout is provided
        $timeoutSeconds = $timeoutSeconds ?? self::DEFAULT_TIMEOUT;
        if ($timeoutSeconds < 0.0) {
            throw new InvalidParamException('The response timeout for BasicStringCommand has to be positive.');
        }
        $this->timeout = $timeoutSeconds;
    }

    /**
     * @inheritDoc
     */
    public function invoke(Communication $communication): ?Response
    {
        $communication->setTimeout($this->timeout);
        $communication->write($this->command, $this->commandTerminator);
        return null;
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return ToString::fromString($this->command . $this->commandTerminator);
    }
}
