<?php

declare(strict_types=1);

namespace Vocapia\Voxsigma\Method;

/**
 * VoxSigma status method.
 *
 * Checks the status of an async session.
 * REST API only.
 */
final class Status extends AbstractMethod
{
    public function getMethodName(): string
    {
        return 'status';
    }

    /**
     * Set the session ID to check.
     *
     * @param string $id Session ID from async request
     */
    public function session(string $id): self
    {
        $this->parameters['session'] = $id;
        return $this;
    }
}
