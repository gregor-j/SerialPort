<?php

declare(strict_types=1);

namespace Tests\GregorJ\SerialPort;

use GregorJ\SerialPort\Container\TcpStreamContainer;
use GregorJ\SerialPort\Exceptions\ConnectionException;
use GregorJ\SerialPort\Exceptions\InvalidParamException;
use GregorJ\SerialPort\Exceptions\TimeoutException;
use GregorJ\SerialPort\Exceptions\UnexpectedResponseException;
use GregorJ\SerialPort\Exceptions\WriteException;
use GregorJ\SerialPort\Interfaces\Stream;
use GregorJ\SerialPort\Interfaces\Stream\TcpStreamConnector;
use GregorJ\SerialPort\StreamCommunication;
use GregorJ\SerialPort\TcpStream;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

use function sprintf;

/**
 * Unit tests for the StreamCommunication class.
 */
final class StreamCommunicationTest extends TestCase
{
    /**
     * Test __toString() method of StreamCommunication.
     * @return void
     */
    public function testToString(): void
    {
        $stream = $this->getMockBuilder(Stream::class)->getMock();
        $stream->expects($this->once())
            ->method('__toString')
            ->willReturn('abc://de:f');
        $serialPort = new StreamCommunication($stream);
        $this->assertSame('abc://de:f', (string)$serialPort);
    }

    /**
     * Test setting an invalid timeout.
     * @return void
     * @throws InvalidParamException
     */
    public function testInvalidTimeout()
    {
        $stream = $this->getMockBuilder(Stream::class)->getMock();
        $serialPort = new StreamCommunication($stream);
        $this->expectException(InvalidParamException::class);
        $this->expectExceptionMessage('Response timeout for StreamCommunication has to be positive.');
        $serialPort->setTimeout(-2.5);
    }

    /**
     * Test connection failed exception.
     * @return void
     * @throws ConnectionException
     * @throws InvalidParamException
     * @throws TimeoutException
     * @throws UnexpectedResponseException
     * @throws WriteException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testConnectionFailed(): void
    {
        $connector = $this->getMockBuilder(TcpStreamConnector::class)->getMock();
        $connector->expects($this->once())
            ->method('connect')
            ->willThrowException(new ConnectionException('Connection failed!'));
        $stream = new TcpStream('a', 1, 1.0, new TcpStreamContainer($connector));
        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('Connection failed!');
        $serial = new StreamCommunication($stream);
        $serial->query('y');
    }

    /**
     * Test exception thrown in case the written bytes differ.
     * @return void
     * @throws ConnectionException
     * @throws InvalidParamException
     * @throws TimeoutException
     * @throws UnexpectedResponseException
     * @throws WriteException
     */
    public function testWriteBytesDiffer(): void
    {
        $stream = $this->getMockBuilder(Stream::class)->getMock();
        $stream->expects($this->once())
            ->method('setTimeout')
            ->with(2.0);
        $stream->expects($this->once())
            ->method('write')
            ->with("testTestTest\n")
            ->willReturn(2000);
        $serialPort = new StreamCommunication($stream);
        $this->expectException(WriteException::class);
        $this->expectExceptionMessage('Expected to write 13 bytes, but 2000 bytes were written.');
        $serialPort->query('testTestTest', "\n");
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
        $serialPort = new StreamCommunication($stream);
        $serialPort->setTimeout(5.4);
        $this->expectException(TimeoutException::class);
        $this->expectExceptionMessage('Response timed out on serial port.');
        $serialPort->query('testTestTest', "\n", "\n");
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
        $serialPort = new StreamCommunication($stream);
        $serialPort->setTimeout(5.4);
        $response = $serialPort->query('testTestTest', "\n", "\n");
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
        $serialPort = new StreamCommunication($stream);
        $serialPort->setTimeout(0.5);
        $response = $serialPort->query('testTestTest', "\n");
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
    public function testGetLog(): void
    {
        $stream = $this->getMockBuilder(Stream::class)->getMock();
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
        $serialPort = new StreamCommunication($stream);
        $serialPort->setTimeout(5.4);
        $response = $serialPort->query('blaBlaBla', "\n", "\n");
        $this->assertEquals("ab\n", $response);
        $log = $serialPort->getLog();
        $this->assertCount(4, $log);
        $this->assertSame(sprintf('default timeout %f seconds', StreamCommunication::DEFAULT_TIMEOUT), $log[0]);
        $this->assertSame('set timeout to 5.400000 seconds', $log[1]);
        $this->assertSame('write "blaBlaBla\n"', $log[2]);
        $this->assertSame('read "ab\n"', $log[3]);
    }
}
