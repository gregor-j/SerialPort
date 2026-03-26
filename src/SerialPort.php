<?php

namespace GregorJ\SerialPort;

use GregorJ\SerialPort\Interfaces\Communication\Command;
use GregorJ\SerialPort\Interfaces\Communication\Container;
use GregorJ\SerialPort\Interfaces\Communication;
use GregorJ\SerialPort\Interfaces\Stream;

/**
 * Class SerialPort
 * Invoke serial port commands on a configured stream.
 * @package GregorJ\SerialPort
 * @author  Gregor J.
 */
final class SerialPort implements Communication
{
    private Stream $stream;

    /**
     * @inheritDoc
     */
    public function __construct(Stream $stream)
    {
        $this->stream = $stream;
        $this->stream->open();
    }

    /**
     * @inheritDoc
     */
    public function __destruct()
    {
        $this->stream->close();
    }

    /**
     * @inheritDoc
     */
    public function invoke(Command $command): ?Container
    {
        return $command->invoke($this->stream);
    }
}
