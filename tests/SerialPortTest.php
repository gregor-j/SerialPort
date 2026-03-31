<?php

declare(strict_types=1);

namespace Tests\GregorJ\SerialPort;

use GregorJ\SerialPort\Exceptions\ConnectionException;
use GregorJ\SerialPort\Exceptions\InvalidValueException;
use GregorJ\SerialPort\Exceptions\ReadException;
use GregorJ\SerialPort\Exceptions\StateException;
use GregorJ\SerialPort\Exceptions\TimeoutException;
use GregorJ\SerialPort\Exceptions\UnexpectedResponseException;
use GregorJ\SerialPort\Exceptions\WriteException;
use GregorJ\SerialPort\Interfaces\Stream;
use GregorJ\SerialPort\SerialPort;
use PHPUnit\Framework\TestCase;

/**
 * Class SerialPortTest
 * Unit tests for the SerialPort class.
 * @package Tests\GregorJ\SerialPort
 * @author  Gregor J.
 */
final class SerialPortTest extends TestCase
{
    /**
     * Test setting an invalid timeout.
     * @return void
     * @throws ConnectionException
     * @throws InvalidValueException
     * @throws StateException
     */
    public function testInvalidTimeout()
    {
        $stream = $this->getMockBuilder(Stream::class)->getMock();
        $stream->expects(static::once())
            ->method('open');
        $serialPort = new SerialPort($stream);
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Timeout has to be positive.');
        $serialPort->setTimeout(-2.5);
    }

    /**
     * Test connection failed exception.
     * @return void
     * @throws ConnectionException
     * @throws StateException
     */
    public function testConnectionFailed(): void
    {
        $stream = $this->getMockBuilder(Stream::class)->getMock();
        $stream->expects(static::once())
            ->method('open')
            ->willThrowException(new ConnectionException('Connection failed!'));
        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('Connection failed!');
        new SerialPort($stream);
    }

    /**
     * Test causing
     * @return void
     * @throws ConnectionException
     * @throws StateException
     * @throws WriteException
     * @throws InvalidValueException
     */
    public function testEmptyCommand(): void
    {
        $stream = $this->getMockBuilder(Stream::class)->getMock();
        $stream->expects(static::once())
            ->method('open');
        $stream->expects(static::once())
            ->method('setBlocking')
            ->with(true);
        $stream->expects(static::once())
            ->method('setTimeout')
            ->with(2.0);
        $stream->expects(static::once())
            ->method('write')
            ->with("testTestTest\n")
            ->willReturn(2000);
        $serialPort = new SerialPort($stream);
        $this->expectException(WriteException::class);
        $this->expectExceptionMessage('Expected to write 13 bytes, but 2000 bytes were written.');
        $serialPort->write('testTestTest', "\n");
    }

    /**
     * Test causing a InvalidValueException because of an empty command.
     * @return void
     * @throws ConnectionException
     * @throws StateException
     * @throws WriteException
     * @throws InvalidValueException
     */
    public function testWriteException(): void
    {
        $stream = $this->getMockBuilder(Stream::class)->getMock();
        $stream->expects($this->exactly(2))
            ->method('isOpen')
            ->willReturn(false, true);
        $stream->expects(static::once())
            ->method('open');
        $stream->expects(static::once())
            ->method('close');
        $stream->expects(static::once())
            ->method('setBlocking')
            ->with(true);
        $serialPort = new SerialPort($stream);
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Cannot write empty string.');
        $serialPort->write('', "\n");
    }

    /**
     * Test the TimeoutException when reading from stream.
     * @return void
     * @throws ConnectionException
     * @throws InvalidValueException
     * @throws ReadException
     * @throws StateException
     * @throws TimeoutException
     * @throws UnexpectedResponseException
     * @throws WriteException
     */
    public function testReadTimeoutException(): void
    {
        $stream = $this->getMockBuilder(Stream::class)->getMock();
        $stream->expects($this->exactly(2))
            ->method('isOpen')
            ->willReturn(false, true);
        $stream->expects(static::once())
            ->method('open');
        $stream->expects(static::once())
            ->method('close');
        $stream->expects(static::once())
            ->method('setBlocking')
            ->with(true);
        $stream->expects($this->exactly(2))
            ->method('setTimeout')
            ->with(5.4);
        $stream->expects(static::once())
            ->method('write')
            ->with("testTestTest\n")
            ->willReturn(13);
        $stream->expects($this->exactly(2))
            ->method('readChar')
            ->willReturn('x', 'y');
        $stream->expects($this->exactly(3))
            ->method('timedOut')
            ->willReturn(false, true, true);
        $serialPort = new SerialPort($stream);
        $serialPort->setTimeout(5.4);
        $serialPort->write('testTestTest', "\n");
        $this->expectException(TimeoutException::class);
        $this->expectExceptionMessage('Timed out while reading.');
        $serialPort->read("\n");
    }

    /**
     * Test reading from stream until terminator appears.
     * @return void
     * @throws ConnectionException
     * @throws InvalidValueException
     * @throws ReadException
     * @throws StateException
     * @throws TimeoutException
     * @throws UnexpectedResponseException
     * @throws WriteException
     */
    public function testReadUntilTerminator(): void
    {
        $stream = $this->getMockBuilder(Stream::class)->getMock();
        $stream->expects($this->exactly(2))
            ->method('isOpen')
            ->willReturn(false, true);
        $stream->expects(static::once())
            ->method('open');
        $stream->expects(static::once())
            ->method('close');
        $stream->expects(static::once())
            ->method('setBlocking')
            ->with(true);
        $stream->expects($this->exactly(2))
            ->method('setTimeout')
            ->with(5.4);
        $stream->expects(static::once())
            ->method('write')
            ->with("testTestTest\n")
            ->willReturn(13);
        $stream->expects($this->exactly(3))
            ->method('readChar')
            ->willReturn('x', 'y', "\n");
        $stream->expects($this->exactly(2))
            ->method('timedOut')
            ->willReturn(false, false);
        $serialPort = new SerialPort($stream);
        $serialPort->setTimeout(5.4);
        $serialPort->write('testTestTest', "\n");
        $response = $serialPort->read("\n");
        static::assertEquals("xy\n", $response);
    }

    /**
     * Test reading from stream until timeout.
     * @return void
     * @throws ConnectionException
     * @throws InvalidValueException
     * @throws ReadException
     * @throws StateException
     * @throws TimeoutException
     * @throws UnexpectedResponseException
     * @throws WriteException
     */
    public function testReadUntilTimeout(): void
    {
        $stream = $this->getMockBuilder(Stream::class)->getMock();
        $stream->expects($this->exactly(2))
            ->method('isOpen')
            ->willReturn(false, true);
        $stream->expects(static::once())
            ->method('open');
        $stream->expects(static::once())
            ->method('close');
        $stream->expects(static::once())
            ->method('setBlocking')
            ->with(true);
        $stream->expects($this->exactly(2))
            ->method('setTimeout')
            ->with(0.5);
        $stream->expects(static::once())
            ->method('write')
            ->with("testTestTest\n")
            ->willReturn(13);
        $stream->expects($this->exactly(3))
            ->method('readChar')
            ->willReturn('x', 'y', 'z');
        $stream->expects($this->exactly(3))
            ->method('timedOut')
            ->willReturn(false, false, true);
        $serialPort = new SerialPort($stream);
        $serialPort->setTimeout(0.5);
        $serialPort->write('testTestTest', "\n");
        $response = $serialPort->read();
        static::assertEquals('xyz', $response);
    }
}
