<?php

/** @noinspection HttpUrlsUsage */

declare(strict_types=1);

namespace Tests\GregorJ\SerialPort;

use CurlHandle;
use GregorJ\SerialPort\Container\CurlTransportContainer;
use GregorJ\SerialPort\CurlTransport;
use GregorJ\SerialPort\Exceptions\ConnectionException;
use GregorJ\SerialPort\Exceptions\ContainerException;
use GregorJ\SerialPort\Exceptions\InvalidParamException;
use GregorJ\SerialPort\Exceptions\NotFoundException;
use GregorJ\SerialPort\Interfaces\Http\CurlIo;
use GregorJ\SerialPort\Interfaces\HttpTransport;
use GregorJ\SerialPort\Interfaces\System\Error;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use RuntimeException;
use stdClass;

use function curl_close;
use function curl_init;
use function function_exists;
use function strpos;

/**
 * Unit tests for CurlTransport.
 */
final class CurlTransportTest extends TestCase
{
    private ?CurlHandle $realHandle = null;

    protected function setUp(): void
    {
        if (!function_exists('curl_init')) {
            self::markTestSkipped('ext-curl is required for CurlTransport tests.');
        }
    }

    protected function tearDown(): void
    {
        if ($this->realHandle !== null) {
            curl_close($this->realHandle);
            $this->realHandle = null;
        }
    }

    /**
     * Returns a real CurlHandle for use in mock return values.
     * The handle is closed in tearDown() automatically.
     */
    private function realHandle(): CurlHandle
    {
        $handle = curl_init();
        if ($handle === false) {
            $this->fail('curl_init() failed in test setup.');
        }

        $this->realHandle = $handle;

        return $handle;
    }

    public function testConstructorWithoutDependenciesUsesContainer(): void
    {
        $transport = new CurlTransport();

        /** @noinspection PhpConditionAlreadyCheckedInspection */
        $this->assertInstanceOf(CurlTransport::class, $transport);
        /** @noinspection PhpConditionAlreadyCheckedInspection */
        $this->assertInstanceOf(HttpTransport::class, $transport);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testConstructorWithNegativeConnectTimeout(): void
    {
        $this->expectException(InvalidParamException::class);
        $this->expectExceptionMessage('HTTP transport connect timeout for HttpCommunication has to be positive.');

        new CurlTransport(-0.1, 1.0);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testConstructorWithNegativeRequestTimeout(): void
    {
        $this->expectException(InvalidParamException::class);
        $this->expectExceptionMessage('HTTP transport request timeout for HttpCommunication has to be positive.');

        new CurlTransport(1.0, -0.1);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws InvalidParamException
     * @throws NotFoundExceptionInterface
     */
    public function testConstructorThrowsWhenDependencyIsMissing(): void
    {
        $container = new class () implements ContainerInterface {
            public function get(string $id): object
            {
                throw new RuntimeException('Unexpected call to get().');
            }

            public function has(string $id): bool
            {
                return false;
            }
        };

        $this->expectException(NotFoundException::class);
        $this->expectExceptionMessage('Missing required dependency "' . CurlIo::class . '" in container.');

        new CurlTransport(1.0, 1.0, $container);
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws InvalidParamException
     * @throws NotFoundExceptionInterface
     */
    public function testConstructorThrowsWhenDependencyHasWrongType(): void
    {
        $container = new class () implements ContainerInterface {
            public function get(string $id): object
            {
                return new stdClass();
            }

            public function has(string $id): bool
            {
                return true;
            }
        };

        $this->expectException(ContainerException::class);
        $this->expectExceptionMessage('Dependency "' . CurlIo::class . '" must implement ' . CurlIo::class . '.');

        new CurlTransport(1.0, 1.0, $container);
    }

    /**
     * @throws ConnectionException
     * @throws ContainerExceptionInterface
     * @throws InvalidParamException
     * @throws NotFoundExceptionInterface
     */
    public function testPostJsonReturnsResponseWithParsedHeadersFromLastBlock(): void
    {
        $curlIo = $this->createMock(CurlIo::class);
        $error = $this->createMock(Error::class);
        $handle = $this->realHandle();

        $rawResponse = "HTTP/1.1 100 Continue\r\n\r\n"
            . "HTTP/1.1 200 OK\r\n"
            . "Content-Type: application/json\r\n"
            . "X-Trace-Id: abc123\r\n"
            . "BrokenHeaderWithoutColon\r\n"
            . "\r\n"
            . '{"ok":true}';

        $expectedHeaderSize = strpos($rawResponse, '{"ok":true}');
        $this->assertNotFalse($expectedHeaderSize);

        $error->expects($this->once())->method('clearLastError');

        $curlIo->expects($this->once())
            ->method('init')
            ->with('http://example.com/api')
            ->willReturn($handle);

        $curlIo->expects($this->once())
            ->method('setOptArray')
            ->with(
                $handle,
                $this->callback(static function (array $options): bool {
                    return $options[CURLOPT_POST] === true
                        && $options[CURLOPT_POSTFIELDS] === '{"x":1}'
                        && $options[CURLOPT_RETURNTRANSFER] === true
                        && $options[CURLOPT_HEADER] === true
                        && $options[CURLOPT_CONNECTTIMEOUT_MS] === 1200
                        && $options[CURLOPT_TIMEOUT_MS] === 3400;
                })
            )
            ->willReturn(true);

        $curlIo->expects($this->once())
            ->method('exec')
            ->with($handle)
            ->willReturn($rawResponse);

        $curlIo->expects($this->exactly(2))
            ->method('getInfo')
            ->willReturnMap([
                [$handle, CURLINFO_RESPONSE_CODE, 200],
                [$handle, CURLINFO_HEADER_SIZE, $expectedHeaderSize],
            ]);

        $curlIo->expects($this->once())->method('close')->with($handle);

        $transport = new CurlTransport(1.2, 3.4, new CurlTransportContainer($curlIo, $error));

        $response = $transport->postJson('http://example.com/api', '{"x":1}');

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('{"ok":true}', $response->getBody());
        $this->assertSame('application/json', $response->getHeaders()['content-type']);
        $this->assertSame('abc123', $response->getHeaders()['x-trace-id']);
        $this->assertArrayNotHasKey('brokenheaderwithoutcolon', $response->getHeaders());
    }

    /**
     * @throws ConnectionException
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws InvalidParamException
     * @throws NotFoundExceptionInterface
     */
    public function testPostJsonReturnsBodyAsIsWhenHeaderSizeIsZero(): void
    {
        $curlIo = $this->createMock(CurlIo::class);
        $error = $this->createMock(Error::class);
        $handle = $this->realHandle();

        $error->expects($this->once())->method('clearLastError');

        $curlIo->method('init')->willReturn($handle);
        $curlIo->method('setOptArray')->willReturn(true);
        $curlIo->method('exec')->willReturn('{"ok":true}');
        $curlIo->expects($this->exactly(2))
            ->method('getInfo')
            ->willReturnMap([
                [$handle, CURLINFO_RESPONSE_CODE, 200],
                [$handle, CURLINFO_HEADER_SIZE, 0],
            ]);
        $curlIo->expects($this->once())->method('close')->with($handle);

        $transport = new CurlTransport(1.0, 1.0, new CurlTransportContainer($curlIo, $error));
        $response = $transport->postJson('http://example.com/api', '{"x":1}');

        $this->assertSame('{"ok":true}', $response->getBody());
        $this->assertSame([], $response->getHeaders());
    }

    /**
     * @throws ConnectionException
     * @throws ContainerExceptionInterface
     * @throws Exception
     * @throws InvalidParamException
     * @throws NotFoundExceptionInterface
     */
    public function testPostJsonWithOversizedHeaderSizeReturnsEmptyParsedBodyAndHeaders(): void
    {
        $curlIo = $this->createMock(CurlIo::class);
        $error = $this->createMock(Error::class);
        $handle = $this->realHandle();

        $error->expects($this->once())->method('clearLastError');

        $curlIo->method('init')->willReturn($handle);
        $curlIo->method('setOptArray')->willReturn(true);
        $curlIo->method('exec')->willReturn('x');
        $curlIo->expects($this->exactly(2))
            ->method('getInfo')
            ->willReturnMap([
                [$handle, CURLINFO_RESPONSE_CODE, 201],
                [$handle, CURLINFO_HEADER_SIZE, 999],
            ]);
        $curlIo->expects($this->once())->method('close')->with($handle);

        $transport = new CurlTransport(1.0, 1.0, new CurlTransportContainer($curlIo, $error));
        $response = $transport->postJson('http://example.com/api', '{"x":1}');

        $this->assertSame('', $response->getBody());
        $this->assertSame([], $response->getHeaders());
    }

    /**
     * When the raw response only contains the body (header size equals full length),
     * extractHeaders receives an empty string and must return [].
     *
     * @throws ConnectionException
     * @throws ContainerExceptionInterface
     * @throws InvalidParamException
     * @throws NotFoundExceptionInterface
     * @throws Exception
     */
    public function testPostJsonWithEmptyRawHeadersReturnsEmptyHeadersArray(): void
    {
        $curlIo = $this->createMock(CurlIo::class);
        $error = $this->createMock(Error::class);
        $handle = $this->realHandle();

        $error->expects($this->once())->method('clearLastError');

        $curlIo->method('init')->willReturn($handle);
        $curlIo->method('setOptArray')->willReturn(true);
        $curlIo->method('exec')->willReturn("\r\n\r\n");
        $curlIo->expects($this->exactly(2))
            ->method('getInfo')
            ->willReturnMap([
                [$handle, CURLINFO_RESPONSE_CODE, 200],
                [$handle, CURLINFO_HEADER_SIZE, 4],
            ]);
        $curlIo->expects($this->once())->method('close')->with($handle);

        $transport = new CurlTransport(1.0, 1.0, new CurlTransportContainer($curlIo, $error));
        $response = $transport->postJson('http://example.com/api', '{"x":1}');

        $this->assertSame('', $response->getBody());
        $this->assertSame([], $response->getHeaders());
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws InvalidParamException
     * @throws NotFoundExceptionInterface
     */
    public function testPostJsonThrowsWhenInitFailsWithPhpErrorMessage(): void
    {
        $curlIo = $this->createMock(CurlIo::class);
        $error = $this->createMock(Error::class);

        $error->expects($this->once())->method('clearLastError');
        $error->expects($this->once())->method('getLastError')->willReturn(['message' => 'Init failed']);

        $curlIo->expects($this->once())->method('init')->willReturn(false);
        $curlIo->expects($this->never())->method('close');

        $transport = new CurlTransport(1.0, 1.0, new CurlTransportContainer($curlIo, $error));

        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('HTTP request to http://example.com/api failed: Init failed');

        $transport->postJson('http://example.com/api', '{"x":1}');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws InvalidParamException
     * @throws NotFoundExceptionInterface
     */
    public function testPostJsonThrowsWhenInitFailsWithoutPhpErrorMessage(): void
    {
        $curlIo = $this->createMock(CurlIo::class);
        $error = $this->createMock(Error::class);

        $error->expects($this->once())->method('clearLastError');
        $error->expects($this->once())->method('getLastError')->willReturn(null);

        $curlIo->expects($this->once())->method('init')->willReturn(false);

        $transport = new CurlTransport(1.0, 1.0, new CurlTransportContainer($curlIo, $error));

        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('Unknown error.');

        $transport->postJson('http://example.com/api', '{"x":1}');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws InvalidParamException
     * @throws NotFoundExceptionInterface
     */
    public function testPostJsonThrowsWhenInitFailsWithArrayErrorWithoutMessage(): void
    {
        $curlIo = $this->createMock(CurlIo::class);
        $error = $this->createMock(Error::class);

        $error->expects($this->once())->method('clearLastError');
        $error->expects($this->once())->method('getLastError')->willReturn([]);

        $curlIo->expects($this->once())->method('init')->willReturn(false);

        $transport = new CurlTransport(1.0, 1.0, new CurlTransportContainer($curlIo, $error));

        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('Unknown error.');

        $transport->postJson('http://example.com/api', '{"x":1}');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws InvalidParamException
     * @throws NotFoundExceptionInterface
     */
    public function testPostJsonThrowsWhenSetOptArrayFailsWithCurlError(): void
    {
        $curlIo = $this->createMock(CurlIo::class);
        $error = $this->createMock(Error::class);
        $handle = $this->realHandle();

        $error->expects($this->once())->method('clearLastError');
        $error->expects($this->never())->method('getLastError');

        $curlIo->method('init')->willReturn($handle);
        $curlIo->method('setOptArray')->willReturn(false);
        $curlIo->expects($this->once())->method('getErrNo')->with($handle)->willReturn(28);
        $curlIo->expects($this->once())->method('getError')->with($handle)->willReturn('Operation timed out');
        $curlIo->expects($this->once())->method('close')->with($handle);

        $transport = new CurlTransport(1.0, 1.0, new CurlTransportContainer($curlIo, $error));

        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('cURL error 28: Operation timed out');

        $transport->postJson('http://example.com/api', '{"x":1}');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws InvalidParamException
     * @throws NotFoundExceptionInterface
     */
    public function testPostJsonThrowsWhenExecFailsAndFallsBackToPhpError(): void
    {
        $curlIo = $this->createMock(CurlIo::class);
        $error = $this->createMock(Error::class);
        $handle = $this->realHandle();

        $error->expects($this->once())->method('clearLastError');
        $error->expects($this->once())->method('getLastError')->willReturn(['message' => 'Warning from runtime']);

        $curlIo->method('init')->willReturn($handle);
        $curlIo->method('setOptArray')->willReturn(true);
        $curlIo->method('exec')->willReturn(false);
        $curlIo->expects($this->once())->method('getErrNo')->with($handle)->willReturn(0);
        $curlIo->expects($this->once())->method('getError')->with($handle)->willReturn('');
        $curlIo->expects($this->once())->method('close')->with($handle);

        $transport = new CurlTransport(1.0, 1.0, new CurlTransportContainer($curlIo, $error));

        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('Warning from runtime');

        $transport->postJson('http://example.com/api', '{"x":1}');
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws InvalidParamException
     * @throws NotFoundExceptionInterface
     */
    public function testPostJsonThrowsWhenStatusCodeIsUnknown(): void
    {
        $curlIo = $this->createMock(CurlIo::class);
        $error = $this->createMock(Error::class);
        $handle = $this->realHandle();

        $error->expects($this->once())->method('clearLastError');

        $curlIo->method('init')->willReturn($handle);
        $curlIo->method('setOptArray')->willReturn(true);
        $curlIo->method('exec')->willReturn('HTTP/1.1 200 OK\r\n\r\n{}');
        $curlIo->expects($this->once())
            ->method('getInfo')
            ->with($handle, CURLINFO_RESPONSE_CODE)
            ->willReturn(0);
        $curlIo->expects($this->once())->method('close')->with($handle);

        $transport = new CurlTransport(1.0, 1.0, new CurlTransportContainer($curlIo, $error));

        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('Could not determine HTTP status code for http://example.com/api.');

        $transport->postJson('http://example.com/api', '{"x":1}');
    }
}
