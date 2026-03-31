<?php

namespace Tests\GregorJ\SerialPort\Responses;

use GregorJ\SerialPort\Exceptions\NotFoundException;
use GregorJ\SerialPort\Responses\StringResponse;
use PHPUnit\Framework\TestCase;

/**
 * Test the basic string response class.
 */
final class StringResponseTest extends TestCase
{
    /**
     * Test the difference between a raw and a clean response.
     * @return void
     * @throws NotFoundException
     */
    public function testRawAndCleanResponse(): void
    {
        $response = new StringResponse("abc\n", "\n");
        $this->assertTrue($response->has(StringResponse::RESPONSE));
        $this->assertTrue($response->has(StringResponse::RAW_RESPONSE));
        $this->assertSame("abc\n", $response->get(StringResponse::RAW_RESPONSE));
        $this->assertSame("abc\n", $response->getRawResponse());
        $this->assertSame("abc", $response->get(StringResponse::RESPONSE));
        $this->assertSame("abc", (string)$response);
    }

    public function testNotFoundException(): void
    {
        $response = new StringResponse("abc\n", "\n");
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Response "lalala" not found.');
        $response->get('lalala');
    }
}
