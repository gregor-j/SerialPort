<?php

declare(strict_types=1);

namespace Tests\GregorJ\SerialPort\Commands;

use GregorJ\SerialPort\Commands\BasicVoidCommand;
use GregorJ\SerialPort\Exceptions\InvalidParamException;
use GregorJ\SerialPort\Interfaces\Communication;
use PHPUnit\Framework\TestCase;

final class BasicVoidCommandTest extends TestCase
{
    public function testInvokeSetsTimeoutAndWritesToCommunicationAndReturnsNull(): void
    {
        $communication = $this->createMock(Communication::class);

        $command = 'AT';
        $writeTerminator = "\r\n";
        $timeout = 1.5;

        $communication
            ->expects(self::once())
            ->method('setTimeout')
            ->with($timeout);

        $communication
            ->expects(self::once())
            ->method('write')
            ->with($command, $writeTerminator);

        $sut = new BasicVoidCommand($command, $writeTerminator, $timeout);

        self::assertNull($sut->invoke($communication));
    }

    public function testInvokeUsesDefaultTimeoutWhenNotProvided(): void
    {
        $communication = $this->createMock(Communication::class);

        $communication
            ->expects(self::once())
            ->method('setTimeout')
            ->with(BasicVoidCommand::DEFAULT_TIMEOUT);

        $communication
            ->expects(self::once())
            ->method('write')
            ->with('AT', '');

        $communication
            ->expects(self::never())
            ->method('query');

        $sut = new BasicVoidCommand('AT');

        self::assertNull($sut->invoke($communication));
    }

    public function testInvokeNeverCallsQuery(): void
    {
        $communication = $this->createMock(Communication::class);

        $communication
            ->expects(self::once())
            ->method('setTimeout')
            ->with(BasicVoidCommand::DEFAULT_TIMEOUT);

        $communication
            ->expects(self::once())
            ->method('write')
            ->with('AT', "\r\n");

        $communication
            ->expects(self::never())
            ->method('query');

        $sut = new BasicVoidCommand('AT', "\r\n");

        self::assertNull($sut->invoke($communication));
    }

    public function testConstructorThrowsInvalidParamExceptionForNegativeTimeout(): void
    {
        $this->expectException(InvalidParamException::class);
        $this->expectExceptionMessage('The response timeout for BasicStringCommand has to be positive.');

        new BasicVoidCommand('AT', '', -0.1);
    }

    public function testToStringContainsCommandAndWriteTerminator(): void
    {
        $sut = new BasicVoidCommand('AT', "\r\n");

        self::assertSame('AT\\r\\n', (string) $sut);
    }
}
