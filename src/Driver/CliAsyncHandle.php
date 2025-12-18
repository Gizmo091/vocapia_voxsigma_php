<?php

declare(strict_types=1);

namespace Vocapia\Voxsigma\Driver;

use Vocapia\Voxsigma\Exception\DriverException;

/**
 * Async handle for CLI driver (wraps a running process).
 */
final class CliAsyncHandle implements AsyncHandle
{
    /** @var resource|null */
    private $process;

    /** @var resource|null */
    private $stdout;

    /** @var resource|null */
    private $stderr;

    private ?Response $result = null;

    /**
     * @param resource $process Process resource from proc_open
     * @param resource $stdout Stdout pipe
     * @param resource $stderr Stderr pipe
     * @param int $pid Process ID
     */
    public function __construct(
        $process,
        $stdout,
        $stderr,
        private readonly int $pid,
    ) {
        $this->process = $process;
        $this->stdout = $stdout;
        $this->stderr = $stderr;
    }

    public function isRunning(): bool
    {
        if ($this->result !== null) {
            return false;
        }

        if ($this->process === null) {
            return false;
        }

        $status = proc_get_status($this->process);
        return $status['running'];
    }

    public function isFinished(): bool
    {
        return !$this->isRunning();
    }

    public function wait(?float $timeout = null): Response
    {
        if ($this->result !== null) {
            return $this->result;
        }

        if ($this->process === null || $this->stdout === null || $this->stderr === null) {
            throw new DriverException('Process already closed');
        }

        $startTime = microtime(true);

        // Wait for process to finish
        while ($this->isRunning()) {
            if ($timeout !== null && (microtime(true) - $startTime) > $timeout) {
                throw new DriverException('Process timeout');
            }
            usleep(10000); // 10ms
        }

        // Read output
        $stdout = stream_get_contents($this->stdout);
        $stderr = stream_get_contents($this->stderr);

        fclose($this->stdout);
        fclose($this->stderr);

        $exitCode = proc_close($this->process);

        $this->process = null;
        $this->stdout = null;
        $this->stderr = null;

        if ($stdout === false) {
            $stdout = '';
        }
        if ($stderr === false) {
            $stderr = '';
        }

        if ($exitCode === 0) {
            $this->result = Response::success($stdout, $exitCode);
        } else {
            $this->result = Response::failure(
                error: $stderr ?: 'Process failed with exit code ' . $exitCode,
                errorCode: $exitCode,
                exitCode: $exitCode,
                xml: $stdout,
            );
        }

        return $this->result;
    }

    public function cancel(): void
    {
        if ($this->process !== null && $this->isRunning()) {
            proc_terminate($this->process, 15); // SIGTERM

            if ($this->stdout !== null) {
                fclose($this->stdout);
                $this->stdout = null;
            }
            if ($this->stderr !== null) {
                fclose($this->stderr);
                $this->stderr = null;
            }

            proc_close($this->process);
            $this->process = null;
        }
    }

    public function getId(): string
    {
        return (string) $this->pid;
    }
}
