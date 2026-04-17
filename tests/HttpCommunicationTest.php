<?php

declare(strict_types=1);

namespace Tests\GregorJ\SerialPort;

use GregorJ\SerialPort\Exceptions\ConnectionException;
use GregorJ\SerialPort\Exceptions\InvalidParamException;
use GregorJ\SerialPort\Exceptions\TimeoutException;
use GregorJ\SerialPort\Exceptions\UnexpectedResponseException;
use GregorJ\SerialPort\Exceptions\WriteException;
use GregorJ\SerialPort\Http\HttpResponse;
use GregorJ\SerialPort\HttpCommunication;
use GregorJ\SerialPort\Interfaces\Http\SerialGatewayContract;
use GregorJ\SerialPort\Interfaces\HttpTransport;
use PHPUnit\Framework\TestCase;

use function base64_encode;
use function sprintf;

/**
 * Unit tests for HttpCommunication.
 */
final class HttpCommunicationTest extends TestCase
{
    /**
     * @return void
     * @throws InvalidParamException
     */
    public function testToString(): void
    {
        $transport = $this->getMockBuilder(HttpTransport::class)->getMock();
        $communication = new HttpCommunication($transport, 'https://gateway.example/query', 'ttyS0', HttpCommunication::DEVICE_TYPE_WIRED);

        $this->assertSame('https://gateway.example/query', (string)$communication);
    }

    /**
     * @return void
     * @throws ConnectionException
     * @throws InvalidParamException
     * @throws TimeoutException
     * @throws UnexpectedResponseException
     * @throws WriteException
     */
    public function testQueryUsesInjectedGatewayContract(): void
    {
        $transport = $this->getMockBuilder(HttpTransport::class)->getMock();
        $contract = $this->getMockBuilder(SerialGatewayContract::class)->getMock();

        $contract->expects($this->once())
            ->method('encodeRequest')
            ->with('HELLO', "\n", "\r\n", 1.2, 'ttyS0', HttpCommunication::DEVICE_TYPE_WIRED)
            ->willReturn('{"dummy":"payload"}');

        $transport->expects($this->once())
            ->method('postJson')
            ->with('https://gateway.example/query', '{"dummy":"payload"}')
            ->willReturn(new HttpResponse(200, '{"any":"response"}'));

        $contract->expects($this->once())
            ->method('decodeResponse')
            ->with('{"any":"response"}')
            ->willReturn("WORLD\r\n");

        $communication = new HttpCommunication(
            $transport,
            'https://gateway.example/query',
            'ttyS0',
            HttpCommunication::DEVICE_TYPE_WIRED,
            $contract
        );
        $communication->setTimeout(1.2);

        $response = $communication->query('HELLO', "\n", "\r\n");
        $this->assertSame("WORLD\r\n", $response);
    }

    /**
     * @return void
     * @throws InvalidParamException
     */
    public function testInvalidEndpoint(): void
    {
        $transport = $this->getMockBuilder(HttpTransport::class)->getMock();
        $this->expectException(InvalidParamException::class);
        $this->expectExceptionMessage('HTTP endpoint for HttpCommunication has to be a valid URL.');

        new HttpCommunication($transport, 'not-a-url', 'ttyS0', HttpCommunication::DEVICE_TYPE_WIRED);
    }

    /**
     * @return void
     * @throws InvalidParamException
     */
    public function testInvalidEndpointScheme(): void
    {
        $transport = $this->getMockBuilder(HttpTransport::class)->getMock();
        $this->expectException(InvalidParamException::class);
        $this->expectExceptionMessage('HTTP endpoint for HttpCommunication has to use scheme http or https.');

        new HttpCommunication($transport, 'ftp://gateway.example/query', 'ttyS0', HttpCommunication::DEVICE_TYPE_WIRED);
    }

    /**
     * @return void
     * @throws InvalidParamException
     */
    public function testInvalidSetTimeout(): void
    {
        $transport = $this->getMockBuilder(HttpTransport::class)->getMock();
        $communication = new HttpCommunication($transport, 'https://gateway.example/query', 'ttyS0', HttpCommunication::DEVICE_TYPE_WIRED);

        $this->expectException(InvalidParamException::class);
        $this->expectExceptionMessage('Response timeout for HttpCommunication has to be positive.');
        $communication->setTimeout(-1.0);
    }

    /**
     * @return void
     * @throws ConnectionException
     * @throws InvalidParamException
     * @throws TimeoutException
     * @throws UnexpectedResponseException
     * @throws WriteException
     */
    public function testQuerySendsDeviceTimeoutAndReadsResponse(): void
    {
        $transport = $this->getMockBuilder(HttpTransport::class)->getMock();
        $transport->expects($this->once())
            ->method('postJson')
            ->with(
                'https://gateway.example/query',
                '{"commandBase64":"SEVMTE8=","writeTerminatorBase64":"Cg==","readTerminatorBase64":"DQo=","deviceTimeoutMs":1200,"deviceId":"ttyS0","deviceType":"wired"}'
            )
            ->willReturn(new HttpResponse(200, '{"responseBase64":"V09STEQNCg=="}'));

        $communication = new HttpCommunication($transport, 'https://gateway.example/query', 'ttyS0', HttpCommunication::DEVICE_TYPE_WIRED);
        $communication->setTimeout(1.2);

        $response = $communication->query('HELLO', "\n", "\r\n");
        $this->assertSame("WORLD\r\n", $response);

        $log = $communication->getLog();
        $this->assertCount(4, $log);
        $this->assertSame(sprintf('default timeout %f seconds', HttpCommunication::DEFAULT_TIMEOUT), $log[0]);
        $this->assertSame('set timeout to 1.200000 seconds', $log[1]);
        $this->assertSame('write "HELLO\n"', $log[2]);
        $this->assertSame('read "WORLD\r\n"', $log[3]);
    }

    /**
     * @return void
     * @throws ConnectionException
     * @throws InvalidParamException
     * @throws TimeoutException
     * @throws UnexpectedResponseException
     * @throws WriteException
     */
    public function testQuerySendsConfiguredDeviceSelection(): void
    {
        $transport = $this->getMockBuilder(HttpTransport::class)->getMock();
        $transport->expects($this->once())
            ->method('postJson')
            ->with(
                'https://gateway.example/query',
                '{"commandBase64":"SEVMTE8=","writeTerminatorBase64":"","readTerminatorBase64":"","deviceTimeoutMs":2000,"deviceId":"BT-COM7","deviceType":"bluetooth"}'
            )
            ->willReturn(new HttpResponse(200, '{"responseBase64":"V09STEQ="}'));

        $communication = new HttpCommunication($transport, 'https://gateway.example/query', 'BT-COM7', HttpCommunication::DEVICE_TYPE_BLUETOOTH);

        $response = $communication->query('HELLO');
        $this->assertSame('WORLD', $response);
    }

    /**
     * @return void
     * @throws InvalidParamException
     */
    public function testSetDeviceRejectsEmptyDeviceId(): void
    {
        $transport = $this->getMockBuilder(HttpTransport::class)->getMock();
        $this->expectException(InvalidParamException::class);
        $this->expectExceptionMessage('Device ID must not be empty.');
        new HttpCommunication($transport, 'https://gateway.example/query', '', HttpCommunication::DEVICE_TYPE_BLUETOOTH);
    }

    /**
     * @return void
     * @throws InvalidParamException
     */
    public function testSetDeviceRejectsInvalidDeviceType(): void
    {
        $transport = $this->getMockBuilder(HttpTransport::class)->getMock();

        $this->expectException(InvalidParamException::class);
        $this->expectExceptionMessage('Device type must be "bluetooth" or "wired".');
        new HttpCommunication($transport, 'https://gateway.example/query', 'ttyUSB0', 'zigbee');
    }

    /**
     * @return void
     * @throws ConnectionException
     * @throws InvalidParamException
     * @throws TimeoutException
     * @throws UnexpectedResponseException
     * @throws WriteException
     */
    public function testTransportTimeoutIsConnectionException(): void
    {
        $transport = $this->getMockBuilder(HttpTransport::class)->getMock();
        $transport->expects($this->once())
            ->method('postJson')
            ->willThrowException(new ConnectionException('HTTP transport timed out.'));

        $communication = new HttpCommunication($transport, 'https://gateway.example/query', 'ttyS0', HttpCommunication::DEVICE_TYPE_WIRED);

        $this->expectException(ConnectionException::class);
        $this->expectExceptionMessage('HTTP transport timed out.');
        $communication->query('HELLO');
    }

    /**
     * @return void
     * @throws ConnectionException
     * @throws InvalidParamException
     * @throws TimeoutException
     * @throws UnexpectedResponseException
     * @throws WriteException
     */
    public function testDeviceTimeoutFromGatewayThrowsTimeoutException(): void
    {
        $transport = $this->getMockBuilder(HttpTransport::class)->getMock();
        $transport->expects($this->once())
            ->method('postJson')
            ->willReturn(new HttpResponse(200, '{"timeoutError":"reading from device timed out"}'));

        $communication = new HttpCommunication($transport, 'https://gateway.example/query', 'ttyS0', HttpCommunication::DEVICE_TYPE_WIRED);

        $this->expectException(TimeoutException::class);
        $this->expectExceptionMessage('reading from device timed out');
        $communication->query('HELLO');
    }

    /**
     * @return void
     * @throws ConnectionException
     * @throws InvalidParamException
     * @throws TimeoutException
     * @throws UnexpectedResponseException
     * @throws WriteException
     */
    public function testUnexpectedHttpStatusThrowsException(): void
    {
        $transport = $this->getMockBuilder(HttpTransport::class)->getMock();
        $transport->expects($this->once())
            ->method('postJson')
            ->willReturn(new HttpResponse(503, '{}'));

        $communication = new HttpCommunication($transport, 'https://gateway.example/query', 'ttyS0', HttpCommunication::DEVICE_TYPE_WIRED);

        $this->expectException(UnexpectedResponseException::class);
        $this->expectExceptionMessage('HTTP gateway returned unexpected status code 503.');
        $communication->query('HELLO');
    }

    /**
     * @return void
     * @throws ConnectionException
     * @throws InvalidParamException
     * @throws TimeoutException
     * @throws UnexpectedResponseException
     * @throws WriteException
     */
    public function testMissingResponseBase64ThrowsException(): void
    {
        $transport = $this->getMockBuilder(HttpTransport::class)->getMock();
        $transport->expects($this->once())
            ->method('postJson')
            ->willReturn(new HttpResponse(200, '{"foo":"bar"}'));

        $communication = new HttpCommunication($transport, 'https://gateway.example/query', 'ttyS0', HttpCommunication::DEVICE_TYPE_WIRED);

        $this->expectException(UnexpectedResponseException::class);
        $this->expectExceptionMessage('HTTP gateway response field "responseBase64" is missing.');
        $communication->query('HELLO');
    }

    /**
     * @return void
     * @throws ConnectionException
     * @throws InvalidParamException
     * @throws TimeoutException
     * @throws UnexpectedResponseException
     * @throws WriteException
     */
    public function testInvalidResponseBase64ThrowsException(): void
    {
        $transport = $this->getMockBuilder(HttpTransport::class)->getMock();
        $transport->expects($this->once())
            ->method('postJson')
            ->willReturn(new HttpResponse(200, '{"responseBase64":"%%"}'));

        $communication = new HttpCommunication($transport, 'https://gateway.example/query', 'ttyS0', HttpCommunication::DEVICE_TYPE_WIRED);

        $this->expectException(UnexpectedResponseException::class);
        $this->expectExceptionMessage('HTTP gateway returned invalid base64 in field "responseBase64".');
        $communication->query('HELLO');
    }
}
