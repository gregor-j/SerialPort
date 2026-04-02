<?php

declare(strict_types=1);

namespace Tests\GregorJ\SerialPort;

use Exception;

use function fclose;
use function fgetc;
use function fwrite;
use function in_array;
use function pcntl_fork;
use function pcntl_waitpid;
use function posix_kill;
use function sprintf;
use function stream_socket_accept;
use function stream_socket_get_name;
use function stream_socket_server;
use function strrpos;
use function substr;
use function usleep;

use const SIGTERM;
use const WNOHANG;

/**
 * RFC 2217 Echo Server for unit tests.
 *
 * Accepts Telnet negotiation and echoes back data with proper IAC handling.
 *
 * @package Tests\GregorJ\SerialPort
 */
final class LocalRfc2217Server
{
    private const IAC  = "\xFF";
    private const WILL = "\xFB";
    private const DO   = "\xFD";
    // private const DONT = "\xFE";  // DONT not used in echo server
    private const SB   = "\xFA";
    private const SE   = "\xF0";

    private int $port;
    private int $pid = 0;

    /**
     * Create and start the RFC 2217 echo server.
     * @throws Exception
     */
    public function __construct()
    {
        $server = @stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
        if ($server === false) {
            throw new Exception(sprintf('Failed to create RFC 2217 server socket: %s (%d)', $errstr, $errno));
        }

        $name = (string)stream_socket_get_name($server, false);
        $this->port = (int)substr($name, (int)strrpos($name, ':') + 1);

        $pid = pcntl_fork();
        if ($pid === -1) {
            fclose($server);
            throw new Exception('Failed to fork RFC 2217 echo server process.');
        }

        if ($pid === 0) {
            // Child process: echo server loop
            $this->runEchoServer($server);
            exit(0);
        }

        $this->pid = $pid;
        fclose($server);
        usleep(25000);
    }

    public function getTcpPort(): int
    {
        return $this->port;
    }

    public function __destruct()
    {
        if ($this->pid > 0) {
            posix_kill($this->pid, SIGTERM);
            $status = 0;
            pcntl_waitpid($this->pid, $status, WNOHANG);
            $this->pid = 0;
        }
    }

    /**
     * Echo server that handles RFC 2217 Telnet negotiation.
     *
     * @param resource $server
     */
    private function runEchoServer($server): void
    {
        // @phpstan-ignore while.alwaysTrue
        while (true) {
            $client = @stream_socket_accept($server, 5.0);
            if ($client === false) {
                continue;
            }

            $this->handleConnection($client);
            fclose($client);
        }
    }

    /**
     * Handle a single client connection: negotiate Telnet and echo data.
     *
     * @param resource $client
     */
    private function handleConnection($client): void
    {
        // Accept Telnet negotiation gracefully
        while (true) {
            $byte = fgetc($client);
            if ($byte === false) {
                return;
            }

            // Handle Telnet commands
            if ($byte === self::IAC) {
                $cmd = fgetc($client);
                if ($cmd === false) {
                    return;
                }

                // IAC IAC → literal 0xFF data, echo it back
                if ($cmd === self::IAC) {
                    fwrite($client, self::IAC . self::IAC);
                    continue;
                }

                // Respond to WILL/DO/DONT negotiation
                if ($cmd === self::WILL || $cmd === self::DO) {
                    $opt = fgetc($client);
                    if ($opt !== false) {
                        // Echo back a sensible response
                        fwrite($client, self::IAC . self::DO . $opt);
                    }
                    continue;
                }

                // Skip subnegotiation (IAC SB...IAC SE)
                if ($cmd === self::SB) {
                    $inSub = true;
                    $prevWasIAC = false;
                    while ($inSub) {
                        $b = fgetc($client);
                        if ($b === false) {
                            return;
                        }
                        if ($prevWasIAC && $b === self::SE) {
                            $inSub = false;
                        }
                        $prevWasIAC = ($b === self::IAC);
                    }
                    continue;
                }

                // Other Telnet commands, skip
                continue;
            }

            // Regular data byte: echo it back
            fwrite($client, $byte);
        }
    }
}
