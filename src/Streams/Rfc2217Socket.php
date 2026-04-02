<?php

declare(strict_types=1);

namespace GregorJ\SerialPort\Streams;

use GregorJ\SerialPort\Exceptions\ConnectionException;
use GregorJ\SerialPort\Exceptions\InvalidValueException;
use GregorJ\SerialPort\Exceptions\WriteException;
use GregorJ\SerialPort\Interfaces\Stream;
use GregorJ\SerialPort\Responses\TcpSocketStatus;

use function chr;
use function error_clear_last;
use function error_get_last;
use function fclose;
use function fgetc;
use function floor;
use function fsockopen;
use function fwrite;
use function in_array;
use function is_array;
use function is_resource;
use function microtime;
use function pack;
use function sprintf;
use function str_replace;
use function stream_get_meta_data;
use function stream_set_blocking;
use function stream_set_timeout;
use function strlen;
use function substr;

/**
 * RFC 2217 (Telnet COM Port Control Option) stream implementation.
 *
 * Provides a TCP connection to an RFC 2217-compliant serial-to-TCP adapter with
 * full control over serial port parameters (baud rate, data bits, parity, stop bits,
 * flow control, modem control lines).
 *
 * Typical usage — configure all parameters BEFORE handing the stream to SerialPort:
 *
 *   $stream = new Rfc2217Socket('10.0.0.1', 2001);
 *   $stream->setBaudRate(9600);
 *   $stream->setDataBits(8);
 *   $stream->setParity(Rfc2217Socket::PARITY_NONE);
 *   $stream->setStopBits(Rfc2217Socket::STOP_BITS_1);
 *   $stream->setFlowControl(Rfc2217Socket::FLOW_NONE);
 *   $serialPort = new SerialPort($stream);   // open() + Telnet negotiation happen here
 *
 * Setters called after open() send the new parameter immediately to the server.
 *
 * @link https://www.rfc-editor.org/rfc/rfc2217
 * @package GregorJ\SerialPort\Streams
 * @author  Gregor J.
 */
final class Rfc2217Socket implements Stream
{
    /**
     * Telnet protocol bytes (RFC 854)
     */

    /** Interpret As Command */
    private const IAC  = "\xFF";
    /** Option negotiation: sender WILL use this option */
    private const WILL = "\xFB";
    /** Option negotiation: sender WON'T use this option */
    private const WONT = "\xFC";
    /** Option negotiation: sender requests the other side DO this option */
    private const DO   = "\xFD";
    /** Option negotiation: sender requests the other side DON'T use this option */
    private const DONT = "\xFE";
    /** Subnegotiation begin */
    private const SB   = "\xFA";
    /** Subnegotiation end */
    private const SE   = "\xF0";

    /**
     * Telnet option codes
     */

    /** Binary Transmission (RFC 856) */
    private const OPT_BINARY   = "\x00";
    /** Suppress Go-Ahead (RFC 858) */
    private const OPT_SGA      = "\x03";
    /** COM-Port-Control Option (RFC 2217) */
    private const OPT_COM_PORT = "\x2C";

    /**
     * RFC 2217 COM Port Control sub-command codes (client → server)
     */

    /** Set the baud rate (4-byte big-endian value) */
    private const CPO_SET_BAUDRATE = "\x01";
    /** Set the number of data bits (1-byte value: 5, 6, 7 or 8) */
    private const CPO_SET_DATASIZE = "\x02";
    /** Set the parity (1-byte value: 1=NONE…5=SPACE) */
    private const CPO_SET_PARITY   = "\x03";
    /** Set the number of stop bits (1-byte value: 1=1bit, 2=2bits, 3=1.5bits) */
    private const CPO_SET_STOPSIZE = "\x04";
    /**
     * Set flow control / modem control lines (1-byte value).
     * Values for flow control: 1=none, 2=XON/XOFF, 3=RTS/CTS, 5=DTR/DSR
     * Values for DTR: 8=ON, 9=OFF
     * Values for RTS: 11=ON, 12=OFF
     */
    private const CPO_SET_CONTROL  = "\x05";
    /** Send a BREAK signal (4-byte duration in milliseconds; 0 = adapter default) */
    private const CPO_SEND_BREAK   = "\x0A";

    /**
     * Parity constants
     */

    public const PARITY_NONE  = 1;
    public const PARITY_ODD   = 2;
    public const PARITY_EVEN  = 3;
    public const PARITY_MARK  = 4;
    public const PARITY_SPACE = 5;

    /**
     * Stop bits constants
     */

    public const STOP_BITS_1   = 1;
    public const STOP_BITS_2   = 2;
    public const STOP_BITS_1_5 = 3;

    /**
     * Flow control constants (SET_CONTROL values)
     */

    /** No flow control */
    public const FLOW_NONE      = 1;
    /** Software flow control (XON/XOFF) */
    public const FLOW_XON_XOFF  = 2;
    /** Hardware flow control (RTS/CTS) */
    public const FLOW_RTS_CTS   = 3;
    /** Hardware flow control (DTR/DSR) */
    public const FLOW_DTR_DSR   = 5;

    /**
     * Defaults
     */

    public const DEFAULT_CONNECTION_TIMEOUT = 2.0;
    public const DEFAULT_WRITE_TIMEOUT      = 2.0;
    public const DEFAULT_BAUD_RATE          = 9600;
    public const DEFAULT_DATA_BITS          = 8;
    public const DEFAULT_PARITY             = self::PARITY_NONE;
    public const DEFAULT_STOP_BITS          = self::STOP_BITS_1;
    public const DEFAULT_FLOW_CONTROL       = self::FLOW_NONE;

    /**
     * Properties
     */

    private string $host;
    private int $port;
    private float $connectionTimeout;

    private int $baudRate;
    private int $dataBits;
    private int $parity;
    private int $stopBits;
    private int $flowControl;

    /**
     * @var resource|null
     */
    private $socket = null;

    /**
     * Create an RFC 2217 socket.
     *
     * All serial parameters default to typical RS-232 values.
     * Call the setXxx() methods to override them BEFORE passing this object to SerialPort
     * (or before calling open() directly).
     *
     * @param string     $host              Hostname or IP address of the serial adapter.
     * @param int        $port              TCP port of the serial adapter.
     * @param float|null $connectionTimeout Connection timeout in seconds (default: 2s).
     * @throws InvalidValueException
     */
    public function __construct(string $host, int $port, float $connectionTimeout = null)
    {
        $connectionTimeout = $connectionTimeout ?? self::DEFAULT_CONNECTION_TIMEOUT;
        if ($connectionTimeout < 0.0) {
            throw new InvalidValueException('Connection timeout for Rfc2217Socket has to be positive.');
        }
        $this->host              = $host;
        $this->port              = $port;
        $this->connectionTimeout = $connectionTimeout;
        $this->baudRate          = self::DEFAULT_BAUD_RATE;
        $this->dataBits          = self::DEFAULT_DATA_BITS;
        $this->parity            = self::DEFAULT_PARITY;
        $this->stopBits          = self::DEFAULT_STOP_BITS;
        $this->flowControl       = self::DEFAULT_FLOW_CONTROL;
    }

    /**
     * Rfc2217Socket destructor — closes the connection if still open.
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * Set the baud rate for the serial port.
     *
     * Common values: 300, 1200, 2400, 4800, 9600, 19200, 38400, 57600, 115200.
     * If the socket is already open the new rate is sent to the adapter immediately.
     *
     * @throws InvalidValueException
     */
    public function setBaudRate(int $baudRate): void
    {
        if ($baudRate <= 0) {
            throw new InvalidValueException('Baud rate must be a positive integer.');
        }
        $this->baudRate = $baudRate;
        if ($this->isOpen()) {
            $this->sendComPortOption(self::CPO_SET_BAUDRATE, pack('N', $this->baudRate));
        }
    }

    /**
     * Set the number of data bits per character.
     *
     * Allowed values: 5, 6, 7, 8.
     * If the socket is already open the new value is sent to the adapter immediately.
     *
     * @throws InvalidValueException
     */
    public function setDataBits(int $dataBits): void
    {
        if (!in_array($dataBits, [5, 6, 7, 8], true)) {
            throw new InvalidValueException('Data bits must be 5, 6, 7 or 8.');
        }
        $this->dataBits = $dataBits;
        if ($this->isOpen()) {
            $this->sendComPortOption(self::CPO_SET_DATASIZE, chr($this->dataBits));
        }
    }

    /**
     * Set the parity mode.
     *
     * Use one of the PARITY_* class constants.
     * If the socket is already open the new value is sent to the adapter immediately.
     *
     * @throws InvalidValueException
     */
    public function setParity(int $parity): void
    {
        if (!in_array($parity, [self::PARITY_NONE, self::PARITY_ODD, self::PARITY_EVEN, self::PARITY_MARK, self::PARITY_SPACE], true)) {
            throw new InvalidValueException(
                sprintf('Invalid parity value %d. Use one of the PARITY_* constants.', $parity)
            );
        }
        $this->parity = $parity;
        if ($this->isOpen()) {
            $this->sendComPortOption(self::CPO_SET_PARITY, chr($this->parity));
        }
    }

    /**
     * Set the number of stop bits.
     *
     * Use one of the STOP_BITS_* class constants.
     * If the socket is already open the new value is sent to the adapter immediately.
     *
     * @throws InvalidValueException
     */
    public function setStopBits(int $stopBits): void
    {
        if (!in_array($stopBits, [self::STOP_BITS_1, self::STOP_BITS_2, self::STOP_BITS_1_5], true)) {
            throw new InvalidValueException(
                sprintf('Invalid stop bits value %d. Use one of the STOP_BITS_* constants.', $stopBits)
            );
        }
        $this->stopBits = $stopBits;
        if ($this->isOpen()) {
            $this->sendComPortOption(self::CPO_SET_STOPSIZE, chr($this->stopBits));
        }
    }

    /**
     * Set the flow control mode.
     *
     * Use one of the FLOW_* class constants.
     * If the socket is already open the new value is sent to the adapter immediately.
     *
     * @throws InvalidValueException
     */
    public function setFlowControl(int $flowControl): void
    {
        if (!in_array($flowControl, [self::FLOW_NONE, self::FLOW_XON_XOFF, self::FLOW_RTS_CTS, self::FLOW_DTR_DSR], true)) {
            throw new InvalidValueException(
                sprintf('Invalid flow control value %d. Use one of the FLOW_* constants.', $flowControl)
            );
        }
        $this->flowControl = $flowControl;
        if ($this->isOpen()) {
            $this->sendFlowControl();
        }
    }

    /**
     * Assert the RTS (Request To Send) modem control line.
     *
     * Can only be called when the socket is open (or triggers auto-connect).
     *
     * @throws ConnectionException
     */
    public function setRts(bool $on): void
    {
        // RFC 2217 SET_CONTROL: 11 = Set RTS ON, 12 = Set RTS OFF
        $this->getSocket();
        $this->sendComPortOption(self::CPO_SET_CONTROL, chr($on ? 11 : 12));
    }

    /**
     * Assert the DTR (Data Terminal Ready) modem control line.
     *
     * Can only be called when the socket is open (or triggers auto-connect).
     *
     * @throws ConnectionException
     */
    public function setDtr(bool $on): void
    {
        // RFC 2217 SET_CONTROL: 8 = Set DTR ON, 9 = Set DTR OFF
        $this->getSocket();
        $this->sendComPortOption(self::CPO_SET_CONTROL, chr($on ? 8 : 9));
    }

    /**
     * Send a BREAK signal on the serial line.
     *
     * @param int $durationMs Break duration in milliseconds. Pass 0 to let the adapter
     *                        use its built-in default duration.
     * @throws ConnectionException
     * @throws InvalidValueException
     */
    public function sendBreak(int $durationMs = 0): void
    {
        if ($durationMs < 0) {
            throw new InvalidValueException('Break duration must not be negative.');
        }
        $this->getSocket();
        $this->sendComPortOption(self::CPO_SEND_BREAK, pack('N', $durationMs));
    }

    /**
     * @inheritDoc
     */
    public function isOpen(): bool
    {
        return is_resource($this->socket);
    }

    /**
     * @inheritDoc
     */
    public function open(): void
    {
        if (!$this->isOpen()) {
            $this->getSocket();
        }
    }

    /**
     * @inheritDoc
     */
    public function close(): void
    {
        if (is_resource($this->socket)) {
            fclose($this->socket);
            $this->socket = null;
        }
    }

    /**
     * @inheritDoc
     *
     * IAC (0xFF) bytes in $string are transparently escaped on the wire
     * per RFC 854 §3 and decoded on the receiving end.
     * The return value reflects the number of *data* bytes (before escaping).
     */
    public function write(string $string, float $timeoutSeconds = null): int
    {
        // Validate parameters BEFORE attempting to connect
        if ($string === '') {
            throw new InvalidValueException('Cannot write empty string.');
        }
        $timeoutSeconds = $timeoutSeconds ?? self::DEFAULT_WRITE_TIMEOUT;
        if ($timeoutSeconds < 0.0) {
            throw new InvalidValueException('Write timeout for Rfc2217Socket must be positive.');
        }

        $socket = $this->getSocket();

        // IAC bytes (0xFF) in data must be doubled on the wire (RFC 854).
        $escaped       = str_replace(self::IAC, self::IAC . self::IAC, $string);
        $inputLength   = strlen($string);
        $escapedLength = strlen($escaped);
        $offset        = 0;
        $zeroWriteStart = null;

        error_clear_last();
        while ($offset < $escapedLength) {
            $bytes = fwrite($socket, substr($escaped, $offset), max(0, $escapedLength - $offset));

            if ($bytes === 0) {
                if ($zeroWriteStart === null) {
                    $zeroWriteStart = microtime(true);
                } elseif ((microtime(true) - $zeroWriteStart) > $timeoutSeconds) {
                    throw new WriteException(
                        sprintf(
                            'Write operation timed out after %ss while writing to RFC 2217 connection %s:%u.',
                            $timeoutSeconds,
                            $this->host,
                            $this->port
                        )
                    );
                }
                continue;
            }

            if ($bytes === false) {
                $lastError = error_get_last();
                throw new WriteException(
                    sprintf(
                        'Failed to write to RFC 2217 connection %s:%u: %s',
                        $this->host,
                        $this->port,
                        is_array($lastError) ? $lastError['message'] : 'Unknown error.'
                    ),
                    is_array($lastError) ? $lastError['type'] : 0
                );
            }

            $zeroWriteStart = null;
            $offset += $bytes;
        }

        // Return number of original (unescaped) data bytes.
        return $inputLength;
    }

    /**
     * @inheritDoc
     *
     * Transparently strips any Telnet control sequences (IAC …) that arrive
     * from the server mid-stream. A double IAC (0xFF 0xFF) is decoded back to
     * a single 0xFF data byte.
     */
    public function readChar(): ?string
    {
        $socket = $this->getSocket();
        while (true) {
            $byte = fgetc($socket);
            if ($byte === false) {
                return null;
            }
            // Plain data byte — return it directly.
            if ($byte !== self::IAC) {
                return $byte;
            }
            // Start of a Telnet control sequence.
            $cmd = fgetc($socket);
            if ($cmd === false) {
                return null;
            }
            // IAC IAC → escaped literal 0xFF data byte.
            if ($cmd === self::IAC) {
                return "\xFF";
            }
            // IAC SB … IAC SE → subnegotiation (e.g. server confirming a COM port setting).
            if ($cmd === self::SB) {
                $this->consumeSubnegotiation($socket);
                continue;
            }
            // IAC WILL/WONT/DO/DONT <option> → option negotiation.
            if ($cmd === self::WILL || $cmd === self::WONT || $cmd === self::DO || $cmd === self::DONT) {
                $opt = fgetc($socket);
                if ($opt !== false) {
                    $this->handleOptionNegotiation($cmd, $opt, $socket);
                }
                continue;
            }
            // Any other 2-byte IAC command (e.g. IAC GA) → ignore.
        }
    }

    /**
     * @inheritDoc
     */
    public function setTimeout(float $seconds): bool
    {
        if ($seconds < 0.0) {
            throw new InvalidValueException('Response timeout for Rfc2217Socket has to be positive.');
        }
        $sec  = floor($seconds);
        $usec = ($seconds - $sec) * 1_000_000;
        return stream_set_timeout($this->getSocket(), (int)$sec, (int)$usec);
    }

    /**
     * @inheritDoc
     */
    public function setBlocking(bool $blocking): bool
    {
        return stream_set_blocking($this->getSocket(), $blocking);
    }

    /**
     * @inheritDoc
     */
    public function timedOut(): bool
    {
        $metadata = stream_get_meta_data($this->getSocket());
        return (bool)$metadata['timed_out'];
    }

    /**
     * @inheritDoc
     */
    public function getStatus(): TcpSocketStatus
    {
        return new TcpSocketStatus(stream_get_meta_data($this->getSocket()));
    }

    /**
     * Return the open socket resource, connecting (and negotiating) if not yet open.
     *
     * Note: $this->socket is assigned BEFORE negotiate() is called, so any
     * sendComPortOption() call inside negotiate() that calls getSocket() will
     * find an already-set socket and return immediately — no infinite recursion.
     *
     * @return resource
     * @throws ConnectionException
     */
    private function getSocket()
    {
        if (!is_resource($this->socket)) {
            $socket = @fsockopen($this->host, $this->port, $errno, $errstr, $this->connectionTimeout);
            if (!is_resource($socket)) {
                throw new ConnectionException(
                    sprintf('RFC 2217 TCP connection to %s:%u failed: %s', $this->host, $this->port, $errstr),
                    $errno
                );
            }
            // Assign BEFORE negotiate() so re-entrant getSocket() calls succeed.
            $this->socket = $socket;
            $this->negotiate();
        }
        // PHPStan cannot prove $this->socket is still a resource after negotiate(),
        // because negotiate() is a method call that could theoretically modify it.
        // We document the invariant with an assertion: socket is always set here.
        assert(is_resource($this->socket));
        return $this->socket;
    }

    /**
     * Perform Telnet option negotiation and send the current COM port parameters.
     *
     * Called once, immediately after the TCP connection is established.
     * Uses $this->socket directly (not getSocket()) — see note in getSocket().
     */
    private function negotiate(): void
    {
        // $this->socket is guaranteed to be set before negotiate() is called (see getSocket()).
        assert(is_resource($this->socket));
        // Request binary transmission and Suppress-Go-Ahead (standard Telnet setup),
        // then ask the server to support the RFC 2217 COM Port Control option.
        fwrite(
            $this->socket,
            self::IAC . self::WILL . self::OPT_BINARY   // We send binary
            . self::IAC . self::DO   . self::OPT_BINARY  // Please send binary
            . self::IAC . self::WILL . self::OPT_SGA     // We suppress Go-Ahead
            . self::IAC . self::DO   . self::OPT_SGA     // Please suppress Go-Ahead
            . self::IAC . self::DO   . self::OPT_COM_PORT // Please support RFC 2217
        );

        // Send the current COM port configuration to the server.
        $this->sendComPortOption(self::CPO_SET_BAUDRATE, pack('N', $this->baudRate));
        $this->sendComPortOption(self::CPO_SET_DATASIZE, chr($this->dataBits));
        $this->sendComPortOption(self::CPO_SET_PARITY, chr($this->parity));
        $this->sendComPortOption(self::CPO_SET_STOPSIZE, chr($this->stopBits));
        $this->sendFlowControl();
    }

    /**
     * Send an RFC 2217 COM Port Option subnegotiation.
     *
     * Frame format: IAC SB COM-PORT-OPTION <command> [<value>] IAC SE
     * Any IAC (0xFF) bytes in $value are automatically escaped.
     *
     * @param non-empty-string $command Single-byte RFC 2217 sub-command code.
     * @param string           $value   Sub-command value (may be empty).
     */
    private function sendComPortOption(string $command, string $value): void
    {
        // $this->socket is guaranteed to be set when this private method is called.
        assert(is_resource($this->socket));
        // Escape IAC bytes within the value (RFC 854).
        $escapedValue = str_replace(self::IAC, self::IAC . self::IAC, $value);
        fwrite(
            $this->socket,
            self::IAC . self::SB . self::OPT_COM_PORT . $command . $escapedValue . self::IAC . self::SE
        );
    }

    /**
     * Send the current flow control setting to the server via SET_CONTROL.
     */
    private function sendFlowControl(): void
    {
        $this->sendComPortOption(self::CPO_SET_CONTROL, chr($this->flowControl));
    }

    /**
     * Respond to a Telnet option negotiation from the server.
     *
     * - We accept WILL for: COM_PORT, BINARY, SGA (respond with DO).
     * - We accept DO  for: BINARY, SGA (respond with WILL).
     * - All other WILL/DO are refused (respond with DONT/WONT).
     * - WONT/DONT are acknowledgements; no response is required.
     *
     * @param string   $cmd    One of WILL, WONT, DO, DONT.
     * @param string   $opt    The Telnet option byte.
     * @param resource $socket The open socket resource.
     */
    private function handleOptionNegotiation(string $cmd, string $opt, $socket): void
    {
        if ($cmd === self::WILL) {
            // Server announces support for an option — accept the ones we know about.
            $accept = $opt === self::OPT_COM_PORT || $opt === self::OPT_BINARY || $opt === self::OPT_SGA;
            fwrite($socket, self::IAC . ($accept ? self::DO : self::DONT) . $opt);
        } elseif ($cmd === self::DO) {
            // Server asks us to use an option — agree to BINARY and SGA.
            $accept = $opt === self::OPT_BINARY || $opt === self::OPT_SGA;
            fwrite($socket, self::IAC . ($accept ? self::WILL : self::WONT) . $opt);
        }
        // WONT / DONT → no response required.
    }

    /**
     * Consume (and discard) a Telnet subnegotiation sequence.
     *
     * Called after the SB byte has already been consumed. Reads bytes until
     * the IAC SE terminator is found.
     *
     * Server-side RFC 2217 confirmations (e.g. SET-BAUDRATE response) arrive as
     * subnegotiations. We currently discard their content; extend this method if
     * you need to act on server confirmations.
     *
     * @param resource $socket The open socket resource.
     */
    private function consumeSubnegotiation($socket): void
    {
        $prevWasIAC = false;
        while (true) {
            $byte = fgetc($socket);
            if ($byte === false) {
                return;
            }
            if ($prevWasIAC) {
                if ($byte === self::SE) {
                    return; // IAC SE → end of subnegotiation
                }
                // IAC followed by anything else (e.g. IAC IAC inside sub-data): continue.
                $prevWasIAC = false;
                continue;
            }
            $prevWasIAC = ($byte === self::IAC);
        }
    }
}
