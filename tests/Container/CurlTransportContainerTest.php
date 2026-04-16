<?php

declare(strict_types=1);

namespace Tests\GregorJ\SerialPort\Container;

use GregorJ\SerialPort\Container\CurlTransportContainer;
use GregorJ\SerialPort\Exceptions\NotFoundException;
use GregorJ\SerialPort\Interfaces\Http\CurlIo;
use GregorJ\SerialPort\Interfaces\System\Error;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Unit tests for CurlTransportContainer.
 */
final class CurlTransportContainerTest extends TestCase
{
    /**
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testDefaultServices(): void
    {
        $container = new CurlTransportContainer();

        $this->assertTrue($container->has(CurlIo::class));
        $this->assertTrue($container->has(Error::class));

        $curlIo = $container->get(CurlIo::class);
        $this->assertInstanceOf(CurlIo::class, $curlIo);

        $error = $container->get(Error::class);
        $this->assertInstanceOf(Error::class, $error);
    }

    /**
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testGetNotFoundException(): void
    {
        $container = new CurlTransportContainer();

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Requested dependency "NonExistent" not found.');
        $container->get('NonExistent');
    }

    /**
     * @return void
     */
    public function testHasNot(): void
    {
        $container = new CurlTransportContainer();

        $this->assertFalse($container->has('NonExistent'));
        $this->assertFalse($container->has(''));
    }
}
