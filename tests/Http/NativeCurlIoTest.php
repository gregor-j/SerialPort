<?php

/** @noinspection HttpUrlsUsage */

declare(strict_types=1);

namespace Tests\GregorJ\SerialPort\Http;

use GregorJ\SerialPort\Http\NativeCurlIo;
use PHPUnit\Framework\TestCase;

use function file_put_contents;
use function function_exists;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

/**
 * Unit tests for NativeCurlIo.
 */
final class NativeCurlIoTest extends TestCase
{
    private NativeCurlIo $curlIo;

    protected function setUp(): void
    {
        if (!function_exists('curl_init')) {
            self::markTestSkipped('ext-curl is required for NativeCurlIo tests.');
        }

        $this->curlIo = new NativeCurlIo();
    }

    public function testExecAndInfoWithFileScheme(): void
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'curl-io-');
        if ($tempFile === false) {
            $this->fail('Could not create temp file for cURL test.');
        }
        file_put_contents($tempFile, 'native-curl-io-test');

        $handle = $this->curlIo->init('file://' . $tempFile);
        $this->assertNotFalse($handle);

        $this->assertTrue($this->curlIo->setOptArray($handle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
        ]));

        $result = $this->curlIo->exec($handle);
        $this->assertSame('native-curl-io-test', $result);

        $info = $this->curlIo->getInfo($handle);
        $this->assertIsArray($info);

        $responseCode = $this->curlIo->getInfo($handle, CURLINFO_RESPONSE_CODE);
        $this->assertIsInt($responseCode);

        $this->assertSame(0, $this->curlIo->getErrNo($handle));
        $this->assertSame('', $this->curlIo->getError($handle));

        $this->curlIo->close($handle);
        unlink($tempFile);
    }

    public function testExecFailureReturnsFalseAndProvidesErrorInformation(): void
    {
        $handle = $this->curlIo->init('http://');
        $this->assertNotFalse($handle);

        $this->assertTrue($this->curlIo->setOptArray($handle, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT_MS => 100,
            CURLOPT_CONNECTTIMEOUT_MS => 100,
        ]));

        $result = $this->curlIo->exec($handle);
        $this->assertFalse($result);

        $errorNumber = $this->curlIo->getErrNo($handle);
        $errorMessage = $this->curlIo->getError($handle);
        $this->assertTrue($errorNumber > 0 || $errorMessage !== '');

        $this->curlIo->close($handle);
    }
}
