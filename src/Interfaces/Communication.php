<?php

namespace GregorJ\SerialPort\Interfaces;

use GregorJ\SerialPort\Exceptions\ConnectionException;
use GregorJ\SerialPort\Exceptions\ReadException;
use GregorJ\SerialPort\Exceptions\StateException;
use GregorJ\SerialPort\Exceptions\UnexpectedResponseException;
use GregorJ\SerialPort\Exceptions\WriteException;
use GregorJ\SerialPort\Interfaces\Communication\Command;
use GregorJ\SerialPort\Interfaces\Communication\Response;

/**
 * A stream communication interface to send commands and get responses.
 * @package GregorJ\SerialPort\Interfaces
 * @author  Gregor J.
 */
interface Communication
{
    /**
     * Open a connection using the given stream.
     * @param Stream $stream
     * @throws ConnectionException
     * @throws StateException
     */
    public function __construct(Stream $stream);

    /**
     * Close the connection to the stream.
     */
    public function __destruct();

    /**
     * Invoke a command on the stream.
     * @param Command $command
     * @return Response|null Returns null in case the command expects no response
     * @throws WriteException
     * @throws ReadException
     * @throws UnexpectedResponseException
     */
    public function invoke(Command $command): ?Response;
}
