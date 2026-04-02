<?php

declare(strict_types=1);

namespace GregorJ\SerialPort\Responses;

use GregorJ\SerialPort\Exceptions\NotFoundException;
use GregorJ\SerialPort\Exceptions\UnexpectedResponseException;
use GregorJ\SerialPort\Interfaces\Response;
use kbATeam\ByteDebug\ToString;

use function array_key_exists;
use function sprintf;

/**
 * Interpret the return value of stream_get_meta_data().
 */
final class TcpSocketStatus implements Response
{
    public const TIMED_OUT = 'timed_out';
    public const BLOCKED = 'blocked';
    public const EOF = 'eof';
    public const UNREAD_BYTES = 'unread_bytes';
    public const STREAM_TYPE = 'stream_type';
    //public const WRAPPER_TYPE = 'wrapper_type'; // not present for TCP sockets
    //public const WRAPPER_DATA = 'wrapper_data'; // not present for TCP sockets
    public const MODE = 'mode';
    public const SEEKABLE = 'seekable';
    //public const URI = 'uri'; // not present for TCP sockets
    //public const CRYPTO = 'crypto'; //not present for TCP sockets

    /**
     * @var non-empty-array<string, mixed>
     */
    private array $metadata;

    /**
     * The raw uninterpreted response.
     * @var array<string, mixed>
     */
    private array $rawResponse;

    /**
     * Construct the instance with the return value of stream_get_meta_data().
     * @param array<string, mixed> $metadata
     * @throws UnexpectedResponseException
     */
    public function __construct(array $metadata)
    {
        $this->rawResponse = $metadata;
        // wrapper_type, wrapper_data, uri, and crypto are not part of the metadata for TCP sockets.
        $keys = [self::TIMED_OUT, self::BLOCKED, self::EOF, self::UNREAD_BYTES, self::STREAM_TYPE, self::MODE, self::SEEKABLE];
        foreach ($keys as $key) {
            if (!array_key_exists($key, $metadata)) {
                throw new UnexpectedResponseException(sprintf('Missing "%s" in stream_get_meta_data() return value.', $key), 0, null, $metadata);
            }
            $this->metadata[$key] = $metadata[$key];
        }
    }

    /**
     * @inheritDoc
     */
    public function get(string $name): mixed
    {
        if (!$this->has($name)) {
            throw new NotFoundException(sprintf('TcpSocketStatus metadata "%s" not found.', ToString::fromString($name)));
        }
        return $this->metadata[$name];
    }

    /**
     * @inheritDoc
     */
    public function has(string $name): bool
    {
        return array_key_exists($name, $this->metadata);
    }

    /**
     * true if the connection timed out while waiting for data on the last call to fread() or fgets().
     * @return bool
     */
    public function timedOut(): bool
    {
        return (bool)$this->metadata[self::TIMED_OUT];
    }

    /**
     * true if the socket is in blocking IO mode.
     * See stream_set_blocking():
     * @link https://www.php.net/manual/en/function.stream-set-blocking.php
     * @return bool
     */
    public function blocked(): bool
    {
        return (bool)$this->metadata[self::BLOCKED];
    }

    /**
     * true if the connection has reached end-of-file (EOF). Note that for sockets this member can be true even when
     * unread_bytes is non-zero. To determine if there is more data to be read, use feof() instead of reading this item.
     * @return bool
     */
    public function eof(): bool
    {
        return (bool)$this->metadata[self::EOF];
    }

    /**
     * The number of bytes currently contained in the PHP's own internal buffer.
     * Note: You shouldn't use this value in a script.
     * @return int
     */
    public function unreadBytes(): int
    {
        // @phpstan-ignore cast.int
        return (int)$this->metadata[self::UNREAD_BYTES];
    }

    /**
     * A label describing the underlying implementation of the stream.
     * @return string
     */
    public function streamType(): string
    {
        // @phpstan-ignore cast.string
        return (string)$this->metadata[self::STREAM_TYPE];
    }


    /**
     * The type of access required for this stream.
     * See Table 1 of the fopen() reference:
     * @link https://www.php.net/manual/en/function.fopen.php
     * @return string
     */
    public function mode(): string
    {
        // @phpstan-ignore cast.string
        return (string)$this->metadata[self::MODE];
    }

    /**
     * Whether the current stream can be seeked.
     * @return bool
     */
    public function seekable(): bool
    {
        return (bool)$this->metadata[self::SEEKABLE];
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return sprintf(
            '[timed out: %s] [blocked: %s] [EOF: %s] [unread_bytes: %u] [stream type: %s] [mode: %s] [seekable: %s]',
            $this->timedOut() ? 'true' : 'false',
            $this->blocked() ? 'true' : 'false',
            $this->eof() ? 'true' : 'false',
            $this->unreadBytes(),
            $this->streamType(),
            $this->mode(),
            $this->seekable() ? 'true' : 'false'
        );
    }

    /**
     * Get the raw uninterpreted response for debugging.
     * @return array<string, mixed>
     */
    public function getRawResponse(): array
    {
        return $this->rawResponse;
    }
}
