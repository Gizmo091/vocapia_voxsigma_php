<?php

declare(strict_types=1);

namespace Vocapia\Voxsigma\Method;

use Vocapia\Voxsigma\Parameter\Parameter;

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
     * @inheritDoc
     */
    protected static function defineParameters(): array
    {
        return [
            new Parameter('session', '', 'session'), // REST only
        ];
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
