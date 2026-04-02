<?php

declare(strict_types=1);

namespace Tests\GregorJ\SerialPort\Commands;

use GregorJ\SerialPort\Commands\BasicCommand;
use GregorJ\SerialPort\Exceptions\InvalidValueException;
use GregorJ\SerialPort\Exceptions\ReadException;
use GregorJ\SerialPort\Exceptions\TimeoutException;
use GregorJ\SerialPort\Exceptions\UnexpectedResponseException;
use GregorJ\SerialPort\Exceptions\WriteException;
use GregorJ\SerialPort\Interfaces\Communication;
use PHPUnit\Framework\TestCase;

/**
 * Test the BasicCommand class.
 */
class BasicCommandTest extends TestCase
{
    /**
     * Test BasicCommand class.
     * @return void
     * @throws InvalidValueException
     * @throws ReadException
     * @throws TimeoutException
     * @throws UnexpectedResponseException
     * @throws WriteException
     */
    public function testBasicCommand(): void
    {
        $com = $this->getMockBuilder(Communication::class)->getMock();
        $com->expects(static::once())
            ->method('setTimeout')
            ->with(1.0);
        $com->expects(static::once())
            ->method('write')
            ->with('HELLO', "\n");
        $com->expects(static::once())
            ->method('read')
            ->with("\r")
            ->willReturn("WORLD\r");
        $command = new BasicCommand('HELLO', "\n", "\r", 1.0);
        $this->assertSame('HELLO\n', (string)$command);
        $response = $command->invoke($com);
        $this->assertSame("WORLD\r", $response->getRawResponse());
        static::assertSame('WORLD', (string)$response);
    }

    /**
     * Constructor must reject negative timeout values.
     */
    public function testConstructorWithNegativeTimeout(): void
    {
        $this->expectException(InvalidValueException::class);
        $this->expectExceptionMessage('The response timeout for BasicCommand has to be positive.');

        new BasicCommand('HELLO', "\n", "\r", -1.0);
    }
}
