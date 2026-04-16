<?php

declare(strict_types=1);

namespace Tests\GregorJ\SerialPort\Responses;

use GregorJ\SerialPort\Exceptions\NotFoundException;
use GregorJ\SerialPort\Responses\StringResponse;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Unit tests for the StringResponse class.
 */
final class StringResponseTest extends TestCase
{
    /**
     * Test the difference between a raw and a clean response.
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testRawAndCleanResponse(): void
    {
        $response = new StringResponse("abc\n", "\n");
        $this->assertTrue($response->has(StringResponse::RESPONSE));
        $this->assertSame("abc", $response->get(StringResponse::RESPONSE));
        $this->assertSame("abc", (string)$response);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testNotFoundException(): void
    {
        $response = new StringResponse("abc\n", "\n");
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Response "lalala" not found.');
        $response->get('lalala');
    }
}
