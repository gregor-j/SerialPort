<?php

declare(strict_types=1);

namespace Tests\GregorJ\SerialPort\Responses;

use GregorJ\SerialPort\Responses\StringResponse;
use PHPUnit\Framework\TestCase;

/**
 * Unit tests for the StringResponse class.
 */
final class StringResponseTest extends TestCase
{
    /**
     * Test constructor with default empty read terminator.
     */
    public function testConstructorWithDefaultReadTerminator(): void
    {
        $response = 'Hello World';
        $stringResponse = new StringResponse($response);

        $this->assertSame($response, $stringResponse->getResponse());
    }

    /**
     * Test constructor with empty string response and default read terminator.
     */
    public function testConstructorWithEmptyResponseAndDefaultTerminator(): void
    {
        $stringResponse = new StringResponse('');

        $this->assertSame('', $stringResponse->getResponse());
    }

    /**
     * Test constructor with read terminator that is found in response.
     */
    public function testConstructorWithReadTerminatorFound(): void
    {
        $response = "HELLO\nWORLD";
        $readTerminator = "\n";
        $stringResponse = new StringResponse($response, $readTerminator);

        $this->assertSame('HELLO', $stringResponse->getResponse());
    }

    /**
     * Test constructor with read terminator that is not found in response.
     */
    public function testConstructorWithReadTerminatorNotFound(): void
    {
        $response = 'HELLO WORLD';
        $readTerminator = "\r";
        $stringResponse = new StringResponse($response, $readTerminator);

        $this->assertSame('HELLO WORLD', $stringResponse->getResponse());
    }

    /**
     * Test constructor with multiple occurrences of read terminator (takes first part).
     */
    public function testConstructorWithMultipleReadTerminators(): void
    {
        $response = "HELLO\nWORLD\nFOO";
        $readTerminator = "\n";
        $stringResponse = new StringResponse($response, $readTerminator);

        $this->assertSame('HELLO', $stringResponse->getResponse());
    }

    /**
     * Test constructor with read terminator at the beginning of response.
     */
    public function testConstructorWithReadTerminatorAtBeginning(): void
    {
        $response = "\nHELLO";
        $readTerminator = "\n";
        $stringResponse = new StringResponse($response, $readTerminator);

        $this->assertSame('', $stringResponse->getResponse());
    }

    /**
     * Test constructor with empty read terminator (should not split response).
     */
    public function testConstructorWithEmptyReadTerminator(): void
    {
        $response = "HELLO\nWORLD";
        $stringResponse = new StringResponse($response, '');

        $this->assertSame("HELLO\nWORLD", $stringResponse->getResponse());
    }

    /**
     * Test getResponse method returns the original response when no terminator is provided.
     */
    public function testGetResponseWithoutTerminator(): void
    {
        $response = 'Test Response 123';
        $stringResponse = new StringResponse($response);

        $this->assertSame($response, $stringResponse->getResponse());
    }

    /**
     * Test getResponse method returns the original response multiple times consistently.
     */
    public function testGetResponseMultipleCalls(): void
    {
        $response = 'Consistent Response';
        $stringResponse = new StringResponse($response);

        $this->assertSame($response, $stringResponse->getResponse());
        $this->assertSame($response, $stringResponse->getResponse());
    }

    /**
     * Test __toString method without read terminator.
     */
    public function testToStringWithoutTerminator(): void
    {
        $response = 'Simple String';
        $stringResponse = new StringResponse($response);

        $this->assertSame('Simple String', (string)$stringResponse);
    }

    /**
     * Test __toString method with read terminator.
     */
    public function testToStringWithTerminator(): void
    {
        $response = "HELLO\rWORLD";
        $readTerminator = "\r";
        $stringResponse = new StringResponse($response, $readTerminator);

        $this->assertSame('HELLO', (string)$stringResponse);
    }

    /**
     * Test __toString method with non-printable characters.
     */
    public function testToStringWithNonPrintableCharacters(): void
    {
        // Include some non-printable characters
        $response = "HELLO\x00\x01WORLD";
        $stringResponse = new StringResponse($response);

        // The string should be escaped by ToString::fromString()
        $stringOutput = (string)$stringResponse;
        // Verify it's not the same as the raw response (it should be escaped)
        $this->assertNotSame($response, $stringOutput);
    }

    /**
     * Test __toString method with mixed printable and non-printable characters.
     */
    public function testToStringWithMixedCharacters(): void
    {
        $response = "TEST\x1bDATA";
        $stringResponse = new StringResponse($response);

        $stringOutput = (string)$stringResponse;
        $this->assertStringContainsString('TEST', $stringOutput);
    }

    /**
     * Test response with carriage return terminator.
     */
    public function testResponseWithCarriageReturnTerminator(): void
    {
        $response = "STATUS OK\rEND";
        $readTerminator = "\r";
        $stringResponse = new StringResponse($response, $readTerminator);

        $this->assertSame('STATUS OK', $stringResponse->getResponse());
    }

    /**
     * Test response with multi-character terminator.
     */
    public function testResponseWithMultiCharacterTerminator(): void
    {
        $response = "DATAHERE<<<END";
        $readTerminator = "<<<";
        $stringResponse = new StringResponse($response, $readTerminator);

        $this->assertSame('DATAHERE', $stringResponse->getResponse());
    }

    /**
     * Test response with multi-character terminator not found.
     */
    public function testResponseWithMultiCharacterTerminatorNotFound(): void
    {
        $response = "DATAHERE<<END";
        $readTerminator = "<<<";
        $stringResponse = new StringResponse($response, $readTerminator);

        $this->assertSame('DATAHERE<<END', $stringResponse->getResponse());
    }

    /**
     * Test empty response with empty terminator.
     */
    public function testEmptyResponseWithEmptyTerminator(): void
    {
        $stringResponse = new StringResponse('', '');

        $this->assertSame('', $stringResponse->getResponse());
        $this->assertSame('', (string)$stringResponse);
    }

    /**
     * Test empty response with non-empty terminator.
     */
    public function testEmptyResponseWithNonEmptyTerminator(): void
    {
        $stringResponse = new StringResponse('', "\n");

        $this->assertSame('', $stringResponse->getResponse());
    }

    /**
     * Test response with whitespace characters.
     */
    public function testResponseWithWhitespaceCharacters(): void
    {
        $response = "HELLO   WORLD  \t\t";
        $stringResponse = new StringResponse($response);

        $this->assertSame($response, $stringResponse->getResponse());
    }

    /**
     * Test response with newline characters but different terminator.
     */
    public function testResponseWithNewlineButDifferentTerminator(): void
    {
        $response = "HELLO\nWORLD\nFOO";
        $readTerminator = "\r";
        $stringResponse = new StringResponse($response, $readTerminator);

        $this->assertSame("HELLO\nWORLD\nFOO", $stringResponse->getResponse());
    }

    /**
     * Test response with unicode characters.
     */
    public function testResponseWithUnicodeCharacters(): void
    {
        $response = "Hëllö Wörld äöü";
        $stringResponse = new StringResponse($response);

        $this->assertSame($response, $stringResponse->getResponse());
        // __toString() escapes non-ASCII characters, so verify the output is valid
        $this->assertNotEmpty((string)$stringResponse);
    }

    /**
     * Test response with Unicode terminator.
     */
    public function testResponseWithUnicodeTerminator(): void
    {
        $response = "Hëllö§Wörld";
        $readTerminator = "§";
        $stringResponse = new StringResponse($response, $readTerminator);

        $this->assertSame('Hëllö', $stringResponse->getResponse());
    }

    /**
     * Test that getResponse and __toString are consistent when no terminator is used.
     */
    public function testConsistencyBetweenGetResponseAndToString(): void
    {
        $response = 'Test Content';
        $stringResponse = new StringResponse($response);

        $this->assertSame($stringResponse->getResponse(), (string)$stringResponse);
    }

    /**
     * Test response with only the terminator character.
     */
    public function testResponseWithOnlyTerminator(): void
    {
        $response = "\n";
        $readTerminator = "\n";
        $stringResponse = new StringResponse($response, $readTerminator);

        $this->assertSame('', $stringResponse->getResponse());
    }

    /**
     * Test response with terminator in the middle and different content.
     */
    public function testResponseWithTerminatorInMiddle(): void
    {
        $response = "PART1|PART2|PART3";
        $readTerminator = "|";
        $stringResponse = new StringResponse($response, $readTerminator);

        $this->assertSame('PART1', $stringResponse->getResponse());
    }

    /**
     * Test response with repeated terminator character.
     */
    public function testResponseWithRepeatedTerminator(): void
    {
        $response = "DATA\n\nMORE";
        $readTerminator = "\n";
        $stringResponse = new StringResponse($response, $readTerminator);

        $this->assertSame('DATA', $stringResponse->getResponse());
    }

    /**
     * Test with very long response string.
     */
    public function testLongResponseString(): void
    {
        $response = str_repeat('A', 10000);
        $stringResponse = new StringResponse($response);

        $this->assertSame($response, $stringResponse->getResponse());
        $this->assertSame(10000, strlen($stringResponse->getResponse()));
    }

    /**
     * Test with long response and terminator that splits it.
     */
    public function testLongResponseWithTerminator(): void
    {
        $response = str_repeat('A', 5000) . "\n" . str_repeat('B', 5000);
        $readTerminator = "\n";
        $stringResponse = new StringResponse($response, $readTerminator);

        $this->assertSame(str_repeat('A', 5000), $stringResponse->getResponse());
    }
}
