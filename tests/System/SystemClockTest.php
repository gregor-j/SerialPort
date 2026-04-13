<?php

declare(strict_types=1);

namespace Tests\GregorJ\SerialPort\System;

use GregorJ\SerialPort\System\SystemClock;
use PHPUnit\Framework\TestCase;

use function microtime;

/**
 * Unit tests for the SystemClock class.
 */
final class SystemClockTest extends TestCase
{
    /**
     * Test that now() returns a float timestamp in the current time window.
     */
    public function testNowReturnsCurrentTimestampAsFloat(): void
    {
        $clock = new SystemClock();

        $before = microtime(true);
        $now = $clock->now();
        $after = microtime(true);

        $this->assertGreaterThanOrEqual($before, $now);
        $this->assertLessThanOrEqual($after, $now);
    }
}
