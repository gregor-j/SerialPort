<?php

declare(strict_types=1);

namespace GregorJ\SerialPort;

use GregorJ\SerialPort\Exceptions\ConnectionException;
use GregorJ\SerialPort\Exceptions\InvalidParamException;
use GregorJ\SerialPort\Exceptions\TimeoutException;
use GregorJ\SerialPort\Exceptions\WriteException;
use GregorJ\SerialPort\Interfaces\Communication;
use GregorJ\SerialPort\Interfaces\Stream;
use GregorJ\ToString\ToString;

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
     * @var string[]
     */
    private array $log = [];


    /**
     * Create a serial port using a stream class.
     * @param Stream $stream
     * @throws ConnectionException
     */
    public function __construct(Stream $stream)
    {
        $this->stream = $stream;
        if (!$this->stream->isOpen()) {
            $this->log[] = sprintf('open %s', $this->stream);
            $this->stream->open();
        }
        $this->timeout = self::DEFAULT_TIMEOUT;
        $this->log[] = sprintf('set timeout to %f seconds', $this->timeout);
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
            throw new InvalidParamException('Cannot write empty string.');
        }
        $this->stream->setTimeout($this->timeout);
        $sendString = $string . $terminator;
        $expectLength = strlen($sendString);
        $this->log[] = sprintf('write "%s"', ToString::fromString($sendString));
        $bytes = $this->stream->write($sendString, $this->timeout);
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
            throw new InvalidParamException('Response timeout for SerialPort has to be positive.');
        }
        $this->log[] = sprintf('set timeout to %f seconds', $seconds);
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
            $this->log[] = sprintf('read timed out. partial response "%s"', ToString::fromString($response));
            throw new TimeoutException('Response timed out on serial port.', 0, null, $response);
        }
        $this->log[] = sprintf('read "%s"', ToString::fromString($response));
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
        $tail = substr($response, -$terminatorLength);
        return $tail === $terminator;
    }

    /**
     * String representation of the serial port config for logging.
     * @return string
     */
    public function __toString(): string
    {
        return (string)$this->stream;
    }

    /**
     * @inheritDoc
     */
    public function getLog(): array
    {
        return $this->log;
    }
}
