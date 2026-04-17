<?php

declare(strict_types=1);

namespace Tests\GregorJ\SerialPort\Http;

use Exception;
use GregorJ\SerialPort\Exceptions\ConnectionException;
use GregorJ\SerialPort\Exceptions\InvalidParamException;
use GregorJ\SerialPort\Exceptions\TimeoutException;
use GregorJ\SerialPort\Exceptions\UnexpectedResponseException;
use GregorJ\SerialPort\Exceptions\WriteException;
use GregorJ\SerialPort\Http\HttpResponse;
use GregorJ\SerialPort\Http\JsonSerialGatewayContract;
use GregorJ\SerialPort\Interfaces\Http\SerialGatewayContract;
use JsonException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Throwable;

use function base64_encode;
use function json_decode;
use function json_encode;

/**
 * Unit tests for JsonSerialGatewayContract.
 */
final class JsonSerialGatewayContractTest extends TestCase
{
    private JsonSerialGatewayContract $contract;

    protected function setUp(): void
    {
        $this->contract = new JsonSerialGatewayContract();
    }

    public function testImplementsSerialGatewayContractInterface(): void
    {
        /** @noinspection PhpConditionAlreadyCheckedInspection */
        $this->assertInstanceOf(SerialGatewayContract::class, $this->contract);
    }

    public function testEncodeRequestCreatesExpectedJsonPayload(): void
    {
        $payload = $this->contract->encodeRequest('HELLO', "\n", "\r\n", 1.2, 'ttyS0', 'wired');

        $this->assertSame(
            '{"commandBase64":"SEVMTE8=","writeTerminatorBase64":"Cg==","readTerminatorBase64":"DQo=","deviceTimeoutMs":1200,"deviceId":"ttyS0","deviceType":"wired"}',
            $payload
        );
    }

    public function testEncodeRequestRoundsTimeoutToMilliseconds(): void
    {
        $payload = $this->contract->encodeRequest('A', '', '', 1.2346, 'ttyS0', 'wired');
        $decoded = json_decode($payload, true, 512, JSON_THROW_ON_ERROR);

        $this->assertIsArray($decoded);
        $this->assertArrayHasKey(JsonSerialGatewayContract::REQUEST_TIMEOUT, $decoded);

        $timeout = $decoded[JsonSerialGatewayContract::REQUEST_TIMEOUT];
        $this->assertIsInt($timeout);

        $this->assertSame(1235, $timeout);
    }

    public function testEncodeRequestThrowsWriteExceptionWhenJsonEncodingFails(): void
    {
        // Force json_encode(JSON_THROW_ON_ERROR) to fail via malformed UTF-8 in a raw JSON field.
        $invalidUtf8DeviceId = "\xB1\x31";

        try {
            $this->contract->encodeRequest('HELLO', "\n", "\r\n", 1.2, $invalidUtf8DeviceId, 'wired');
            $this->fail('Expected WriteException was not thrown.');
        } catch (WriteException $exception) {
            $this->assertSame('Failed to encode HTTP serial request payload.', $exception->getMessage());
            $this->assertInstanceOf(JsonException::class, $exception->getPrevious());
        }
    }

    public function testDecodeResponseReturnsDecodedMessage(): void
    {
        $httpResponse = new HttpResponse(200, '{"responseBase64":"V09STEQNCg=="}');
        $decoded = $this->contract->decodeResponse($httpResponse);

        $this->assertSame("WORLD\r\n", $decoded);
    }

    public function testDecodeResponseReturnsBinaryMessage(): void
    {
        $binary = "\x00\x01A\n";
        $responseBody = json_encode(
            [JsonSerialGatewayContract::RESPONSE_VALUE => base64_encode($binary)],
            JSON_THROW_ON_ERROR
        );
        $httpResponse = new HttpResponse(200, $responseBody);

        $decoded = $this->contract->decodeResponse($httpResponse);

        $this->assertSame($binary, $decoded);
    }

    public function testDecodeResponseThrowsOnInvalidJson(): void
    {
        $this->expectException(UnexpectedResponseException::class);
        $this->expectExceptionMessage('HTTP gateway returned invalid JSON response.');

        $this->contract->decodeResponse(new HttpResponse(200, '{invalid'));
    }

    public function testDecodeResponseThrowsOnNonArrayJson(): void
    {
        $this->expectException(UnexpectedResponseException::class);
        $this->expectExceptionMessage('HTTP gateway returned invalid JSON response.');

        $this->contract->decodeResponse(new HttpResponse(200, '"just-a-string"'));
    }

    /**
     * @param class-string<Throwable> $expectedException
     */
    #[DataProvider('errorMappingProvider')]
    public function testDecodeResponseMapsGatewayErrors(
        string $responseBody,
        string $expectedException,
        string $expectedMessage
    ): void {
        $this->expectException($expectedException);
        $this->expectExceptionMessage($expectedMessage);

        $this->contract->decodeResponse(new HttpResponse(200, $responseBody));
    }

    /**
     * @return array<string, array{string, class-string<Throwable>, string}>
     */
    public static function errorMappingProvider(): array
    {
        return [
            'invalid param error' => [
                '{"invalidParamError":"bad parameter"}',
                InvalidParamException::class,
                'bad parameter',
            ],
            'connection error' => [
                '{"connectionError":"connection failed"}',
                ConnectionException::class,
                'connection failed',
            ],
            'timeout error' => [
                '{"timeoutError":"timed out"}',
                TimeoutException::class,
                'timed out',
            ],
            'generic error' => [
                '{"error":"generic error"}',
                Exception::class,
                'generic error',
            ],
        ];
    }

    public function testDecodeResponseThrowsWhenResponseBase64IsMissing(): void
    {
        $this->expectException(UnexpectedResponseException::class);
        $this->expectExceptionMessage('HTTP gateway response field "responseBase64" is missing.');

        $this->contract->decodeResponse(new HttpResponse(200, '{"foo":"bar"}'));
    }

    public function testDecodeResponseThrowsWhenResponseBase64IsNotString(): void
    {
        $this->expectException(UnexpectedResponseException::class);
        $this->expectExceptionMessage('HTTP gateway response field "responseBase64" is integer, not string.');

        $this->contract->decodeResponse(new HttpResponse(200, '{"responseBase64":123}'));
    }

    public function testDecodeResponseThrowsWhenResponseBase64IsInvalid(): void
    {
        $this->expectException(UnexpectedResponseException::class);
        $this->expectExceptionMessage('HTTP gateway returned invalid base64 in field "responseBase64".');

        $this->contract->decodeResponse(new HttpResponse(200, '{"responseBase64":"%%"}'));
    }
}
