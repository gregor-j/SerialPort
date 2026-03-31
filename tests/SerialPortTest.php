<?php

namespace Tests\GregorJ\SerialPort;

use GregorJ\SerialPort\Exceptions\ConnectionException;
use GregorJ\SerialPort\Exceptions\ReadException;
use GregorJ\SerialPort\Exceptions\StateException;
use GregorJ\SerialPort\Exceptions\UnexpectedResponseException;
use GregorJ\SerialPort\Exceptions\WriteException;
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
            ->willThrowException(new ConnectionException('Connection failed!', 111));
        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('Connection failed!');
        $this->expectExceptionCode(111);
        new SerialPort($stream);
    }

    /**
     * Test invoking a command.
     * @return void
     * @throws ConnectionException
     * @throws StateException
     * @throws ReadException
     * @throws UnexpectedResponseException
     * @throws WriteException
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
