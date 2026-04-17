<?php

declare(strict_types=1);

namespace GregorJ\SerialPort\Interfaces\Http;

/**
 * Interface describing an HTTP response.
 */
interface HttpResponseInterface
{
    /**
     * Get the HTTP status code.
     * @return int
     */
    public function getStatusCode(): int;

    /**
     * Get the HTTP body.
     * @return string
     */
    public function getBody(): string;

    /**
     * Get the HTTP headers.
     * @return array<string, string>
     */
    public function getHeaders(): array;
}
