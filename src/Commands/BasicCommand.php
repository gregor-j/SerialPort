<?php

declare(strict_types=1);

namespace GregorJ\SerialPort\Commands;

use GregorJ\SerialPort\Exceptions\InvalidParamException;
use GregorJ\SerialPort\Interfaces\Command;
use GregorJ\SerialPort\Interfaces\Communication;
use GregorJ\SerialPort\Responses\StringResponse;
use GregorJ\ToString\ToString;

/**
 * Invoke a basic string command on a Communication and return its
 * StringResponse.
 */
final class BasicCommand implements Command
{
    public const DEFAULT_TIMEOUT = 2.0;
    private string $command;
    private string $commandTerminator;
    private string $readTerminator;
    private float $timeout;

    /**
     * Define a string command its terminator, a read terminator and sets a timeout for this command.
     * @param string $command
     * @param string $commandTerminator
     * @param string $readTerminator
     * @param float|null $timeoutSeconds
     * @throws InvalidParamException
     */
    public function __construct(string $command, string $commandTerminator = '', string $readTerminator = '', float $timeoutSeconds = null)
    {
        $this->command = $command;
        $this->commandTerminator = $commandTerminator;
        $this->readTerminator = $readTerminator;
        // set default timeout in case no timeout is provided
        $timeoutSeconds = $timeoutSeconds ?? self::DEFAULT_TIMEOUT;
        if ($timeoutSeconds < 0.0) {
            throw new InvalidParamException('The response timeout for BasicCommand has to be positive.');
        }
        $this->timeout = $timeoutSeconds;
    }

    /**
     * @inheritDoc
     */
    public function invoke(Communication $communication): StringResponse
    {
        $communication->setTimeout($this->timeout);
        $response = $communication->query($this->command, $this->commandTerminator, $this->readTerminator);
        return new StringResponse($response, $this->readTerminator);
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return ToString::fromString($this->command . $this->commandTerminator);
    }
}
