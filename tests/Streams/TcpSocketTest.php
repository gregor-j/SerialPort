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
use Tests\GregorJ\SerialPort\LocalTcpServer;

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
        $server = new LocalTcpServer();
        $socket = new TcpSocket('127.0.0.1', $server->getTcpPort());
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
        $server = new LocalTcpServer();
        $socket = new TcpSocket('127.0.0.1', $server->getTcpPort());
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
        $server = new LocalTcpServer();
        $socket = new TcpSocket('127.0.0.1', $server->getTcpPort());
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
        $server = new LocalTcpServer();
        $socket = new TcpSocket('127.0.0.1', $server->getTcpPort());
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
        $server = new LocalTcpServer();
        $socket = new TcpSocket('127.0.0.1', $server->getTcpPort());
        $socket->open();
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Cannot write empty string.');
        $socket->write('');
    }

    /**
     * Test exception thrown in case the server is gone before writing.
     * @return void
     * @throws StateException
     * @throws WriteException
     * @throws InvalidValueException
     */
    public function testServerWentAway(): void
    {
        $server = new LocalTcpServer();
        $socket = new TcpSocket('127.0.0.1', $server->getTcpPort());
        $server = null;
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

    /**
     * getStatus() must return a TcpSocketStatus with correct field values when open.
     * @return void
     * @throws ConnectionException
     * @throws InvalidValueException
     * @throws StateException
     * @throws UnexpectedResponseException
     */
    public function testGetStatusWhenOpen(): void
    {
        $server = new LocalTcpServer();
        $socket = new TcpSocket('127.0.0.1', $server->getTcpPort());
        $socket->open();
        $socket->setBlocking(true);
        $status = $socket->getStatus();
        // A freshly connected, blocking socket has not timed out.
        $this->assertFalse($status->timedOut());
        // setBlocking(true) means the socket is in blocking mode.
        $this->assertTrue($status->blocked());
        // Stream type for a TCP socket created via fsockopen().
        $this->assertSame('tcp_socket/ssl', $status->streamType());
        // A freshly connected socket has not reached EOF.
        $this->assertFalse($status->eof());
        // TCP sockets are not seekable.
        $this->assertFalse($status->seekable());

        $socket->close();
    }

    /**
     * getStatus() must throw StateException when the socket is not open.
     * @return void
     * @throws StateException
     * @throws UnexpectedResponseException
     * @throws InvalidValueException
     */
    public function testGetStatusWhenClosed(): void
    {
        $socket = new TcpSocket('127.0.0.1', 7777);
        $this->expectException(StateException::class);
        $this->expectExceptionMessage('TCP connection not established.');
        $socket->getStatus();
    }
}
