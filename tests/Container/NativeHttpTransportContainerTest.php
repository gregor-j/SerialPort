<?php

declare(strict_types=1);

namespace Tests\GregorJ\SerialPort\Container;

use GregorJ\SerialPort\Container\NativeHttpTransportContainer;
use GregorJ\SerialPort\Exceptions\NotFoundException;
use GregorJ\SerialPort\Interfaces\Http\HttpStreamIo;
use GregorJ\SerialPort\Interfaces\System\Error;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Unit tests for NativeHttpTransportContainer.
 */
final class NativeHttpTransportContainerTest extends TestCase
{
    /**
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testDefaultServices(): void
    {
        $container = new NativeHttpTransportContainer();

        $this->assertTrue($container->has(HttpStreamIo::class));
        $this->assertTrue($container->has(Error::class));

        $streamIo = $container->get(HttpStreamIo::class);
        $this->assertInstanceOf(HttpStreamIo::class, $streamIo);

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
        $container = new NativeHttpTransportContainer();

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Missing required dependency');
        $container->get('NonExistent');
    }

    /**
     * @return void
     */
    public function testHasNot(): void
    {
        $container = new NativeHttpTransportContainer();
        $this->assertFalse($container->has('NonExistent'));
        $this->assertFalse($container->has(''));
    }
}
