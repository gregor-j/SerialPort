<?php

declare(strict_types=1);

namespace Tests\GregorJ\SerialPort\Streams;

use GregorJ\SerialPort\Exceptions\ConnectionException;
use GregorJ\SerialPort\Exceptions\InvalidValueException;
use GregorJ\SerialPort\Exceptions\StateException;
use GregorJ\SerialPort\Exceptions\UnexpectedResponseException;
use GregorJ\SerialPort\Exceptions\WriteException;
use GregorJ\SerialPort\Streams\TcpSocket;
use PHPUnit\Framework\TestCase;
use Tests\GregorJ\SerialPort\LocalFifo;

/**
 * Class TcpSocketTest
 * Unit tests for the TcpSocket class.
 * @package Tests\GregorJ\SerialPort\Streams
 * @author  Gregor J.
 */
final class TcpSocketTest extends TestCase
{
    /**
     * Test actual reading and writing from an echo service.
     * @return void
     * @throws ConnectionException
     * @throws StateException
     * @throws UnexpectedResponseException
     * @throws WriteException
     * @throws InvalidValueException
     */
    public function testReadingAndWriting(): void
    {
        $fifo = new LocalFifo();
        $socket = new TcpSocket('127.0.0.1', $fifo->getTcpPort());
        $socket->open();
        $bytes = $socket->write('1234');
        $this->assertSame(4, $bytes);
        $socket->setTimeout(0.5);
        $socket->setBlocking(true);
        $response = '';
        while ($char = $socket->readChar()) {
            $response .= $char;
        }
        $this->assertNull($char);
        $this->assertTrue($socket->timedOut());
        $this->assertSame('1234', $response);
        $socket->close();
    }

    /**
     * Test exception thrown in case socket is already opened.
     * @return void
     * @throws ConnectionException
     * @throws InvalidValueException
     * @throws StateException
     */
    public function testOpeningTwice(): void
    {
        $fifo = new LocalFifo();
        $socket = new TcpSocket('127.0.0.1', $fifo->getTcpPort());
        $socket->open();
        $this->expectException(StateException::class);
        $this->expectExceptionMessage('TCP connection already established.');
        $socket->open();
    }

    /**
     * Test exception thrown in case the remote host refuses a connection.
     * @return void
     * @throws ConnectionException
     * @throws InvalidValueException
     * @throws StateException
     */
    public function testConnectionError(): void
    {
        $socket = new TcpSocket('127.0.0.16', 7777);
        $this->expectException(ConnectionException::class);
        $this->expectExceptionCode(111);
        $socket->open();
    }

    /**
     * Test exception thrown in case socket is not opened.
     * @return void
     * @throws StateException
     * @throws WriteException
     * @throws InvalidValueException
     */
    public function testWritingWithoutOpeningFirst(): void
    {
        $fifo = new LocalFifo();
        $socket = new TcpSocket('127.0.0.1', $fifo->getTcpPort());
        $this->expectException(StateException::class);
        $this->expectExceptionMessage('TCP connection not established.');
        $socket->write('');
    }

    /**
     * @return void
     * @throws ConnectionException
     * @throws InvalidValueException
     * @throws StateException
     */
    public function testSetInvalidTimeout(): void
    {
        $fifo = new LocalFifo();
        $socket = new TcpSocket('127.0.0.1', $fifo->getTcpPort());
        $socket->open();
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Timeout has to be positive.');
        $socket->setTimeout(-0.5);
    }

    /**
     * Test InvalidValueException when trying to write an empty string.
     * @return void
     * @throws ConnectionException
     * @throws InvalidValueException
     * @throws StateException
     * @throws WriteException
     */
    public function testWritingEmptyString(): void
    {
        $fifo = new LocalFifo();
        $socket = new TcpSocket('127.0.0.1', $fifo->getTcpPort());
        $socket->open();
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Cannot write empty string.');
        $socket->write('');
    }

    /**
     * Test exception thrown in case fifo went away.
     * @return void
     * @throws StateException
     * @throws WriteException
     * @throws InvalidValueException
     */
    public function testFifoWentAway(): void
    {
        $fifo = new LocalFifo();
        $socket = new TcpSocket('127.0.0.1', $fifo->getTcpPort());
        $fifo = null;
        sleep(1);
        $this->expectException(StateException::class);
        $this->expectExceptionMessage('TCP connection not established.');
        /** @noinspection PhpUnusedLocalVariableInspection */
        $bytes = $socket->write('lalala');
    }

    /**
     * Constructor must reject negative timeout values.
     * @return void
     */
    public function testConstructorWithInvalidTimeout(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Timeout has to be positive.');
        new TcpSocket('127.0.0.1', 7777, -0.1);
    }
}
