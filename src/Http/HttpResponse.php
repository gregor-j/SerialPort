<?php

declare(strict_types=1);

namespace GregorJ\SerialPort\Http;

use GregorJ\SerialPort\Interfaces\Http\HttpResponseInterface;

/**
 * Value object containing HTTP response data required by HttpCommunication.
 */
final class HttpResponse implements HttpResponseInterface
{
    /**
     * @param int $statusCode
     * @param string $body
     * @param array<string, string> $headers
     */
    public function __construct(
        readonly int $statusCode,
        readonly string $body,
        readonly array $headers = []
    ) {
    }

    /**
     * @return int
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return string
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }
}
