<?php

declare(strict_types=1);

namespace Tests\GregorJ\SerialPort\Http;

use GregorJ\SerialPort\Exceptions\UnexpectedResponseException;
use GregorJ\SerialPort\Exceptions\WriteException;
use GregorJ\SerialPort\Http\JsonSerialGatewayContract;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for JsonSerialGatewayContract.
 */
final class JsonSerialGatewayContractTest extends TestCase
{
    /**
     * @return void
     */
    public function testEncodeRequestBuildsExpectedJson(): void
    {
        $contract = new JsonSerialGatewayContract();

        $jsonPayload = $contract->encodeRequest('HELLO', "\n", "\r\n", 1200, 'ttyS0', 'wired');

        $this->assertSame(
            '{"commandBase64":"SEVMTE8=","writeTerminatorBase64":"Cg==","readTerminatorBase64":"DQo=","deviceTimeoutMs":1200,"deviceId":"ttyS0","deviceType":"wired"}',
            $jsonPayload
        );
    }

    /**
     * @return void
     */
    public function testEncodeRequestThrowsWriteExceptionForInvalidUtf8DeviceId(): void
    {
        $contract = new JsonSerialGatewayContract();

        $this->expectException(WriteException::class);
        $this->expectExceptionMessage('Failed to encode HTTP serial request payload.');

        $contract->encodeRequest('HELLO', "\n", "\r\n", 1200, "\xB1", 'wired');
    }

    /**
     * @return void
     * @throws UnexpectedResponseException
     */
    public function testDecodeResponseSuccess(): void
    {
        $contract = new JsonSerialGatewayContract();

        $response = $contract->decodeResponse('{"responseBase64":"V09STEQNCg=="}');

        $this->assertFalse($response->isDeviceTimedOut());
        $this->assertSame("WORLD\r\n", $response->getResponse());
        $this->assertSame('', $response->getPartialResponse());
    }

    /**
     * @return void
     * @throws UnexpectedResponseException
     */
    public function testDecodeResponseTimeoutWithPartialResponse(): void
    {
        $contract = new JsonSerialGatewayContract();

        $response = $contract->decodeResponse('{"deviceTimedOut":true,"partialResponseBase64":"V08="}');

        $this->assertTrue($response->isDeviceTimedOut());
        $this->assertSame('', $response->getResponse());
        $this->assertSame('WO', $response->getPartialResponse());
    }

    /**
     * @return void
     * @throws UnexpectedResponseException
     */
    public function testDecodeResponseTimeoutWithoutPartialResponse(): void
    {
        $contract = new JsonSerialGatewayContract();

        $response = $contract->decodeResponse('{"deviceTimedOut":true}');

        $this->assertTrue($response->isDeviceTimedOut());
        $this->assertSame('', $response->getPartialResponse());
    }

    /**
     * @return void
     */
    public function testDecodeResponseThrowsUnexpectedResponseExceptionForInvalidJson(): void
    {
        $contract = new JsonSerialGatewayContract();

        $this->expectException(UnexpectedResponseException::class);
        $this->expectExceptionMessage('HTTP gateway returned invalid JSON response.');
        $contract->decodeResponse('{');
    }

    /**
     * @return void
     */
    public function testDecodeResponseThrowsUnexpectedResponseExceptionForNonArrayJson(): void
    {
        $contract = new JsonSerialGatewayContract();

        $this->expectException(UnexpectedResponseException::class);
        $this->expectExceptionMessage('HTTP gateway returned invalid JSON response.');
        $contract->decodeResponse('"just a string"');
    }

    /**
     * @return void
     */
    public function testDecodeResponseThrowsUnexpectedResponseExceptionForMissingResponseBase64(): void
    {
        $contract = new JsonSerialGatewayContract();

        $this->expectException(UnexpectedResponseException::class);
        $this->expectExceptionMessage('HTTP gateway response field "responseBase64" is missing.');
        $contract->decodeResponse('{"foo":"bar"}');
    }

    /**
     * @return void
     */
    public function testDecodeResponseThrowsUnexpectedResponseExceptionForInvalidResponseBase64(): void
    {
        $contract = new JsonSerialGatewayContract();

        $this->expectException(UnexpectedResponseException::class);
        $this->expectExceptionMessage('HTTP gateway returned invalid base64 in field "responseBase64".');
        $contract->decodeResponse('{"responseBase64":"%%"}');
    }

    /**
     * @return void
     */
    public function testDecodeResponseThrowsUnexpectedResponseExceptionForInvalidPartialResponseType(): void
    {
        $contract = new JsonSerialGatewayContract();

        $this->expectException(UnexpectedResponseException::class);
        $this->expectExceptionMessage('HTTP gateway response field "partialResponseBase64" has invalid type.');
        $contract->decodeResponse('{"deviceTimedOut":true,"partialResponseBase64":123}');
    }

    /**
     * @return void
     */
    public function testDecodeResponseThrowsUnexpectedResponseExceptionForInvalidPartialResponseBase64(): void
    {
        $contract = new JsonSerialGatewayContract();

        $this->expectException(UnexpectedResponseException::class);
        $this->expectExceptionMessage('HTTP gateway returned invalid base64 in field "partialResponseBase64".');
        $contract->decodeResponse('{"deviceTimedOut":true,"partialResponseBase64":"%%"}');
    }
}
