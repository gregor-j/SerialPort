<?php

namespace Tests\GregorJ\SerialPort\Streams;

use GregorJ\SerialPort\Exceptions\OpenStreamException;
use GregorJ\SerialPort\Exceptions\StreamStateException;
use GregorJ\SerialPort\Exceptions\UnexpectedResponseException;
use GregorJ\SerialPort\Exceptions\WriteStreamException;
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
     * @throws OpenStreamException
     * @throws StreamStateException
     * @throws UnexpectedResponseException
     * @throws WriteStreamException
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
        $this->assertTrue($socket->getStatus()->timedOut());
        $this->assertSame('1234', $response);
        $socket->close();
    }

    /**
     * Test exception thrown in case stream is already opened.
     * @return void
     * @throws OpenStreamException
     * @throws StreamStateException
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
     * Test exception thrown in case the remote host refuses a connection.
     * @return void
     * @throws OpenStreamException
     * @throws StreamStateException
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
     * @return void
     * @throws StreamStateException
     * @throws WriteStreamException
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
     * @return void
     * @throws StreamStateException
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
     * @return void
     * @throws StreamStateException
     */
    public function testSetTimeoutWithoutOpeningFirst(): void
    {
        $fifo = new LocalFifo();
        $socket = new TcpSocket('127.0.0.1', $fifo->getTcpPort());
        $this->expectException(StreamStateException::class);
        $this->expectExceptionMessage('Stream not opened.');
        $socket->setTimeout(0);
    }

    /**
     * Test exception thrown in case stream is not opened.
     * @return void
     * @throws StreamStateException
     */
    public function testSetBlockingWithoutOpeningFirst(): void
    {
        $fifo = new LocalFifo();
        $socket = new TcpSocket('127.0.0.1', $fifo->getTcpPort());
        $this->expectException(StreamStateException::class);
        $this->expectExceptionMessage('Stream not opened.');
        $socket->setBlocking(true);
    }

    /**
     * Test exception thrown in case stream is not opened.
     * @return void
     * @throws StreamStateException
     * @throws UnexpectedResponseException
     */
    public function testTimedOutWithoutOpeningFirst(): void
    {
        $fifo = new LocalFifo();
        $socket = new TcpSocket('127.0.0.1', $fifo->getTcpPort());
        $this->expectException(StreamStateException::class);
        $this->expectExceptionMessage('Stream not opened.');
        /** @noinspection PhpUnusedLocalVariableInspection */
        $timedOut = $socket->getStatus()->timedOut();
    }

    /**
     * Test exception thrown in case fifo went away.
     * @return void
     * @throws StreamStateException
     * @throws WriteStreamException
     */
    public function testFifoWentAway(): void
    {
        $fifo = new LocalFifo();
        $socket = new TcpSocket('127.0.0.1', $fifo->getTcpPort());
        $fifo = null;
        sleep(1);
        $this->expectException(StreamStateException::class);
        $this->expectExceptionMessage('Stream not opened.');
        /** @noinspection PhpUnusedLocalVariableInspection */
        $bytes = $socket->write('lalala');
    }
}
