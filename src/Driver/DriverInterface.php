<?php

declare(strict_types=1);

namespace Vocapia\Voxsigma\Driver;

/**
 * Interface for VoxSigma execution drivers.
 *
 * Implementations handle the actual execution of VoxSigma methods,
 * either via local CLI binaries or remote REST API.
 */
interface DriverInterface
{
    /**
     * Execute a request synchronously.
     */
    public function execute(Request $request): Response;

    /**
     * Execute a request asynchronously.
     */
    public function executeAsync(Request $request): AsyncHandle;

    /**
     * Check if this driver supports pipeline execution.
     *
     * Only CLI driver supports piping multiple commands together.
     */
    public function supportsPipeline(): bool;
}
