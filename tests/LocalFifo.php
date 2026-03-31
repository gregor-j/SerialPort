<?php

declare(strict_types=1);

namespace Tests\GregorJ\SerialPort;

use Exception;

/**
 * Class LocalFifo
 * Creates a local FIFO and opens a TCP server on a random port to access that FIFO.
 * @package Tests\GregorJ\SerialPort
 * @author Gregor J.
 */
final class LocalFifo
{
    private string $fifoPath;
    private string $pidFile;
    private int $ncPid = 0;
    private int $tcpPort;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $this->assertCliTool('cat');
        $this->assertCliTool('nc');
        $this->fifoPath = $this->createFifo();
        $this->pidFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('fifopid');
        $this->tcpPort = rand(10240, 65535);
        exec(sprintf(
            'cat "%s" | nc -l 127.0.0.1 %u -k > "%s" & echo "$!" > "%s"',
            $this->fifoPath,
            $this->tcpPort,
            $this->fifoPath,
            $this->pidFile
        ));
        usleep(50000);
        $this->ncPid = $this->readPidFile();
    }

    /**
     * Ensure that the given CLI tool is installed.
     * @param string $name
     * @throws Exception
     */
    private function assertCliTool(string $name): void
    {
        $result = shell_exec(sprintf('which %s', $name));
        if ($result === null) {
            throw new Exception(sprintf('%s requires "%s" to be installed.', __CLASS__, $name));
        }
    }

    /**
     * @throws Exception
     */
    private function createFifo(): string
    {
        $fifoPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('fifo');
        if (!posix_mkfifo($fifoPath, 0600)) {
            throw new Exception('Failed to create fifo.');
        }
        return $fifoPath;
    }

    /**
     * @throws Exception
     */
    private function readPidFile(): int
    {
        $pid = file_get_contents($this->pidFile);
        if (!is_string($pid) || !preg_match('~([0-9]+)~', $pid, $matches)) {
            throw new Exception('Failed to read pid.');
        }
        return (int)$matches[1];
    }

    private function isNcRunning(): bool
    {
        if ($this->ncPid === 0) {
            return false;
        }
        $result = shell_exec(sprintf('ps %d', $this->ncPid));
        if (!is_string($result)) {
            return false;
        }
        $resultArray = preg_split("/\n/", $result);
        return (is_array($resultArray) && count($resultArray) > 2);
    }

    public function getTcpPort(): int
    {
        return $this->tcpPort;
    }

    public function __destruct()
    {
        if ($this->isNcRunning()) {
            posix_kill($this->ncPid, SIGTERM);
        }
        if (file_exists($this->fifoPath)) {
            unlink($this->fifoPath);
        }
        if (file_exists($this->pidFile)) {
            unlink($this->pidFile);
        }
    }
}
