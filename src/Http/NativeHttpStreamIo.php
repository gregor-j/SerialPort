<?php

declare(strict_types=1);

namespace GregorJ\SerialPort\Http;

use GregorJ\SerialPort\Interfaces\Http\HttpStreamIo;

use function file_get_contents;
use function stream_context_create;

/**
 * Native HTTP stream operations using PHP built-in functions.
 */
final class NativeHttpStreamIo implements HttpStreamIo
{
    /**
     * Create a stream context
     * @link https://php.net/manual/en/function.stream-context-create.php
     * @param array<string, mixed> $options Must be an associative array of associative arrays in the format $arr['wrapper']['option'] = $value.
     * @return resource A stream context resource.
     */
    public function createStreamContext(array $options)
    {
        return stream_context_create($options);
    }

    /**
     * Reads entire file into a string
     * @link https://php.net/manual/en/function.file-get-contents.php
     * @param string $url Name of the URL to read.
     * @param resource $context A valid context resource created with stream_context_create. If you don't need to use a custom context, you can skip this parameter by null.
     * @return string|false The function returns the read data or false on failure.
     */
    public function getContents(string $url, $context): string|false
    {
        return file_get_contents($url, false, $context);
    }

    /**
     * @return array<int, string>
     */
    public function getResponseHeaders(): array
    {
        /** @phpstan-ignore-next-line variable.undefined */
        return $http_response_header ?? [];
    }
}
