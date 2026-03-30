<?php

namespace Tests\GregorJ\SerialPort\Exceptions;

use GregorJ\SerialPort\Exceptions\UnexpectedResponseException;
use PHPUnit\Framework\TestCase;

class UnexpectedResponseExceptionTest extends TestCase
{
    /**
     * Test getting the raw response from the exception.
     * @return void
     */
    public function testUnexpectedResponseException(): void
    {
        $exception = new UnexpectedResponseException('abc', 0, null, 'response');
        $this->assertSame('response', $exception->getRawResponse());
    }
}
