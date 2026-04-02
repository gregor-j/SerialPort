<?php

namespace Tests\GregorJ\SerialPort\Exceptions;

use GregorJ\SerialPort\Exceptions\TimeoutException;
use PHPUnit\Framework\TestCase;

class TimeoutExceptionTest extends TestCase
{
    /**
     * Test getting the partial response from the TimeoutException.
     * @return void
     */
    public function testGetPartialResponse()
    {
        $exception = new TimeoutException('lalala', 1, null, 'Hello Worl');
        $this->assertSame('lalala', $exception->getMessage());
        $this->assertSame(1, $exception->getCode());
        $this->assertNull($exception->getPrevious());
        $this->assertSame('Hello Worl', $exception->getPartialResponse());
    }
}
