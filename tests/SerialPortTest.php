<?php

namespace Tests\GregorJ\SerialPort;

use GregorJ\SerialPort\Exceptions\OpenStreamException;
use GregorJ\SerialPort\Interfaces\Communication\Command;
use GregorJ\SerialPort\Interfaces\Stream;
use GregorJ\SerialPort\SerialPort;
use PHPUnit\Framework\TestCase;

/**
 * Class SerialPortTest
 * Unit tests for the SerialPort class.
 * @package Tests\GregorJ\SerialPort
 * @author  Gregor J.
 */
class SerialPortTest extends TestCase
{
    /**
     * TCP port ncat listens on.
     */
    public const ECHO_PORT = 9999;

    /**
     * Test connection failed exception.
     */
    public function testConnectionFailed(): void
    {
        $stream = $this->getMockBuilder(Stream::class)->getMock();
        $stream->expects(static::once())
            ->method('open')
            ->willThrowException(new OpenStreamException('Connection failed!', 111));
        $this->expectException(OpenStreamException::class);
        $this->expectExceptionMessage('Connection failed!');
        $this->expectExceptionCode(111);
        new SerialPort($stream);
    }

    /**
     * Test invoking a command.
     */
    public function testInvokingCommand(): void
    {
        $stream = $this->getMockBuilder(Stream::class)->getMock();
        $stream->expects(static::once())
            ->method('open');
        $stream->expects(static::once())
            ->method('close');
        $command = $this->getMockBuilder(Command::class)->getMock();
        $command->expects(static::once())
            ->method('invoke')
            ->willReturn(null);
        $device = new SerialPort($stream);
        $result = $device->invoke($command);
        static::assertNull($result);
    }
}
