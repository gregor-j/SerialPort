<?php

declare(strict_types=1);

namespace Tests\GregorJ\SerialPort\Container;

use GregorJ\SerialPort\Container\StreamWrapperTransportContainer;
use GregorJ\SerialPort\Exceptions\NotFoundException;
use GregorJ\SerialPort\Interfaces\Http\StreamWrapperIo;
use GregorJ\SerialPort\Interfaces\System\Error;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Unit tests for StreamWrapperTransportContainer.
 */
final class StreamWrapperTransportContainerTest extends TestCase
{
    /**
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testDefaultServices(): void
    {
        $container = new StreamWrapperTransportContainer();

        $this->assertTrue($container->has(StreamWrapperIo::class));
        $this->assertTrue($container->has(Error::class));

        $streamIo = $container->get(StreamWrapperIo::class);
        $this->assertInstanceOf(StreamWrapperIo::class, $streamIo);

        $errors = $container->get(Error::class);
        $this->assertInstanceOf(Error::class, $errors);
    }

    /**
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testGetNotFoundException(): void
    {
        $container = new StreamWrapperTransportContainer();

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Requested dependency "NonExistent" not found.');
        $container->get('NonExistent');
    }

    /**
     * @return void
     */
    public function testHasNot(): void
    {
        $container = new StreamWrapperTransportContainer();
        $this->assertFalse($container->has('NonExistent'));
        $this->assertFalse($container->has(''));
    }
}
