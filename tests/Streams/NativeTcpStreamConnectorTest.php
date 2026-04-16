<?php

declare(strict_types=1);

namespace Tests\GregorJ\SerialPort\Streams;

use GregorJ\SerialPort\Interfaces\Stream\TcpStreamConnector;
use GregorJ\SerialPort\Streams\NativeTcpStreamConnector;
use Tests\GregorJ\SerialPort\LocalTcpServer;
use PHPUnit\Framework\TestCase;

use function fclose;
use function is_resource;

/**
 * Unit tests for NativeTcpStreamConnector.
 */
final class NativeTcpStreamConnectorTest extends TestCase
{
    private NativeTcpStreamConnector $connector;

    protected function setUp(): void
    {
        $this->connector = new NativeTcpStreamConnector();
    }

    public function testImplementsTcpStreamConnectorInterface(): void
    {
        /** @noinspection PhpConditionAlreadyCheckedInspection */
        $this->assertInstanceOf(TcpStreamConnector::class, $this->connector);
    }

    public function testConnectToLocalEchoServerReturnsResource(): void
    {
        $server = new LocalTcpServer();
        $errorCode = 0;
        $errorMessage = '';

        $socket = $this->connector->connect('127.0.0.1', $server->getTcpPort(), $errorCode, $errorMessage, 2.0);

        $this->assertIsResource($socket);
        $this->assertSame(0, $errorCode);
        $this->assertSame('', $errorMessage);

        if (is_resource($socket)) {
            fclose($socket);
        }
    }

    public function testConnectToRefusedPortReturnsFalseAndSetsErrorCode(): void
    {
        $errorCode = 0;
        $errorMessage = '';

        // Port 1 is reserved and almost certainly refused on a test machine.
        $socket = $this->connector->connect('127.0.0.16', 7777, $errorCode, $errorMessage, 0.5);

        $this->assertFalse($socket);
        $this->assertNotSame(0, $errorCode);
        $this->assertNotSame('', $errorMessage);
    }
}
