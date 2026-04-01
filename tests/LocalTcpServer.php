<?php

declare(strict_types=1);

namespace Tests\GregorJ\SerialPort;

use Exception;

use function fclose;
use function feof;
use function fread;
use function fwrite;
use function pcntl_fork;
use function pcntl_waitpid;
use function posix_kill;
use function sprintf;
use function stream_set_blocking;
use function stream_socket_accept;
use function stream_socket_get_name;
use function stream_socket_server;
use function strrpos;
use function substr;
use function usleep;

use const SIGTERM;
use const WNOHANG;

/**
 * Class LocalTcpServer
 *
 * A PHP-native TCP echo server for use in tests.
 *
 * Advantages over the nc/FIFO-based LocalFifo:
 *  - No external tools required (no nc, cat, mkfifo)
 *  - No temporary files on disk
 *  - Port is guaranteed free: the server socket is created before forking,
 *    so no other process can steal it between bind and accept
 *  - Portable to any system where PHP runs with ext-pcntl and ext-posix
 *
 * The server runs in a forked child process and echoes back everything it
 * receives on each accepted connection. It exits cleanly when sent SIGTERM.
 *
 * @package Tests\GregorJ\SerialPort
 * @author  Gregor J.
 */
final class LocalTcpServer
{
    private int $port;
    private int $pid = 0;

    /**
     * Create and start the echo server.
     * The server socket is bound before forking to guarantee port availability.
     * @throws Exception
     */
    public function __construct()
    {
        // Bind to port 0 so the OS assigns a free port atomically.
        $server = @stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
        if ($server === false) {
            throw new Exception(sprintf('Failed to create server socket: %s (%d)', $errstr, $errno));
        }

        // Extract the OS-assigned port from the bound address.
        // strrpos handles both IPv4 (127.0.0.1:PORT) and IPv6 ([::1]:PORT).
        $name = (string)stream_socket_get_name($server, false);
        $this->port = (int)substr($name, (int)strrpos($name, ':') + 1);

        $pid = pcntl_fork();
        if ($pid === -1) {
            fclose($server);
            throw new Exception('Failed to fork echo server process.');
        }

        if ($pid === 0) {
            // Child process: run echo loop until killed.
            $this->runEchoServer($server);
            exit(0); // @codeCoverageIgnore
        }

        // Parent: hand server socket ownership to the child.
        $this->pid = $pid;
        fclose($server);

        // Brief pause to ensure the child has entered stream_socket_accept()
        // before any test attempts to connect.
        usleep(25000);
    }

    /**
     * Accept connections and echo received data back to the sender.
     * Runs until the process receives SIGTERM.
     *
     * @param resource $server
     */
    private function runEchoServer($server): void
    {
        stream_set_blocking($server, true);

        // @phpstan-ignore while.alwaysTrue
        while (true) {
            $client = @stream_socket_accept($server, 5.0);
            if ($client === false) {
                // Accept timed out or was interrupted; try again.
                continue;
            }

            stream_set_blocking($client, true);

            // Echo all received bytes back to the client.
            while (!feof($client)) {
                $data = fread($client, 8192);
                if ($data === false || $data === '') {
                    break;
                }
                fwrite($client, $data);
            }

            fclose($client);
        }
    }

    /**
     * Return the TCP port on which the echo server is listening.
     */
    public function getTcpPort(): int
    {
        return $this->port;
    }

    /**
     * Stop the echo server child process on shutdown.
     */
    public function __destruct()
    {
        if ($this->pid > 0) {
            posix_kill($this->pid, SIGTERM);
            $status = 0;
            pcntl_waitpid($this->pid, $status, WNOHANG);
            $this->pid = 0;
        }
    }
}
