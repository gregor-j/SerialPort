<?php

declare(strict_types=1);

namespace Tests\GregorJ\SerialPort\Container;

use GregorJ\SerialPort\Container\TcpSocketContainer;
use GregorJ\SerialPort\Interfaces\Stream\StreamIo;
use GregorJ\SerialPort\Interfaces\Stream\TcpSocketConnector;
use GregorJ\SerialPort\Interfaces\System\Clock;
use GregorJ\SerialPort\Interfaces\System\Error;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Unit tests for TcpSocketContainer
 */
class TcpSocketContainerTest extends TestCase
{
    /**
     * Test default services.
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
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
     * @throws NotFoundExceptionInterface
     * @throws ContainerExceptionInterface
     */
    public function testGetNotFoundException(): void
    {
        $container = new TcpSocketContainer();
        $this->expectException(NotFoundExceptionInterface::class);
        $this->expectExceptionMessage('Requested dependency "NonExistent" not found.');
        $container->get('NonExistent');
    }

    /**
     * @return void
     */
    public function testHasNot(): void
    {
        $container = new TcpSocketContainer();
        $this->assertFalse($container->has('NonExistent'));
        $this->assertFalse($container->has(''));
    }
}
