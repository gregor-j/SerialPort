<?php

declare(strict_types=1);

namespace Tests\GregorJ\SerialPort\System;

use GregorJ\SerialPort\System\SystemError;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the SystemError class.
 */
final class SystemErrorTest extends TestCase
{
    /**
     * Test getting and emptying the last error.
     * @return void
     */
    public function testGetLastErrorReturnsErrorArrayAfterTriggeredError(): void
    {
        $systemError = new SystemError();
        $systemError->clearLastError();

        @trigger_error('SystemError test warning', E_USER_WARNING);

        $lastError = $systemError->getLastError();

        $this->assertIsArray($lastError);
        $this->assertSame(E_USER_WARNING, $lastError['type']);
        $this->assertStringContainsString('SystemError test warning', (string)$lastError['message']);
        $this->assertArrayHasKey('file', $lastError);
        $this->assertArrayHasKey('line', $lastError);

        $systemError->clearLastError();
        $this->assertNull($systemError->getLastError());
    }
}
