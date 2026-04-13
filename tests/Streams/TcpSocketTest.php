<?php

declare(strict_types=1);

namespace Tests\GregorJ\SerialPort\Streams;

use GregorJ\SerialPort\Exceptions\ConnectionException;
use GregorJ\SerialPort\Exceptions\InvalidParamException;
use GregorJ\SerialPort\Exceptions\UnexpectedResponseException;
use GregorJ\SerialPort\Exceptions\WriteException;
use GregorJ\SerialPort\Interfaces\Clock;
use GregorJ\SerialPort\Interfaces\Error;
use GregorJ\SerialPort\Interfaces\TcpSocketConnector;
use GregorJ\SerialPort\Interfaces\StreamIo;
use GregorJ\SerialPort\Streams\TcpSocket;
use PHPUnit\Framework\TestCase;
use Tests\GregorJ\SerialPort\LocalTcpServer;

/**
 * Unit tests for the TcpSocket class.
 */
final class TcpSocketTest extends TestCase
{
    /**
     * Test actual reading and writing from an echo service.
     * @return void
     * @throws ConnectionException
     * @throws UnexpectedResponseException
     * @throws WriteException
     * @throws InvalidParamException
     */
    public function testReadingAndWriting(): void
    {
        $server = new LocalTcpServer();
        $socket = new TcpSocket('127.0.0.1', $server->getTcpPort());
        $this->assertSame('tcp://127.0.0.1:' . $server->getTcpPort(), (string)$socket);
        $bytes = $socket->write('1234');
        $this->assertSame(4, $bytes);
        $socket->setTimeout(0.5);
        $response = '';
        while ($char = $socket->readChar()) {
            $response .= $char;
        }
        $this->assertNull($char);
        $this->assertTrue($socket->timedOut());
        $this->assertSame('1234', $response);
    }

    /**
     * Test exception thrown in case the remote host refuses a connection.
     * @return void
     * @throws ConnectionException
     * @throws InvalidParamException
     */
    public function testConnectionError(): void
    {
        $socket = new TcpSocket('127.0.0.16', 7777);
        $this->expectException(ConnectionException::class);
        $this->expectExceptionCode(111);
        $socket->readChar();
    }

    /**
     * @return void
     * @throws ConnectionException
     * @throws InvalidParamException
     */
    public function testSetInvalidTimeout(): void
    {
        $server = new LocalTcpServer();
        $socket = new TcpSocket('127.0.0.1', $server->getTcpPort());
        $this->expectException(InvalidParamException::class);
        $this->expectExceptionMessage('Response timeout for TcpSocket has to be positive.');
        $socket->setTimeout(-0.5);
    }

    /**
     * @return void
     * @throws ConnectionException
     * @throws InvalidParamException
     * @throws WriteException
     */
    public function testSetInvalidWriteTimeout(): void
    {
        $server = new LocalTcpServer();
        $socket = new TcpSocket('127.0.0.1', $server->getTcpPort());
        $this->expectException(InvalidParamException::class);
        $this->expectExceptionMessage('Write timeout for TcpSocket must be positive.');
        $socket->write('x', -0.5);
    }

    /**
     * Test InvalidParamException when trying to write an empty string.
     * @return void
     * @throws ConnectionException
     * @throws InvalidParamException
     * @throws WriteException
     */
    public function testWritingEmptyString(): void
    {
        $server = new LocalTcpServer();
        $socket = new TcpSocket('127.0.0.1', $server->getTcpPort());
        $this->expectException(InvalidParamException::class);
        $this->expectExceptionMessage('Cannot write empty string.');
        $socket->write('');
    }

    /**
     * Constructor must reject negative timeout values.
     * @return void
     */
    public function testConstructorWithInvalidTimeout(): void
    {
        $this->expectException(InvalidParamException::class);
        $this->expectExceptionMessage('Connection timeout for TcpSocket has to be positive.');
        new TcpSocket('127.0.0.1', 7777, -0.1);
    }

    /**
     * write() must wrap fwrite() failures in a WriteException.
     *
     * Uses a read-only stream handle to deterministically force fwrite() to return false.
     *
     * @return void
     * @throws ConnectionException
     * @throws InvalidParamException
     * @throws WriteException
     */
    public function testWriteThrowsWhenFwriteReturnsFalse(): void
    {
        $connector = new class () implements TcpSocketConnector {
            public function connect(string $hostname, int $port, int &$error_code, string &$error_message, float|null $timeout)
            {
                return fopen('php://temp', 'w+b');
            }
        };

        $io = new class () implements StreamIo {
            public function close($socket): bool
            {
                if (is_resource($socket)) {
                    fclose($socket);
                }
                return true;
            }

            public function write($socket, string $string, int|null $length): int|false
            {
                return false;
            }

            public function readChar($socket): string|false
            {
                return false;
            }

            public function setTimeout($socket, int $seconds, int $microseconds): bool
            {
                return true;
            }

            public function setBlocking($socket, bool $enable): bool
            {
                return true;
            }

            public function getMetadata($socket): array
            {
                return ['timed_out' => false];
            }
        };

        $clock = new class () implements Clock {
            public function now(): float
            {
                return 0.0;
            }
        };

        $errors = new class () implements Error {
            public function clearLastError(): void
            {
            }

            public function getLastError(): array
            {
                return ['type' => 2, 'message' => 'simulated fwrite failure'];
            }
        };

        $socket = new TcpSocket('127.0.0.1', 7777, null, $connector, $io, $clock, $errors);

        $this->expectException(WriteException::class);
        $this->expectExceptionMessage('Failed to write "x" to TCP connection 127.0.0.1:7777: simulated fwrite failure');
        $socket->write('x');
    }

    /**
     * write() must throw WriteException when write operation times out.
     *
     * This test verifies the timeout mechanism by creating a blocking scenario
     * where fwrite() cannot immediately write all data. We use a combination of
     * non-blocking mode and a small buffer size to force repeated zero-byte writes.
     *
     * @return void
     * @throws InvalidParamException
     * @throws ConnectionException
     */
    public function testWriteThrowsOnWriteTimeout(): void
    {
        $connector = new class () implements TcpSocketConnector {
            public function connect(string $hostname, int $port, int &$error_code, string &$error_message, float|null $timeout)
            {
                return fopen('php://temp', 'w+b');
            }
        };

        $io = new class () implements StreamIo {
            public function close($socket): bool
            {
                if (is_resource($socket)) {
                    fclose($socket);
                }
                return true;
            }

            public function write($socket, string $string, int|null $length): int
            {
                return 0;
            }

            public function readChar($socket): string|false
            {
                return false;
            }

            public function setTimeout($socket, int $seconds, int $microseconds): bool
            {
                return true;
            }

            public function setBlocking($socket, bool $enable): bool
            {
                return true;
            }

            public function getMetadata($socket): array
            {
                return ['timed_out' => false];
            }
        };

        $clock = new class () implements Clock {
            /** @var float[] */
            private array $values = [1000.0, 1000.2];
            private int $index = 0;

            public function now(): float
            {
                $value = $this->values[$this->index] ?? 1000.2;
                $this->index++;
                return $value;
            }
        };

        $errors = new class () implements Error {
            public function clearLastError(): void
            {
            }

            public function getLastError(): ?array
            {
                return null;
            }
        };

        $socket = new TcpSocket('127.0.0.1', 7777, null, $connector, $io, $clock, $errors);

        $this->expectException(WriteException::class);
        $this->expectExceptionMessage('Write operation timed out');
        $socket->write('payload', 0.05);
    }
}
