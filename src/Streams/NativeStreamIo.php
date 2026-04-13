<?php

declare(strict_types=1);

namespace GregorJ\SerialPort\Streams;

use GregorJ\SerialPort\Interfaces\StreamIo;

use function fclose;
use function fgetc;
use function fwrite;
use function stream_get_meta_data;
use function stream_set_blocking;
use function stream_set_timeout;

/**
 * Native IO operations on sockets.
 */
final class NativeStreamIo implements StreamIo
{
    /**
     * Closes an open stream.
     * @link https://php.net/manual/en/function.fclose.php
     * @param resource $socket The stream must be valid, and must point to a resource successfully opened by fsockopen.
     * @return bool true on success or false on failure.
     */
    public function close($socket): bool
    {
        return fclose($socket);
    }

    /**
     * Binary-safe stream write.
     * @link https://php.net/manual/en/function.fwrite.php
     * @param resource $socket The stream must be valid, and must point to a resource successfully opened by fsockopen.
     * @param string $string The string that is to be written.
     * @param int<0, max>|null $length [optional] If the length argument is given, writing will stop after length bytes have
     * been written or the end of string is reached, whichever comes first.
     * Note that if the length argument is given, then the magic_quotes_runtime configuration option will be ignored
     * and no slashes will be stripped from string.
     * @return int|false the number of bytes written, or FALSE on error.
     */
    public function write($socket, string $string, int|null $length): int|false
    {
        return fwrite($socket, $string, $length);
    }

    /**
     * Gets a character from the stream.
     * @link https://php.net/manual/en/function.fgetc.php
     * @param resource $socket The stream must be valid, and must point to a resource successfully opened by fsockopen.
     * @return string|false a string containing a single character read from the stream pointed to by handle. Returns
     * false on EOF.
     */
    public function readChar($socket): string|false
    {
        return fgetc($socket);
    }

    /**
     * Set timeout period on a stream.
     * @link https://php.net/manual/en/function.stream-set-timeout.php
     * @param resource $socket The stream must be valid, and must point to a resource successfully opened by fsockopen.
     * @param int $seconds The seconds part of the timeout to be set.
     * @param int $microseconds The microseconds part of the timeout to be set.
     * @return bool true on success or false on failure.
     */
    public function setTimeout($socket, int $seconds, int $microseconds): bool
    {
        return stream_set_timeout($socket, $seconds, $microseconds);
    }

    /**
     * Set blocking/non-blocking mode on a stream
     * @link https://php.net/manual/en/function.stream-set-blocking.php
     * @param resource $socket The stream must be valid, and must point to a resource successfully opened by fsockopen.
     * @param bool $enable If mode is FALSE, the given stream will be switched to non-blocking mode, and if TRUE, it
     * will be switched to blocking mode. This affects calls like fgets and fread that read from the stream. In
     * non-blocking mode an fgets call will always return right away while in blocking mode it will wait for data to
     * become available on the stream.
     * @return bool true on success or false on failure.
     */
    public function setBlocking($socket, bool $enable): bool
    {
        return stream_set_blocking($socket, $enable);
    }

    /**
     * Retrieves metadata from streams
     * @link https://php.net/manual/en/function.stream-get-meta-data.php
     * @param resource $socket The stream must be valid, and must point to a resource successfully opened by fsockopen.
     * @return array<string, mixed> The result array contains the following items:
     * <p>
     * timed_out (bool) - true if the stream
     * timed out while waiting for data on the last call to
     * fread or fgets.
     * </p>
     * <p>
     * blocked (bool) - true if the stream is
     * in blocking IO mode. See stream_set_blocking.
     * </p>
     * <p>
     * eof (bool) - true if the stream has reached
     * end-of-file. Note that for socket streams this member can be true
     * even when unread_bytes is non-zero. To
     * determine if there is more data to be read, use
     * feof instead of reading this item.
     * </p>
     * <p>
     * unread_bytes (int) - the number of bytes
     * currently contained in the PHP's own internal buffer.
     * </p>
     * You shouldn't use this value in a script.
     * <p>
     * stream_type (string) - a label describing
     * the underlying implementation of the stream.
     * </p>
     * <p>
     * wrapper_type (string) - a label describing
     * the protocol wrapper implementation layered over the stream.
     * See for more information about wrappers.
     * </p>
     * <p>
     * wrapper_data (mixed) - wrapper specific
     * data attached to this stream. See for
     * more information about wrappers and their wrapper data.
     * </p>
     * <p>
     * filters (array) - and array containing
     * the names of any filters that have been stacked onto this stream.
     * Documentation on filters can be found in the
     * Filters appendix.
     * </p>
     * <p>
     * mode (string) - the type of access required for
     * this stream (see Table 1 of the fopen() reference)
     * </p>
     * <p>
     * seekable (bool) - whether the current stream can
     * be seeked.
     * </p>
     * <p>
     * uri (string) - the URI/filename associated with this
     * stream.
     * </p>
     */
    public function getMetadata($socket): array
    {
        return stream_get_meta_data($socket);
    }
}
