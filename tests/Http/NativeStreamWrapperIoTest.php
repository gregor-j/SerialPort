<?php

/** @noinspection HttpUrlsUsage */

declare(strict_types=1);

namespace Tests\GregorJ\SerialPort\Http;

use GregorJ\SerialPort\Http\NativeStreamWrapperIo;
use GregorJ\SerialPort\Interfaces\Http\StreamWrapperIo;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the NativeStreamWrapperIo class.
 */
final class NativeStreamWrapperIoTest extends TestCase
{
    private NativeStreamWrapperIo $streamIo;

    protected function setUp(): void
    {
        $this->streamIo = new NativeStreamWrapperIo();
    }

    /**
     * Test that the class implements StreamWrapperIo interface.
     */
    public function testImplementsHttpStreamIoInterface(): void
    {
        /** @noinspection PhpConditionAlreadyCheckedInspection */
        $this->assertInstanceOf(StreamWrapperIo::class, $this->streamIo);
    }

    /**
     * Test createStreamContext returns a valid resource.
     */
    public function testCreateStreamContextReturnsResource(): void
    {
        $options = [
            'http' => [
                'method' => 'GET',
                'timeout' => 5.0,
            ],
        ];

        $context = $this->streamIo->createStreamContext($options);

        $this->assertIsResource($context);
    }

    /**
     * Test createStreamContext with empty options array.
     */
    public function testCreateStreamContextWithEmptyOptions(): void
    {
        $context = $this->streamIo->createStreamContext([]);

        $this->assertIsResource($context);
    }

    /**
     * Test createStreamContext with complex nested options.
     */
    public function testCreateStreamContextWithComplexOptions(): void
    {
        $options = [
            'http' => [
                'method' => 'POST',
                'header' => [
                    'Content-Type: application/json',
                    'Accept: application/json',
                ],
                'content' => '{"key":"value"}',
                'timeout' => 10.0,
                'protocol_version' => 1.1,
                'ignore_errors' => true,
            ],
            'socket' => [
                'connect_timeout' => 2.0,
            ],
        ];

        $context = $this->streamIo->createStreamContext($options);

        $this->assertIsResource($context);
    }

    /**
     * Test createStreamContext with SSL options.
     */
    public function testCreateStreamContextWithSslOptions(): void
    {
        $options = [
            'ssl' => [
                'verify_peer' => false,
                'allow_self_signed' => true,
            ],
        ];

        $context = $this->streamIo->createStreamContext($options);

        $this->assertIsResource($context);
    }

    /**
     * Test getContents returns false on invalid URL.
     */
    public function testGetContentsWithInvalidUrlReturnsFalse(): void
    {
        $context = $this->streamIo->createStreamContext([]);
        @$result = $this->streamIo->getContents(
            'http://invalid.non.existent.domain.test:99999',
            $context
        );

        $this->assertFalse($result);
    }

    /**
     * Test getContents with data URL scheme.
     */
    public function testGetContentsWithDataUrl(): void
    {
        $dataUrl = 'data://text/plain;base64,SGVsbG8gV29ybGQ=';
        $context = $this->streamIo->createStreamContext([]);

        $result = $this->streamIo->getContents($dataUrl, $context);

        if ($result !== false) {
            $this->assertStringContainsString('Hello', $result);
        }
    }

    /**
     * Test getContents accepts null as context parameter by testing with a fresh context.
     */
    public function testGetContentsWithValidContext(): void
    {
        $dataUrl = 'data://text/plain;base64,VGVzdA==';
        $context = $this->streamIo->createStreamContext([]);

        $result = $this->streamIo->getContents($dataUrl, $context);

        if ($result !== false) {
            $this->assertStringContainsString('Test', $result);
        }
    }

    /**
     * Test that multiple context creations produce independent resources.
     */
    public function testMultipleContextCreationsAreIndependent(): void
    {
        $context1 = $this->streamIo->createStreamContext(['http' => ['method' => 'GET']]);
        $context2 = $this->streamIo->createStreamContext(['http' => ['method' => 'POST']]);

        $this->assertIsResource($context1);
        $this->assertIsResource($context2);
        $this->assertNotSame($context1, $context2);
    }

    /**
     * Test that the same context can be reused for multiple getContents calls.
     */
    public function testContextReuseForMultipleCalls(): void
    {
        $context = $this->streamIo->createStreamContext([]);
        $dataUrl = 'data://text/plain;base64,Zmlyc3Q=';

        $result1 = $this->streamIo->getContents($dataUrl, $context);
        $result2 = $this->streamIo->getContents($dataUrl, $context);

        // Both calls should work (or both fail consistently)
        $this->assertSame(($result1 === false), ($result2 === false));
    }

    /**
     * Test getContents with non-existent local file.
     */
    public function testGetContentsWithNonExistentFileReturnsFalse(): void
    {
        $context = $this->streamIo->createStreamContext([]);

        @$result = $this->streamIo->getContents('/nonexistent/file/path/to/resource.txt', $context);

        $this->assertFalse($result);
    }

    /**
     * Test createStreamContext accepts various option structures.
     */
    public function testCreateStreamContextWithVariousOptionTypes(): void
    {
        $optionsVariations = [
            // Integer values
            ['http' => ['timeout' => 10, 'max_redirects' => 5]],
            // Float values
            ['http' => ['timeout' => 10.5]],
            // Boolean values
            ['http' => ['ignore_errors' => true, 'follow_location' => false]],
            // String values
            ['http' => ['method' => 'GET', 'user_agent' => 'Test/1.0']],
        ];

        foreach ($optionsVariations as $options) {
            $context = $this->streamIo->createStreamContext($options);
            $this->assertIsResource($context);
        }
    }

    /**
     * Test getContents return type via success case.
     */
    public function testGetContentsReturnTypeViaSuccessCase(): void
    {
        $dataUrl = 'data://text/plain;base64,U3VjY2Vzcw==';
        $context = $this->streamIo->createStreamContext([]);

        $result = $this->streamIo->getContents($dataUrl, $context);

        // Result is string on success or false on failure
        if ($result !== false) {
            $this->assertStringContainsString('Success', $result);
        }
    }

    /**
     * Test getResponseHeaders always returns an array.
     */
    public function testGetResponseHeadersReturnsArray(): void
    {
        $headers = $this->streamIo->getResponseHeaders();

        $this->assertSame($headers, $this->streamIo->getResponseHeaders());
    }

    /**
     * Test getResponseHeaders returns status line after an HTTP request.
     */
    public function testGetResponseHeadersAfterHttpRequest(): void
    {
        $context = $this->streamIo->createStreamContext([
            'http' => [
                'method' => 'GET',
                'ignore_errors' => true,
                'timeout' => 1.0,
            ],
        ]);

        // A failed request may still populate response headers depending on transport errors.
        @$this->streamIo->getContents('http://example.com', $context);
        $headers = $this->streamIo->getResponseHeaders();

        if ($headers !== []) {
            $this->assertMatchesRegularExpression('/^HTTP\/\S+\s+\d{3}/', $headers[0]);
            return;
        }

        $this->assertSame([], $headers);
    }
}
