<?php

declare(strict_types=1);

namespace Tests\GregorJ\SerialPort\Commands;

use GregorJ\SerialPort\Commands\BasicVoidCommand;
use GregorJ\SerialPort\Exceptions\InvalidParamException;
use GregorJ\SerialPort\Exceptions\UnexpectedResponseException;
use GregorJ\SerialPort\Interfaces\Communication;
use PHPUnit\Framework\TestCase;

final class BasicVoidCommandTest extends TestCase
{
    public function testInvokeSetsTimeoutAndQueriesCommunicationAndReturnsNullOnEmptyResponse(): void
    {
        $communication = $this->createMock(Communication::class);

        $command = 'AT';
        $writeTerminator = "\r\n";
        $readTerminator = "\n";
        $timeout = 1.5;

        $communication
            ->expects(self::once())
            ->method('setTimeout')
            ->with($timeout);

        $communication
            ->expects(self::once())
            ->method('query')
            ->with($command, $writeTerminator, $readTerminator)
            ->willReturn('');

        $sut = new BasicVoidCommand($command, $writeTerminator, $readTerminator, $timeout);

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
            ->method('query')
            ->with('AT', '', '')
            ->willReturn('');

        $sut = new BasicVoidCommand('AT');

        self::assertNull($sut->invoke($communication));
    }

    public function testInvokeTrimsReadTerminatorAndAcceptsPureTerminatorResponse(): void
    {
        $communication = $this->createMock(Communication::class);

        $communication
            ->expects(self::once())
            ->method('setTimeout')
            ->with(BasicVoidCommand::DEFAULT_TIMEOUT);

        $communication
            ->expects(self::once())
            ->method('query')
            ->with('AT', '', "\r\n")
            ->willReturn("\r\n");

        $sut = new BasicVoidCommand('AT', '', "\r\n");

        self::assertNull($sut->invoke($communication));
    }

    public function testInvokeThrowsUnexpectedResponseExceptionWhenUnexpectedCharactersAreReturned(): void
    {
        $communication = $this->createMock(Communication::class);

        $communication
            ->expects(self::once())
            ->method('setTimeout')
            ->with(BasicVoidCommand::DEFAULT_TIMEOUT);

        $communication
            ->expects(self::once())
            ->method('query')
            ->with('AT', '', '')
            ->willReturn('ERROR');

        $sut = new BasicVoidCommand('AT');

        $this->expectException(UnexpectedResponseException::class);
        $this->expectExceptionMessage('Unexpected characters in response "ERROR"');

        $sut->invoke($communication);
    }

    public function testInvokeThrowsUnexpectedResponseExceptionWhenCharactersExistBeforeReadTerminator(): void
    {
        $communication = $this->createMock(Communication::class);

        $communication
            ->expects(self::once())
            ->method('setTimeout')
            ->with(BasicVoidCommand::DEFAULT_TIMEOUT);

        $communication
            ->expects(self::once())
            ->method('query')
            ->with('AT', '', "\n")
            ->willReturn("X\n");

        $sut = new BasicVoidCommand('AT', '', "\n");

        $this->expectException(UnexpectedResponseException::class);
        $this->expectExceptionMessage('Unexpected characters in response "X"');

        $sut->invoke($communication);
    }

    public function testConstructorThrowsInvalidParamExceptionForNegativeTimeout(): void
    {
        $this->expectException(InvalidParamException::class);
        $this->expectExceptionMessage('The response timeout for BasicStringCommand has to be positive.');

        new BasicVoidCommand('AT', '', '', -0.1);
    }

    public function testToStringContainsCommandAndWriteTerminator(): void
    {
        $sut = new BasicVoidCommand('AT', "\r\n");

        self::assertSame('AT\\r\\n', (string) $sut);
    }
}
