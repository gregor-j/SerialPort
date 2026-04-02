<?php

declare(strict_types=1);

namespace Tests\GregorJ\SerialPort\Streams;

use GregorJ\SerialPort\Exceptions\ConnectionException;
use GregorJ\SerialPort\Exceptions\InvalidValueException;
use GregorJ\SerialPort\Streams\Rfc2217Socket;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for RFC 2217 (Telnet COM Port Control Option) socket implementation.
 *
 * @package Tests\GregorJ\SerialPort\Streams
 * @author  Gregor J.
 */
final class Rfc2217SocketTest extends TestCase
{
    /**
     * Test constructor with valid parameters.
     */
    public function testConstructorWithValidParameters(): void
    {
        $socket = new Rfc2217Socket('127.0.0.1', 2001);
        $this->assertFalse($socket->isOpen());
    }

    /**
     * Test constructor with custom connection timeout.
     */
    public function testConstructorWithCustomConnectionTimeout(): void
    {
        $socket = new Rfc2217Socket('127.0.0.1', 2001, 5.0);
        $this->assertFalse($socket->isOpen());
    }

    /**
     * Test constructor rejects negative connection timeout.
     */
    public function testConstructorRejectsNegativeConnectionTimeout(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Connection timeout for Rfc2217Socket has to be positive.');
        new Rfc2217Socket('127.0.0.1', 2001, -1.0);
    }

    /**
     * Test setBaudRate with valid value.
     */
    public function testSetBaudRateWithValidValue(): void
    {
        $socket = new Rfc2217Socket('127.0.0.1', 2001);
        $socket->setBaudRate(9600);  // Should not throw
        $this->assertFalse($socket->isOpen());  // Connection not yet established
    }

    /**
     * Test setBaudRate rejects zero.
     */
    public function testSetBaudRateRejectsZero(): void
    {
        $socket = new Rfc2217Socket('127.0.0.1', 2001);
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Baud rate must be a positive integer.');
        $socket->setBaudRate(0);
    }

    /**
     * Test setBaudRate rejects negative value.
     */
    public function testSetBaudRateRejectsNegative(): void
    {
        $socket = new Rfc2217Socket('127.0.0.1', 2001);
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Baud rate must be a positive integer.');
        $socket->setBaudRate(-100);
    }

    /**
     * Test setDataBits with valid values.
     */
    public function testSetDataBitsWithValidValues(): void
    {
        $socket = new Rfc2217Socket('127.0.0.1', 2001);
        foreach ([5, 6, 7, 8] as $bits) {
            $socket->setDataBits($bits);  // Should not throw
        }
        $this->assertFalse($socket->isOpen());
    }

    /**
     * Test setDataBits rejects invalid values.
     */
    public function testSetDataBitsRejectsInvalidValues(): void
    {
        $socket = new Rfc2217Socket('127.0.0.1', 2001);
        foreach ([0, 1, 4, 9, 16] as $bits) {
            $this->expectException(InvalidValueException::class);
            $this->expectExceptionMessage('Data bits must be 5, 6, 7 or 8.');
            $socket->setDataBits($bits);
        }
    }

    /**
     * Test setParity with all valid values.
     */
    public function testSetParityWithValidValues(): void
    {
        $socket = new Rfc2217Socket('127.0.0.1', 2001);
        foreach (
            [
            Rfc2217Socket::PARITY_NONE,
            Rfc2217Socket::PARITY_ODD,
            Rfc2217Socket::PARITY_EVEN,
            Rfc2217Socket::PARITY_MARK,
            Rfc2217Socket::PARITY_SPACE,
            ] as $parity
        ) {
            $socket->setParity($parity);  // Should not throw
        }
        $this->assertFalse($socket->isOpen());
    }

    /**
     * Test setParity rejects invalid value.
     */
    public function testSetParityRejectsInvalidValue(): void
    {
        $socket = new Rfc2217Socket('127.0.0.1', 2001);
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Invalid parity value 0. Use one of the PARITY_* constants.');
        $socket->setParity(0);
    }

    /**
     * Test setStopBits with valid values.
     */
    public function testSetStopBitsWithValidValues(): void
    {
        $socket = new Rfc2217Socket('127.0.0.1', 2001);
        foreach (
            [
            Rfc2217Socket::STOP_BITS_1,
            Rfc2217Socket::STOP_BITS_2,
            Rfc2217Socket::STOP_BITS_1_5,
            ] as $stopBits
        ) {
            $socket->setStopBits($stopBits);  // Should not throw
        }
        $this->assertFalse($socket->isOpen());
    }

    /**
     * Test setStopBits rejects invalid value.
     */
    public function testSetStopBitsRejectsInvalidValue(): void
    {
        $socket = new Rfc2217Socket('127.0.0.1', 2001);
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Invalid stop bits value 0. Use one of the STOP_BITS_* constants.');
        $socket->setStopBits(0);
    }

    /**
     * Test setFlowControl with valid values.
     */
    public function testSetFlowControlWithValidValues(): void
    {
        $socket = new Rfc2217Socket('127.0.0.1', 2001);
        foreach (
            [
            Rfc2217Socket::FLOW_NONE,
            Rfc2217Socket::FLOW_XON_XOFF,
            Rfc2217Socket::FLOW_RTS_CTS,
            Rfc2217Socket::FLOW_DTR_DSR,
            ] as $flow
        ) {
            $socket->setFlowControl($flow);  // Should not throw
        }
        $this->assertFalse($socket->isOpen());
    }

    /**
     * Test setFlowControl rejects invalid value.
     */
    public function testSetFlowControlRejectsInvalidValue(): void
    {
        $socket = new Rfc2217Socket('127.0.0.1', 2001);
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Invalid flow control value 0. Use one of the FLOW_* constants.');
        $socket->setFlowControl(0);
    }

    /**
     * Test sendBreak rejects negative duration.
     */
    public function testSendBreakRejectsNegativeDuration(): void
    {
        $socket = new Rfc2217Socket('127.0.0.1', 2001);
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Break duration must not be negative.');
        // Note: this will fail during getSocket() but we test the validation first
        try {
            $socket->sendBreak(-1);
        } catch (InvalidValueException $e) {
            $this->assertStringContainsString('Break duration', $e->getMessage());
            throw $e;
        }
    }

    /**
     * Test write() with empty string throws InvalidValueException before connection attempt.
     */
    public function testWriteWithEmptyStringThrows(): void
    {
        $socket = new Rfc2217Socket('127.0.0.1', 2001);
        // The empty string check happens in write() before getSocket() is called
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Cannot write empty string.');
        $socket->write('');
    }

    /**
     * Test write() with negative timeout throws InvalidValueException before connection attempt.
     */
    public function testWriteWithNegativeTimeoutThrows(): void
    {
        $socket = new Rfc2217Socket('127.0.0.1', 2001);
        // The negative timeout check happens in write() before getSocket() is called
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Write timeout for Rfc2217Socket must be positive.');
        $socket->write('test', -0.5);
    }

    /**
     * Test isOpen() returns false initially.
     */
    public function testIsOpenReturnsFalseInitially(): void
    {
        $socket = new Rfc2217Socket('127.0.0.1', 2001);
        $this->assertFalse($socket->isOpen());
    }

    /**
     * Test close() when not open (should be idempotent).
     */
    public function testCloseWhenNotOpenIsIdempotent(): void
    {
        $socket = new Rfc2217Socket('127.0.0.1', 2001);
        $socket->close();  // Should not throw
        $this->assertFalse($socket->isOpen());
    }

    /**
     * Test destructor calls close().
     */
    public function testDestructorCallsClose(): void
    {
        $socket = new Rfc2217Socket('127.0.0.1', 2001);
        $this->assertFalse($socket->isOpen());
        unset($socket);  // Should not throw, destructor calls close()
    }

    /**
     * Test open() with unreachable host throws ConnectionException.
     */
    public function testOpenWithUnreachableHostThrows(): void
    {
        $socket = new Rfc2217Socket('127.0.0.16', 2001, 0.1);  // Non-existent host, short timeout
        $this->expectException(ConnectionException::class);
        $socket->open();
    }

    /**
     * Test setTimeout() with negative value throws InvalidValueException.
     */
    public function testSetTimeoutWithNegativeValueThrows(): void
    {
        $socket = new Rfc2217Socket('127.0.0.1', 2001);
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('Response timeout for Rfc2217Socket has to be positive.');
        try {
            $socket->setTimeout(-0.5);
        } catch (InvalidValueException $e) {
            throw $e;
        }
    }

    /**
     * Test that constants are defined and have expected values.
     */
    public function testConstantsAreDefined(): void
    {
        // Parity constants
        $this->assertSame(1, Rfc2217Socket::PARITY_NONE);
        $this->assertSame(2, Rfc2217Socket::PARITY_ODD);
        $this->assertSame(3, Rfc2217Socket::PARITY_EVEN);
        $this->assertSame(4, Rfc2217Socket::PARITY_MARK);
        $this->assertSame(5, Rfc2217Socket::PARITY_SPACE);

        // Stop bits constants
        $this->assertSame(1, Rfc2217Socket::STOP_BITS_1);
        $this->assertSame(2, Rfc2217Socket::STOP_BITS_2);
        $this->assertSame(3, Rfc2217Socket::STOP_BITS_1_5);

        // Flow control constants
        $this->assertSame(1, Rfc2217Socket::FLOW_NONE);
        $this->assertSame(2, Rfc2217Socket::FLOW_XON_XOFF);
        $this->assertSame(3, Rfc2217Socket::FLOW_RTS_CTS);
        $this->assertSame(5, Rfc2217Socket::FLOW_DTR_DSR);

        // Defaults
        $this->assertSame(2.0, Rfc2217Socket::DEFAULT_CONNECTION_TIMEOUT);
        $this->assertSame(2.0, Rfc2217Socket::DEFAULT_WRITE_TIMEOUT);
        $this->assertSame(9600, Rfc2217Socket::DEFAULT_BAUD_RATE);
        $this->assertSame(8, Rfc2217Socket::DEFAULT_DATA_BITS);
        $this->assertSame(Rfc2217Socket::PARITY_NONE, Rfc2217Socket::DEFAULT_PARITY);
        $this->assertSame(Rfc2217Socket::STOP_BITS_1, Rfc2217Socket::DEFAULT_STOP_BITS);
        $this->assertSame(Rfc2217Socket::FLOW_NONE, Rfc2217Socket::DEFAULT_FLOW_CONTROL);
    }

    /**
     * Test setDataBits with all edge cases.
     */
    public function testSetDataBitsEdgeCases(): void
    {
        $socket = new Rfc2217Socket('127.0.0.1', 2001);

        // Valid edge cases
        $socket->setDataBits(5);
        $socket->setDataBits(8);

        // Invalid edge cases
        $this->expectException(InvalidValueException::class);
        $socket->setDataBits(4);  // Just below minimum
    }

    /**
     * Test multiple successive calls to setters.
     */
    public function testMultipleSuccessiveSetterCalls(): void
    {
        $socket = new Rfc2217Socket('127.0.0.1', 2001);

        // Should not throw
        $socket->setBaudRate(300);
        $socket->setBaudRate(1200);
        $socket->setBaudRate(115200);

        $socket->setDataBits(5);
        $socket->setDataBits(8);

        $socket->setParity(Rfc2217Socket::PARITY_NONE);
        $socket->setParity(Rfc2217Socket::PARITY_EVEN);

        $socket->setStopBits(Rfc2217Socket::STOP_BITS_1);
        $socket->setStopBits(Rfc2217Socket::STOP_BITS_2);

        $socket->setFlowControl(Rfc2217Socket::FLOW_NONE);
        $socket->setFlowControl(Rfc2217Socket::FLOW_RTS_CTS);

        $this->assertFalse($socket->isOpen());
    }
}
