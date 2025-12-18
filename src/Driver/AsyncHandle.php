<?php

declare(strict_types=1);

namespace Vocapia\Voxsigma\Driver;

/**
 * Handle for asynchronous VoxSigma operations.
 *
 * CLI: Wraps a running process
 * REST: Wraps a session ID for polling
 */
interface AsyncHandle
{
    /**
     * Check if the operation is still running.
     */
    public function isRunning(): bool;

    /**
     * Check if the operation has finished.
     */
    public function isFinished(): bool;

    /**
     * Wait for the operation to complete and return the response.
     *
     * @param float|null $timeout Maximum time to wait in seconds (null = infinite)
     */
    public function wait(?float $timeout = null): Response;

    /**
     * Cancel the operation if possible.
     */
    public function cancel(): void;

    /**
     * Get the operation identifier.
     *
     * CLI: Process ID (PID)
     * REST: Session ID
     */
    public function getId(): string;
}
