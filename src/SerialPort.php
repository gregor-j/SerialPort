<?php

declare(strict_types=1);

namespace GregorJ\SerialPort;

use GregorJ\SerialPort\Exceptions\ConnectionException;
use GregorJ\SerialPort\Exceptions\InvalidValueException;
use GregorJ\SerialPort\Exceptions\StateException;
use GregorJ\SerialPort\Exceptions\TimeoutException;
use GregorJ\SerialPort\Exceptions\WriteException;
use GregorJ\SerialPort\Interfaces\Communication;
use GregorJ\SerialPort\Interfaces\Stream;

use function is_string;
use function sprintf;
use function strlen;
use function substr;

/**
 * Invoke serial port commands on a configured communication and return their response.
 * @package GregorJ\SerialPort
 * @author  Gregor J.
 */
final class SerialPort implements Communication
{
    public const DEFAULT_TIMEOUT = 2.0;
    private Stream $stream;
    private float $timeout;

    /**
     * Create a serial port using a stream class.
     * @param Stream $stream
     * @throws ConnectionException
     * @throws StateException
     */
    public function __construct(Stream $stream)
    {
        $this->stream = $stream;
        $this->timeout = self::DEFAULT_TIMEOUT;
        if (!$this->stream->isOpen()) {
            $this->stream->open();
        }
        $this->stream->setBlocking(true);
    }

    /**
     * Close stream in case it's open.
     */
    public function __destruct()
    {
        if ($this->stream->isOpen()) {
            $this->stream->close();
        }
    }

    /**
     * @inheritDoc
     */
    public function write(string $string, string $terminator = ''): void
    {
        if ($string === '') {
            throw new InvalidValueException('Cannot write empty string.');
        }
        $this->stream->setTimeout($this->timeout);
        $sendString = $string . $terminator;
        $expectLength = strlen($sendString);
        $bytes = $this->stream->write($sendString);
        if ($bytes !== $expectLength) {
            throw new WriteException(sprintf('Expected to write %u bytes, but %u bytes were written.', $expectLength, $bytes));
        }
    }

    /**
     * @inheritDoc
     */
    public function setTimeout(float $seconds): void
    {
        if ($seconds < 0.0) {
            throw new InvalidValueException('Timeout has to be positive.');
        }
        $this->timeout = $seconds;
    }

    /**
     * @inheritDoc
     */
    public function read(string $terminator = ''): string
    {
        $this->stream->setTimeout($this->timeout);
        $response = '';
        $terminatorLength = strlen($terminator);
        do {
            $char = $this->stream->readChar();
            if (is_string($char) && $char !== '') {
                $response .= $char;
            }
        } while (!$this->endsWith($response, $terminator, $terminatorLength) && !$this->stream->timedOut());

        if ($terminator !== '' && !$this->endsWith($response, $terminator, $terminatorLength) && $this->stream->timedOut()) {
            throw new TimeoutException('Timed out while reading.');
        }
        return $response;
    }

    /**
     * Check if the response string ends with the terminator.
     * @param string $response The response string.
     * @param string $terminator The terminator to search for.
     * @param int $terminatorLength The length of the terminator.
     * @return bool
     */
    private function endsWith(string $response, string $terminator, int $terminatorLength): bool
    {
        if ($terminatorLength === 0) {
            return false;
        }
        // Only check the last terminatorLength bytes of the response.
        // Since $tail has exactly the same length as $terminator, a direct equality
        // check suffices — str_contains would be redundant here.
        $tail = substr($response, -$terminatorLength);
        return $tail === $terminator;
    }
}
