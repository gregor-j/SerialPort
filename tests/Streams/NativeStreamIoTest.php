<?php

declare(strict_types=1);

namespace Tests\GregorJ\SerialPort\Streams;

use GregorJ\SerialPort\Interfaces\Stream\StreamIo;
use GregorJ\SerialPort\Streams\NativeStreamIo;
use Tests\GregorJ\SerialPort\LocalTcpServer;
use PHPUnit\Framework\TestCase;

use function fclose;
use function fopen;
use function fsockopen;
use function fwrite;
use function is_resource;
use function rewind;

/**
 * Unit tests for NativeStreamIo.
 * In-memory php://temp streams cover write/read/close/setBlocking.
 * A real TCP socket (via LocalTcpServer) is required for setTimeout and getMetadata
 * because those operations are only meaningful on network streams.
 */
final class NativeStreamIoTest extends TestCase
{
    private NativeStreamIo $io;
    /** @var resource */
    private $stream;
    /** @var resource|null */
    private $socket = null;
    protected function setUp(): void
    {
        $this->io = new NativeStreamIo();
        $resource = fopen('php://temp', 'r+b');
        $this->assertIsResource($resource);
        $this->stream = $resource;
    }
    protected function tearDown(): void
    {
        if (is_resource($this->stream)) {
            $this->io->close($this->stream);
        }
        if (is_resource($this->socket)) {
            fclose($this->socket);
            $this->socket = null;
        }
    }
    /**
     * Opens a real TCP socket to the local echo server for tests that need a network stream.
     * @return resource
     */
    private function openNetworkSocket(): mixed
    {
        $server = new LocalTcpServer();
        $errno = 0;
        $errstr = '';
        $socket = fsockopen('127.0.0.1', $server->getTcpPort(), $errno, $errstr, 2.0);
        $this->assertIsResource($socket);
        $this->socket = $socket;
        return $socket;
    }
    public function testImplementsStreamIoInterface(): void
    {
        /** @noinspection PhpConditionAlreadyCheckedInspection */
        $this->assertInstanceOf(StreamIo::class, $this->io);
    }
    public function testWriteReturnsNumberOfBytesWritten(): void
    {
        $bytes = $this->io->write($this->stream, 'hello', null);
        $this->assertSame(5, $bytes);
    }
    public function testWriteWithExplicitLengthLimitsOutput(): void
    {
        $bytes = $this->io->write($this->stream, 'hello', 3);
        $this->assertSame(3, $bytes);
    }
    public function testWriteEmptyStringReturnsZero(): void
    {
        $bytes = $this->io->write($this->stream, '', null);
        $this->assertSame(0, $bytes);
    }
    public function testReadCharReturnsOneCharacterAtATime(): void
    {
        fwrite($this->stream, 'AB');
        rewind($this->stream);
        $this->assertSame('A', $this->io->readChar($this->stream));
        $this->assertSame('B', $this->io->readChar($this->stream));
    }
    public function testReadCharReturnsFalseAtEndOfStream(): void
    {
        // Empty stream: reading immediately returns false (EOF).
        $result = $this->io->readChar($this->stream);
        $this->assertFalse($result);
    }
    public function testSetBlockingEnableReturnsTrue(): void
    {
        $result = $this->io->setBlocking($this->stream, true);
        $this->assertTrue($result);
    }
    public function testSetBlockingDisableReturnsTrue(): void
    {
        $result = $this->io->setBlocking($this->stream, false);
        $this->assertTrue($result);
    }
    public function testSetTimeoutReturnsTrueOnNetworkStream(): void
    {
        $socket = $this->openNetworkSocket();
        $this->assertTrue($this->io->setTimeout($socket, 1, 0));
        $this->assertTrue($this->io->setTimeout($socket, 0, 500000));
        $this->assertTrue($this->io->setTimeout($socket, 0, 0));
    }
    public function testGetMetadataReturnsExpectedKeysOnNetworkStream(): void
    {
        $socket = $this->openNetworkSocket();
        $meta = $this->io->getMetadata($socket);
        $this->assertArrayHasKey('timed_out', $meta);
        $this->assertArrayHasKey('blocked', $meta);
        $this->assertArrayHasKey('eof', $meta);
        $this->assertFalse($meta['timed_out']);
    }
    public function testGetMetadataReturnsArrayForMemoryStream(): void
    {
        $meta = $this->io->getMetadata($this->stream);
        $this->assertArrayHasKey('seekable', $meta);
        $this->assertArrayHasKey('uri', $meta);
        $this->assertSame('php://temp', $meta['uri']);
    }
    public function testCloseReturnsTrueAndInvalidatesStream(): void
    {
        $result = $this->io->close($this->stream);
        $this->assertTrue($result);
        $this->assertFalse(is_resource($this->stream));
    }
}
