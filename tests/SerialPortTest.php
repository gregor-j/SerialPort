<?php

declare(strict_types=1);

namespace Tests\GregorJ\SerialPort;

use GregorJ\SerialPort\Exceptions\ConnectionException;
use GregorJ\SerialPort\Exceptions\InvalidParamException;
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
     * Test __toString() method of SerialPort.
     * @return void
     * @throws ConnectionException
     */
    public function testToString(): void
    {
        $stream = $this->getMockBuilder(Stream::class)->getMock();
        $stream->expects($this->exactly(2))
            ->method('__toString')
            ->willReturn('abc://de:f');
        $serialPort = new SerialPort($stream);
        $this->assertSame('abc://de:f', (string)$serialPort);
    }

    /**
     * Test setting an invalid timeout.
     * @return void
     * @throws ConnectionException
     * @throws InvalidParamException
     */
    public function testInvalidTimeout()
    {
        $stream = $this->getMockBuilder(Stream::class)->getMock();
        $stream->expects($this->once())
            ->method('open');
        $serialPort = new SerialPort($stream);
        $this->expectException(InvalidParamException::class);
        $this->expectExceptionMessage('Response timeout for SerialPort has to be positive.');
        $serialPort->setTimeout(-2.5);
    }

    /**
     * Test connection failed exception.
     * @return void
     * @throws ConnectionException
     */
    public function testConnectionFailed(): void
    {
        $stream = $this->getMockBuilder(Stream::class)->getMock();
        $stream->expects($this->once())
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
     * @throws WriteException
     * @throws InvalidParamException
     */
    public function testEmptyCommand(): void
    {
        $stream = $this->getMockBuilder(Stream::class)->getMock();
        $stream->expects($this->once())
            ->method('open');
        $stream->expects($this->once())
            ->method('setBlocking')
            ->with(true);
        $stream->expects($this->once())
            ->method('setTimeout')
            ->with(2.0);
        $stream->expects($this->once())
            ->method('write')
            ->with("testTestTest\n")
            ->willReturn(2000);
        $serialPort = new SerialPort($stream);
        $this->expectException(WriteException::class);
        $this->expectExceptionMessage('Expected to write 13 bytes, but 2000 bytes were written.');
        $serialPort->write('testTestTest', "\n");
    }

    /**
     * Test causing a InvalidParamException because of an empty command.
     * @return void
     * @throws ConnectionException
     * @throws WriteException
     * @throws InvalidParamException
     */
    public function testWriteException(): void
    {
        $stream = $this->getMockBuilder(Stream::class)->getMock();
        $stream->expects($this->exactly(2))
            ->method('isOpen')
            ->willReturn(false, true);
        $stream->expects($this->once())
            ->method('open');
        $stream->expects($this->once())
            ->method('close');
        $stream->expects($this->once())
            ->method('setBlocking')
            ->with(true);
        $serialPort = new SerialPort($stream);
        $this->expectException(InvalidParamException::class);
        $this->expectExceptionMessage('Cannot write empty string.');
        $serialPort->write('', "\n");
    }

    /**
     * Test the TimeoutException when reading from stream.
     * @return void
     * @throws ConnectionException
     * @throws InvalidParamException
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
        $stream->expects($this->once())
            ->method('open');
        $stream->expects($this->once())
            ->method('close');
        $stream->expects($this->once())
            ->method('setBlocking')
            ->with(true);
        $stream->expects($this->exactly(2))
            ->method('setTimeout')
            ->with(5.4);
        $stream->expects($this->once())
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
        $this->expectExceptionMessage('Response timed out on serial port.');
        $serialPort->read("\n");
    }

    /**
     * Test reading from stream until terminator appears.
     * @return void
     * @throws ConnectionException
     * @throws InvalidParamException
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
        $stream->expects($this->once())
            ->method('open');
        $stream->expects($this->once())
            ->method('close');
        $stream->expects($this->once())
            ->method('setBlocking')
            ->with(true);
        $stream->expects($this->exactly(2))
            ->method('setTimeout')
            ->with(5.4);
        $stream->expects($this->once())
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
        $this->assertEquals("xy\n", $response);
    }

    /**
     * Test reading from stream until timeout.
     * @return void
     * @throws ConnectionException
     * @throws InvalidParamException
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
        $stream->expects($this->once())
            ->method('open');
        $stream->expects($this->once())
            ->method('close');
        $stream->expects($this->once())
            ->method('setBlocking')
            ->with(true);
        $stream->expects($this->exactly(2))
            ->method('setTimeout')
            ->with(0.5);
        $stream->expects($this->once())
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
        $this->assertEquals('xyz', $response);
    }

    /**
     * Test reading from stream and getting the communication log.
     * @return void
     * @throws ConnectionException
     * @throws InvalidParamException
     * @throws TimeoutException
     * @throws UnexpectedResponseException
     * @throws WriteException
     */
    public function testLog(): void
    {
        $stream = $this->getMockBuilder(Stream::class)->getMock();
        $stream->expects($this->exactly(1))
            ->method('__toString')
            ->willReturn("a://bcd:56");
        $stream->expects($this->exactly(2))
            ->method('isOpen')
            ->willReturn(false, true);
        $stream->expects($this->once())
            ->method('open');
        $stream->expects($this->once())
            ->method('close');
        $stream->expects($this->once())
            ->method('setBlocking')
            ->with(true);
        $stream->expects($this->exactly(2))
            ->method('setTimeout')
            ->with(5.4);
        $stream->expects($this->once())
            ->method('write')
            ->with("blaBlaBla\n")
            ->willReturn(10);
        $stream->expects($this->exactly(3))
            ->method('readChar')
            ->willReturn('a', 'b', "\n");
        $stream->expects($this->exactly(2))
            ->method('timedOut')
            ->willReturn(false, false);
        $serialPort = new SerialPort($stream);
        $serialPort->setTimeout(5.4);
        $serialPort->write('blaBlaBla', "\n");
        $response = $serialPort->read("\n");
        $this->assertEquals("ab\n", $response);
        $log = $serialPort->getLog();
        $this->assertCount(5, $log);
        $this->assertSame('open a://bcd:56', $log[0]);
        $this->assertSame(sprintf('set timeout to %f seconds', SerialPort::DEFAULT_TIMEOUT), $log[1]);
        $this->assertSame('set timeout to 5.400000 seconds', $log[2]);
        $this->assertSame('write "blaBlaBla\n"', $log[3]);
        $this->assertSame('read "ab\n"', $log[4]);
    }
}
