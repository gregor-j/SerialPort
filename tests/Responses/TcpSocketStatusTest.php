<?php

declare(strict_types=1);

namespace Tests\GregorJ\SerialPort\Responses;

use GregorJ\SerialPort\Exceptions\NotFoundException;
use GregorJ\SerialPort\Exceptions\UnexpectedResponseException;
use GregorJ\SerialPort\Responses\TcpSocketStatus;
use PHPUnit\Framework\TestCase;

final class TcpSocketStatusTest extends TestCase
{
    /**
     * Test TcpSocketStatus methods.
     * @return void
     * @throws NotFoundException
     * @throws UnexpectedResponseException
     */
    public function testTcpStreamStatus(): void
    {
        $status = new TcpSocketStatus(['timed_out' => false, 'blocked' => false, 'eof' => false, 'unread_bytes' => 0, 'stream_type' => 'lalala', 'mode' => 'hahaha', 'seekable' => false]);
        //assert has() method
        $this->assertTrue($status->has(TcpSocketStatus::TIMED_OUT));
        $this->assertTrue($status->has(TcpSocketStatus::BLOCKED));
        $this->assertTrue($status->has(TcpSocketStatus::EOF));
        $this->assertTrue($status->has(TcpSocketStatus::UNREAD_BYTES));
        $this->assertTrue($status->has(TcpSocketStatus::STREAM_TYPE));
        $this->assertTrue($status->has(TcpSocketStatus::MODE));
        $this->assertTrue($status->has(TcpSocketStatus::SEEKABLE));
        //assert that status has not an arbitrary field
        $this->assertFalse($status->has('foo'));
        //assert get() method
        $this->assertSame(false, $status->get(TcpSocketStatus::TIMED_OUT));
        $this->assertSame(false, $status->get(TcpSocketStatus::BLOCKED));
        $this->assertSame(false, $status->get(TcpSocketStatus::EOF));
        $this->assertSame(0, $status->get(TcpSocketStatus::UNREAD_BYTES));
        $this->assertSame('lalala', $status->get(TcpSocketStatus::STREAM_TYPE));
        $this->assertSame('hahaha', $status->get(TcpSocketStatus::MODE));
        $this->assertSame(false, $status->get(TcpSocketStatus::SEEKABLE));
        //assert field specific methods
        $this->assertSame(false, $status->timedOut());
        $this->assertSame(false, $status->blocked());
        $this->assertSame(false, $status->eof());
        $this->assertSame(0, $status->unreadBytes());
        $this->assertSame('lalala', $status->streamType());
        $this->assertSame('hahaha', $status->mode());
        $this->assertSame(false, $status->seekable());
        //assert __toString() method
        $this->assertSame('[timed out: false] [blocked: false] [EOF: false] [unread_bytes: 0] [stream type: "lalala"] [mode: hahaha] [seekable: false]', (string)$status);
    }

    /**
     * Test that get() throws a NotFoundException.
     * @return void
     * @throws NotFoundException
     * @throws UnexpectedResponseException
     */
    public function testGetNotFoundException(): void
    {
        $status = new TcpSocketStatus(['timed_out' => false, 'blocked' => false, 'eof' => false, 'unread_bytes' => 0, 'stream_type' => 'lalala', 'mode' => 'hahaha', 'seekable' => false]);
        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Metadata "lalala" does not exist.');
        $status->get('lalala');
    }

    /**
     * Test UnexpectedResponseException in TcpSocketStatus constructor.
     * @return void
     * @throws UnexpectedResponseException
     */
    public function testConstructorUnexpectedResponseException(): void
    {
        $this->expectException(UnexpectedResponseException::class);
        $this->expectExceptionMessage('Missing "timed_out" in stream_get_meta_data() return value.');
        new TcpSocketStatus(['blocked' => false, 'eof' => false, 'unread_bytes' => 0, 'stream_type' => 'lalala', 'mode' => 'hahaha', 'seekable' => false]);
    }

    /**
     * Test getting the raw uninterpreted response.
     * @return void
     * @throws UnexpectedResponseException
     */
    public function testGetRawResponse(): void
    {
        $status = new TcpSocketStatus(['timed_out' => false, 'blocked' => false, 'eof' => false, 'unread_bytes' => 0, 'stream_type' => 'lalala', 'mode' => 'hahaha', 'seekable' => false]);
        $this->assertSame(['timed_out' => false, 'blocked' => false, 'eof' => false, 'unread_bytes' => 0, 'stream_type' => 'lalala', 'mode' => 'hahaha', 'seekable' => false], $status->getRawResponse());
    }
}
