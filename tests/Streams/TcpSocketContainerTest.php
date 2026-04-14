<?php

namespace Tests\GregorJ\SerialPort\Streams;

use GregorJ\SerialPort\Exceptions\NotFoundException;
use GregorJ\SerialPort\Interfaces\Stream\StreamIo;
use GregorJ\SerialPort\Interfaces\Stream\TcpSocketConnector;
use GregorJ\SerialPort\Interfaces\System\Clock;
use GregorJ\SerialPort\Interfaces\System\Error;
use GregorJ\SerialPort\Streams\TcpSocketContainer;
use PHPUnit\Framework\TestCase;

class TcpSocketContainerTest extends TestCase
{
    /**
     * Test default services.
     * @return void
     * @throws NotFoundException
     */
    public function testDefaultServices(): void
    {
        $container = new TcpSocketContainer();
        $this->assertTrue($container->has(TcpSocketConnector::class));
        $this->assertInstanceOf(TcpSocketConnector::class, $container->get(TcpSocketConnector::class));
        $this->assertTrue($container->has(StreamIo::class));
        $this->assertInstanceOf(StreamIo::class, $container->get(StreamIo::class));
        $this->assertTrue($container->has(Clock::class));
        $this->assertInstanceOf(Clock::class, $container->get(Clock::class));
        $this->assertTrue($container->has(Error::class));
        $this->assertInstanceOf(Error::class, $container->get(Error::class));
    }

    /**
     * @return void
     * @throws NotFoundException
     */
    public function testGetNotFoundException(): void
    {
        $container = new TcpSocketContainer();
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Service "NonExistentService" was not found in TcpSocketContainer.');
        $container->get('NonExistentService');
    }
}
