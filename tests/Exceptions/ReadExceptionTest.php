<?php

namespace Tests\GregorJ\SerialPort\Exceptions;

use GregorJ\SerialPort\Exceptions\ReadException;
use PHPUnit\Framework\TestCase;

/**
 * Class ReadExceptionTest
 * @package Tests\GregorJ\SerialPort\Exceptions
 * @author  Gregor J.
 */
final class ReadExceptionTest extends TestCase
{
    /**
     * Test setting and getting a response.
     */
    public function testSettingResponse(): void
    {
        $exception = new ReadException('ABC');
        static::assertSame('ABC', $exception->getResponse());
        static::assertSame('', $exception->getMessage());
        static::assertSame(0, $exception->getCode());
        static::assertNull($exception->getPrevious());
    }
}
