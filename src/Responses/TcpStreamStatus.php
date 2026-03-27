<?php

declare(strict_types=1);

namespace GregorJ\SerialPort\Responses;

use GregorJ\SerialPort\Exceptions\NotFoundException;
use GregorJ\SerialPort\Interfaces\Communication\Response;

/**
 * Interpret the return value of stream_get_meta_data().
 */
final class TcpStreamStatus implements Response
{
    public const TIMED_OUT = 'timed_out';
    public const BLOCKED = 'blocked';
    public const EOF = 'eof';
    public const UNREAD_BYTES = 'unread_bytes';
    public const STREAM_TYPE = 'stream_type';
    public const WRAPPER_TYPE = 'wrapper_type';
    public const WRAPPER_DATA = 'wrapper_data';
    public const MODE = 'mode';
    public const SEEKABLE = 'seekable';
    public const URI = 'uri';
    public const CRYPTO = 'crypto';

    /**
     * @var non-empty-array<string, mixed>
     */
    private array $metadata;

    /**
     * @var mixed
     */
    private mixed $wrapperData;

    /**
     * @var mixed|null
     */
    private mixed $crypto;

    /**
     * Construct the instance with the return value of stream_get_meta_data().
     * @param array<string, mixed> $metadata
     */
    public function __construct(array $metadata)
    {
        //extract everything but wrapper_data and crypto.
        $this->metadata = [
            self::TIMED_OUT => $metadata[self::TIMED_OUT],
            self::BLOCKED => $metadata[self::BLOCKED],
            self::EOF => $metadata[self::EOF],
            self::UNREAD_BYTES => $metadata[self::UNREAD_BYTES],
            self::STREAM_TYPE => $metadata[self::STREAM_TYPE],
            self::WRAPPER_TYPE => $metadata[self::WRAPPER_TYPE],
            self::MODE => $metadata[self::MODE],
            self::SEEKABLE => $metadata[self::SEEKABLE],
            self::URI => $metadata[self::URI],
        ];
        $this->wrapperData = $metadata[self::WRAPPER_DATA];
        $this->crypto = $metadata[self::CRYPTO] ?? null;
    }

    /**
     * @inheritDoc
     */
    public function get(string $name): float|bool|int|string
    {
        if (!$this->has($name)) {
            throw new NotFoundException(sprintf('Metadata "%s" does not exist.', $name));
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
     * true if the stream timed out while waiting for data on the last call to fread() or fgets().
     * @return bool
     */
    public function timedOut(): bool
    {
        return (bool)$this->metadata[self::TIMED_OUT];
    }

    /**
     * true if the stream is in blocking IO mode.
     * See stream_set_blocking():
     * @link https://www.php.net/manual/en/function.stream-set-blocking.php
     * @return bool
     */
    public function blocked(): bool
    {
        return (bool)$this->metadata[self::BLOCKED];
    }

    /**
     * true if the stream has reached end-of-file. Note that for socket streams this member can be true even when
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
     * A label describing the protocol wrapper implementation layered over the stream.
     * See Supported Protocols and Wrappers for more information about wrappers:
     * @link https://www.php.net/manual/en/wrappers.php
     * @return string
     */
    public function wrapperType(): string
    {
        // @phpstan-ignore cast.string
        return (string)$this->metadata[self::WRAPPER_TYPE];
    }

    /**
     * Wrapper specific data attached to this stream.
     * See Supported Protocols and Wrappers for more information about wrappers and their wrapper data:
     * @link https://www.php.net/manual/en/wrappers.php
     * @return mixed
     */
    public function wrapperData(): mixed
    {
        return $this->wrapperData;
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
     * The URI/filename associated with this stream.
     * @return string
     */
    public function uri(): string
    {
        // @phpstan-ignore cast.string
        return (string)$this->metadata[self::URI];
    }

    /**
     * The TLS connection metadata for this stream.
     * Note: Only provided when the resource's stream uses TLS.
     * @return array<mixed, mixed>|null
     * @noinspection PhpPluralMixedCanBeReplacedWithArrayInspection
     */
    public function crypto(): ?array
    {
        return is_array($this->crypto) ? $this->crypto : null;
    }

    /**
     * @inheritDoc
     */
    public function __toString(): string
    {
        return sprintf(
            '[timed out: %s] [blocked: %s] [EOF: %s] [unread_bytes: %u] [stream type: "%s"] [wrapper type: "%s"] [mode: %s] [seekable: %s] [uri: %s]',
            $this->timedOut() ? 'true' : 'false',
            $this->blocked() ? 'true' : 'false',
            $this->eof() ? 'true' : 'false',
            $this->unreadBytes(),
            $this->streamType(),
            $this->wrapperType(),
            $this->mode(),
            $this->seekable() ? 'true' : 'false',
            $this->uri(),
        );
    }
}
