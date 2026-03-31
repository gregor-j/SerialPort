<?php

declare(strict_types=1);

namespace GregorJ\SerialPort\Commands;

use GregorJ\SerialPort\Interfaces\Communication;
use GregorJ\SerialPort\Interfaces\Communication\Command;
use GregorJ\SerialPort\Responses\StringResponse;
use kbATeam\ByteDebug\ToString;

/**
 * Invoke a basic string command on a communication and return its response.
 * @package GregorJ\SerialPort
 * @author  Gregor J.
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
     * @param float|null $seconds
     */
    public function __construct(string $command, string $commandTerminator = '', string $readTerminator = '', float $seconds = null)
    {
        $this->command = $command;
        $this->commandTerminator = $commandTerminator;
        $this->readTerminator = $readTerminator;
        $this->timeout = $seconds !== null ? $seconds : self::DEFAULT_TIMEOUT;
    }

    /**
     * @inheritDoc
     */
    public function invoke(Communication $communication): StringResponse
    {
        $communication->setTimeout($this->timeout);
        $communication->write($this->command, $this->commandTerminator);
        return new StringResponse($communication->read($this->readTerminator), $this->readTerminator);
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return ToString::fromString($this->command . $this->commandTerminator);
    }
}
