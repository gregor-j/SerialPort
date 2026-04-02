<?php

declare(strict_types=1);

namespace Tests\GregorJ\SerialPort\Streams;

use GregorJ\SerialPort\Exceptions\ConnectionException;
use GregorJ\SerialPort\Exceptions\InvalidValueException;
use GregorJ\SerialPort\Exceptions\UnexpectedResponseException;
use GregorJ\SerialPort\Exceptions\WriteException;
use GregorJ\SerialPort\Streams\TcpSocket;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
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
     * Test exception thrown in case the remote host refuses a connection.
     * @return void
     * @throws ConnectionException
     * @throws InvalidValueException
     */
    public function testConnectionError(): void
    {
        $socket = new TcpSocket('127.0.0.16', 7777);
        $this->expectException(ConnectionException::class);
        $this->expectExceptionCode(111);
        $socket->open();
    }

    /**
     * @return void
     * @throws ConnectionException
     * @throws InvalidValueException
     */
    public function testSetInvalidTimeout(): void
    {
        $server = new LocalTcpServer();
        $socket = new TcpSocket('127.0.0.1', $server->getTcpPort());
        $socket->open();
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Response timeout for TcpSocket has to be positive.');
        $socket->setTimeout(-0.5);
    }

    /**
     * Test InvalidValueException when trying to write an empty string.
     * @return void
     * @throws ConnectionException
     * @throws InvalidValueException
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
     * Constructor must reject negative timeout values.
     * @return void
     */
    public function testConstructorWithInvalidTimeout(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Connection timeout for TcpSocket has to be positive.');
        new TcpSocket('127.0.0.1', 7777, -0.1);
    }

    /**
     * getStatus() must return a TcpSocketStatus with correct field values when open.
     * @return void
     * @throws ConnectionException
     * @throws InvalidValueException
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
     * write() must wrap fwrite() failures in a WriteException.
     *
     * Uses a read-only stream handle to deterministically force fwrite() to return false.
     *
     * @return void
     * @throws InvalidValueException
     */
    public function testWriteThrowsWhenFwriteReturnsFalse(): void
    {
        $readOnlyStream = fopen('php://temp', 'rb');
        $this->assertIsResource($readOnlyStream);

        $socket = new TcpSocket('127.0.0.1', 7777);
        $reflection = new ReflectionClass($socket);
        $socketProperty = $reflection->getProperty('socket');
        /** @noinspection PhpExpressionResultUnusedInspection */
        $socketProperty->setAccessible(true);
        $socketProperty->setValue($socket, $readOnlyStream);

        try {
            $socket->write('x');
            $this->fail('Expected WriteException was not thrown.');
        } catch (WriteException $exception) {
            $this->assertStringStartsWith('Failed to write "x" to TCP connection 127.0.0.1:7777:', $exception->getMessage());
        } finally {
            fclose($readOnlyStream);
        }
    }
}
