<?php

declare(strict_types=1);

namespace GregorJ\SerialPort\Interfaces\Http;

/**
 * Abstraction for HTTP stream operations used by HttpTransport implementations.
 */
interface HttpStreamIo
{
    /**
     * Create a stream context with options.
     * @param array<string, mixed> $options
     * @return resource
     */
    public function createStreamContext(array $options);

    /**
     * Fetch data from a URL using the provided context.
     * @param string $url
     * @param resource $context
     * @return string|false
     */
    public function getContents(string $url, $context): string|false;

    /**
     * Return raw response headers from the last HTTP request.
     *
     * Index 0 contains the HTTP status line (e.g. HTTP/1.1 200 OK).
     *
     * @return array<int, string>
     */
    public function getResponseHeaders(): array;
}
