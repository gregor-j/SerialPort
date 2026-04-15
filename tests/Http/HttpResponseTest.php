<?php

declare(strict_types=1);

namespace Tests\GregorJ\SerialPort\Http;

use GregorJ\SerialPort\Http\HttpResponse;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the HttpResponse class.
 */
final class HttpResponseTest extends TestCase
{
    /**
     * Test constructor with all parameters.
     */
    public function testConstructorWithAllParameters(): void
    {
        $statusCode = 200;
        $body = 'Test body content';
        $headers = ['Content-Type' => 'text/plain', 'X-Custom-Header' => 'value'];

        $response = new HttpResponse($statusCode, $body, $headers);

        $this->assertInstanceOf(HttpResponse::class, $response);
        $this->assertSame($statusCode, $response->getStatusCode());
        $this->assertSame($body, $response->getBody());
        $this->assertSame($headers, $response->getHeaders());
    }

    /**
     * Test constructor with default headers parameter.
     */
    public function testConstructorWithoutHeaders(): void
    {
        $statusCode = 404;
        $body = 'Not found';

        $response = new HttpResponse($statusCode, $body);

        $this->assertSame($statusCode, $response->getStatusCode());
        $this->assertSame($body, $response->getBody());
        $this->assertSame([], $response->getHeaders());
    }

    /**
     * Test getStatusCode method.
     */
    public function testGetStatusCode(): void
    {
        $statusCode = 201;
        $response = new HttpResponse($statusCode, 'Created');

        $this->assertSame($statusCode, $response->getStatusCode());
    }

    /**
     * Test getBody method.
     */
    public function testGetBody(): void
    {
        $body = 'Response body with special characters: äöü';
        $response = new HttpResponse(200, $body);

        $this->assertSame($body, $response->getBody());
    }

    /**
     * Test getHeaders method returns correct structure.
     */
    public function testGetHeaders(): void
    {
        $headers = [
            'Content-Type' => 'application/json',
            'Authorization' => 'Bearer token123',
            'X-Custom-Header' => 'custom-value',
        ];

        $response = new HttpResponse(200, '{}', $headers);

        $this->assertSame($headers, $response->getHeaders());
    }

    /**
     * Test with different HTTP status codes.
     */
    public function testVariousStatusCodes(): void
    {
        $statusCodes = [100, 200, 201, 204, 301, 400, 401, 403, 404, 500, 502, 503];

        foreach ($statusCodes as $code) {
            $response = new HttpResponse($code, 'Body for ' . $code);
            $this->assertSame($code, $response->getStatusCode());
        }
    }

    /**
     * Test with empty body.
     */
    public function testEmptyBody(): void
    {
        $response = new HttpResponse(204, '');

        $this->assertSame('', $response->getBody());
    }

    /**
     * Test with empty headers.
     */
    public function testEmptyHeaders(): void
    {
        $response = new HttpResponse(200, 'body', []);

        $this->assertSame([], $response->getHeaders());
    }

    /**
     * Test with multiline body content.
     */
    public function testMultilineBody(): void
    {
        $body = "Line 1\nLine 2\nLine 3";
        $response = new HttpResponse(200, $body);

        $this->assertSame($body, $response->getBody());
    }

    /**
     * Test with large number of headers.
     */
    public function testManyHeaders(): void
    {
        $headers = [];
        for ($i = 1; $i <= 10; $i++) {
            $headers["X-Header-" . $i] = "value-" . $i;
        }

        $response = new HttpResponse(200, 'body', $headers);

        $this->assertCount(10, $response->getHeaders());
        $this->assertSame($headers, $response->getHeaders());
    }

    /**
     * Test with JSON body.
     */
    public function testJsonBody(): void
    {
        $jsonBody = '{"status":"ok","data":{"id":123,"name":"test"}}';
        $response = new HttpResponse(200, $jsonBody, ['Content-Type' => 'application/json']);

        $this->assertSame($jsonBody, $response->getBody());
        $this->assertSame('application/json', $response->getHeaders()['Content-Type']);
    }

    /**
     * Test that response values can be accessed multiple times.
     */
    public function testMultipleAccessToValues(): void
    {
        $statusCode = 200;
        $body = 'test';
        $headers = ['test' => 'header'];

        $response = new HttpResponse($statusCode, $body, $headers);

        $this->assertSame($statusCode, $response->getStatusCode());
        $this->assertSame($statusCode, $response->getStatusCode());
        $this->assertSame($body, $response->getBody());
        $this->assertSame($body, $response->getBody());
        $this->assertSame($headers, $response->getHeaders());
        $this->assertSame($headers, $response->getHeaders());
    }
}
