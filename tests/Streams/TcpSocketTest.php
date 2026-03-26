<?php

namespace Tests\GregorJ\SerialPort\Streams;

use GregorJ\SerialPort\Exceptions\OpenStreamException;
use GregorJ\SerialPort\Exceptions\StreamStateException;
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
     */
    public function testReadingAndWriting(): void
    {
        $fifo = new LocalFifo();
        $socket = new TcpSocket('127.0.0.1', $fifo->getTcpPort());
        $socket->open();
        $bytes = $socket->write('1234');
        static::assertSame(4, $bytes);
        $socket->setTimeout(0, 500000);
        $response = '';
        while ($char = $socket->readChar()) {
            $response .= $char;
        }
        static::assertNull($char);
        static::assertTrue($socket->timedOut());
        static::assertSame('1234', $response);
        $socket->close();
    }

    /**
     * Test exception thrown in case stream is already opened.
     */
    public function testOpeningTwice(): void
    {
        $fifo = new LocalFifo();
        $socket = new TcpSocket('127.0.0.1', $fifo->getTcpPort());
        $socket->open();
        $this->expectException(StreamStateException::class);
        $this->expectExceptionMessage('Stream already opened.');
        $socket->open();
    }

    /**
     * Test exception thrown in case stream is already opened.
     */
    public function testConnectionError(): void
    {
        $socket = new TcpSocket('127.0.0.16', 7777);
        $this->expectException(OpenStreamException::class);
        $this->expectExceptionMessage('Connection refused');
        $this->expectExceptionCode(111);
        $socket->open();
    }

    /**
     * Test exception thrown in case stream is not opened.
     */
    public function testWritingWithoutOpeningFirst(): void
    {
        $fifo = new LocalFifo();
        $socket = new TcpSocket('127.0.0.1', $fifo->getTcpPort());
        $this->expectException(StreamStateException::class);
        $this->expectExceptionMessage('Stream not opened.');
        $socket->write('');
    }

    /**
     * Test exception thrown in case stream is not opened.
     */
    public function testReadWithoutOpeningFirst(): void
    {
        $fifo = new LocalFifo();
        $socket = new TcpSocket('127.0.0.1', $fifo->getTcpPort());
        $this->expectException(StreamStateException::class);
        $this->expectExceptionMessage('Stream not opened.');
        $socket->readChar();
    }

    /**
     * Test exception thrown in case stream is not opened.
     */
    public function testSetTimeoutWithoutOpeningFirst(): void
    {
        $fifo = new LocalFifo();
        $socket = new TcpSocket('127.0.0.1', $fifo->getTcpPort());
        $this->expectException(StreamStateException::class);
        $this->expectExceptionMessage('Stream not opened.');
        $socket->setTimeout(0, 0);
    }

    /**
     * Test exception thrown in case stream is not opened.
     */
    public function testTimedOutWithoutOpeningFirst(): void
    {
        $fifo = new LocalFifo();
        $socket = new TcpSocket('127.0.0.1', $fifo->getTcpPort());
        $this->expectException(StreamStateException::class);
        $this->expectExceptionMessage('Stream not opened.');
        $socket->timedOut();
    }
}
