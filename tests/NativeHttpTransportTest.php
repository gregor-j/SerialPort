<?php /** @noinspection HttpUrlsUsage */

declare(strict_types=1);

namespace Tests\GregorJ\SerialPort;

use GregorJ\SerialPort\Container\NativeHttpTransportContainer;
use GregorJ\SerialPort\Exceptions\ConnectionException;
use GregorJ\SerialPort\Exceptions\InvalidParamException;
use GregorJ\SerialPort\Interfaces\Http\HttpStreamIo;
use GregorJ\SerialPort\Interfaces\HttpTransport;
use GregorJ\SerialPort\Interfaces\System\Error;
use GregorJ\SerialPort\NativeHttpTransport;
use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * Unit tests for NativeHttpTransport with injected dependencies.
 */
final class NativeHttpTransportTest extends TestCase
{
    /**
     * @return void
     * @throws InvalidParamException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function testConstructorWithoutContainerUsesDefault(): void
    {
        $streamIo = $this->getMockBuilder(HttpStreamIo::class)->getMock();
        $errors = $this->getMockBuilder(Error::class)->getMock();
        $transport = new NativeHttpTransport(2.0, 5.0, new NativeHttpTransportContainer($streamIo, $errors));
        // Verify that dependencies are accepted in constructor
        /** @noinspection PhpConditionAlreadyCheckedInspection */
        $this->assertInstanceOf(NativeHttpTransport::class, $transport);
    }

    /**
     * @return void
     */
    public function testConstructorWithoutDependenciesUsesContainer(): void
    {
        // Default construction without explicit dependencies
        // should use NativeHttpTransportContainer to resolve HttpStreamIo and Error
        $transport = new NativeHttpTransport();
        /** @noinspection PhpConditionAlreadyCheckedInspection */
        $this->assertInstanceOf(NativeHttpTransport::class, $transport);
    }

    /**
     * Test constructor with default timeout values.
     * @return void
     * @throws ContainerExceptionInterface
     * @throws InvalidParamException
     * @throws NotFoundExceptionInterface
     */
    public function testConstructorWithDefaultTimeouts(): void
    {
        $streamIo = $this->getMockBuilder(HttpStreamIo::class)->getMock();
        $errors = $this->getMockBuilder(Error::class)->getMock();
        $transport = new NativeHttpTransport(
            NativeHttpTransport::DEFAULT_CONNECT_TIMEOUT,
            NativeHttpTransport::DEFAULT_REQUEST_TIMEOUT,
            new NativeHttpTransportContainer($streamIo, $errors)
        );
        /** @noinspection PhpConditionAlreadyCheckedInspection */
        $this->assertInstanceOf(NativeHttpTransport::class, $transport);
    }

    /**
     * Test constructor rejects negative connect timeout.
     * @return void
     * @throws ContainerExceptionInterface
     * @throws InvalidParamException
     * @throws NotFoundExceptionInterface
     */
    public function testConstructorWithNegativeConnectTimeout(): void
    {
        $this->expectException(InvalidParamException::class);
        $this->expectExceptionMessage('HTTP transport connect timeout for HttpCommunication has to be positive.');

        new NativeHttpTransport(-1.0, 5.0);
    }

    /**
     * Test constructor rejects negative request timeout.
     * @return void
     * @throws ContainerExceptionInterface
     * @throws InvalidParamException
     * @throws NotFoundExceptionInterface
     */
    public function testConstructorWithNegativeRequestTimeout(): void
    {
        $this->expectException(InvalidParamException::class);
        $this->expectExceptionMessage('HTTP transport request timeout for HttpCommunication has to be positive.');
        new NativeHttpTransport(2.0, -1.0);
    }

    /**
     * Test constructor accepts zero timeout values.
     * @return void
     * @throws ContainerExceptionInterface
     * @throws InvalidParamException
     * @throws NotFoundExceptionInterface
     */
    public function testConstructorWithZeroTimeouts(): void
    {
        $streamIo = $this->getMockBuilder(HttpStreamIo::class)->getMock();
        $errors = $this->getMockBuilder(Error::class)->getMock();
        $transport = new NativeHttpTransport(0.0, 0.0, new NativeHttpTransportContainer($streamIo, $errors));
        /** @noinspection PhpConditionAlreadyCheckedInspection */
        $this->assertInstanceOf(NativeHttpTransport::class, $transport);
    }

    /**
     * Test postJson returns parsed status code and headers.
     * @return void
     * @throws ConnectionException
     * @throws ContainerExceptionInterface
     * @throws InvalidParamException
     * @throws NotFoundExceptionInterface
     */
    public function testPostJsonReturnsResponseWhenStatusCodeIsAvailable(): void
    {
        $streamIo = $this->getMockBuilder(HttpStreamIo::class)->getMock();
        $errors = $this->getMockBuilder(Error::class)->getMock();

        $streamIo->expects($this->once())
            ->method('createStreamContext')
            ->willReturn(tmpfile());

        $streamIo->expects($this->once())
            ->method('getContents')
            ->willReturn('{"ok":true}');

        $streamIo->expects($this->once())
            ->method('getResponseHeaders')
            ->willReturn([
                'HTTP/1.1 201 Created',
                'Content-Type: application/json',
                'X-Trace-Id: abc123',
            ]);

        $errors->expects($this->once())
            ->method('clearLastError');

        $transport = new NativeHttpTransport(2.0, 5.0, new NativeHttpTransportContainer($streamIo, $errors));
        $response = $transport->postJson('http://example.com/api', '{"test":"data"}');

        $this->assertSame(201, $response->getStatusCode());
        $this->assertSame('{"ok":true}', $response->getBody());
        $this->assertSame('application/json', $response->getHeaders()['content-type']);
        $this->assertSame('abc123', $response->getHeaders()['x-trace-id']);
    }

    /**
     * Test postJson throws ConnectionException when unable to determine status code.
     * @return void
     * @throws ConnectionException
     * @throws ContainerExceptionInterface
     * @throws InvalidParamException
     * @throws NotFoundExceptionInterface
     */
    public function testPostJsonThrowsConnectionExceptionWhenStatusCodeUnknown(): void
    {
        $streamIo = $this->getMockBuilder(HttpStreamIo::class)->getMock();
        $errors = $this->getMockBuilder(Error::class)->getMock();

        $responseBody = '{"responseBase64":"dGVzdA=="}';

        $streamIo->expects($this->once())
            ->method('createStreamContext')
            ->willReturn(tmpfile());

        $streamIo->expects($this->once())
            ->method('getContents')
            ->willReturn($responseBody);

        $streamIo->expects($this->once())
            ->method('getResponseHeaders')
            ->willReturn([]);

        $errors->expects($this->once())
            ->method('clearLastError');

        $transport = new NativeHttpTransport(2.0, 5.0, new NativeHttpTransportContainer($streamIo, $errors));

        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('Could not determine HTTP status code');

        $transport->postJson('http://example.com/api', '{"test":"data"}');
    }

    /**
     * Test postJson throws ConnectionException when streamIo returns false.
     * @return void
     * @throws ConnectionException
     * @throws ContainerExceptionInterface
     * @throws InvalidParamException
     * @throws NotFoundExceptionInterface
     */
    public function testPostJsonThrowsConnectionExceptionOnStreamIoFailure(): void
    {
        $streamIo = $this->getMockBuilder(HttpStreamIo::class)->getMock();
        $errors = $this->getMockBuilder(Error::class)->getMock();

        $errorInfo = ['message' => 'Connection timeout'];

        $streamIo->expects($this->once())
            ->method('createStreamContext')
            ->willReturn(tmpfile());

        $streamIo->expects($this->once())
            ->method('getContents')
            ->willReturn(false);

        $streamIo->expects($this->never())
            ->method('getResponseHeaders');

        $errors->expects($this->once())
            ->method('clearLastError');

        $errors->expects($this->once())
            ->method('getLastError')
            ->willReturn($errorInfo);

        $transport = new NativeHttpTransport(2.0, 5.0, new NativeHttpTransportContainer($streamIo, $errors));

        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessageMatches('/HTTP request to.*failed/');

        $transport->postJson('http://example.com/api', '{"test":"data"}');
    }

    /**
     * Test postJson with error info as non-array returns unknown error message.
     * @return void
     * @throws ConnectionException
     * @throws ContainerExceptionInterface
     * @throws InvalidParamException
     * @throws NotFoundExceptionInterface
     */
    public function testPostJsonWithNonArrayErrorInfo(): void
    {
        $streamIo = $this->getMockBuilder(HttpStreamIo::class)->getMock();
        $errors = $this->getMockBuilder(Error::class)->getMock();

        $streamIo->expects($this->once())
            ->method('createStreamContext')
            ->willReturn(tmpfile());

        $streamIo->expects($this->once())
            ->method('getContents')
            ->willReturn(false);

        $streamIo->expects($this->never())
            ->method('getResponseHeaders');

        $errors->expects($this->once())
            ->method('clearLastError');

        $errors->expects($this->once())
            ->method('getLastError')
            ->willReturn(null);

        $transport = new NativeHttpTransport(2.0, 5.0, new NativeHttpTransportContainer($streamIo, $errors));

        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('Unknown error.');

        $transport->postJson('http://example.com/api', '{"test":"data"}');
    }

    /**
     * Test constructor with various timeout combinations.
     * @return void
     * @throws ContainerExceptionInterface
     * @throws InvalidParamException
     * @throws NotFoundExceptionInterface
     */
    public function testConstructorWithVariousTimeoutValues(): void
    {
        $timeoutCombinations = [
            [0.0, 0.0],
            [0.5, 1.0],
            [1.0, 5.0],
            [10.0, 30.0],
            [0.1, 0.2],
        ];

        $streamIo = $this->getMockBuilder(HttpStreamIo::class)->getMock();
        $errors = $this->getMockBuilder(Error::class)->getMock();

        foreach ($timeoutCombinations as [$connectTimeout, $requestTimeout]) {
            $transport = new NativeHttpTransport(
                $connectTimeout,
                $requestTimeout,
                new NativeHttpTransportContainer($streamIo, $errors)
            );
            /** @noinspection PhpConditionAlreadyCheckedInspection */
            $this->assertInstanceOf(NativeHttpTransport::class, $transport);
        }
    }

    /**
     * Test that NativeHttpTransport implements HttpTransport interface.
     */
    public function testImplementsHttpInterface(): void
    {
        $transport = new NativeHttpTransport();
        /** @noinspection PhpConditionAlreadyCheckedInspection */
        $this->assertInstanceOf(HttpTransport::class, $transport);
    }
}
