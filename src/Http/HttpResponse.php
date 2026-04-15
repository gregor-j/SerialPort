<?php

declare(strict_types=1);

namespace GregorJ\SerialPort\Http;

/**
 * Value object containing HTTP response data required by HttpCommunication.
 */
final class HttpResponse
{
    private int $statusCode;
    private string $body;

    /**
     * @var array<string, string>
     */
    private array $headers;

    /**
     * @param int $statusCode
     * @param string $body
     * @param array<string, string> $headers
     */
    public function __construct(int $statusCode, string $body, array $headers = [])
    {
        $this->statusCode = $statusCode;
        $this->body = $body;
        $this->headers = $headers;
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
