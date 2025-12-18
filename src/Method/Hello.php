<?php

declare(strict_types=1);

namespace Vocapia\Voxsigma\Method;

/**
 * VoxSigma hello method.
 *
 * Tests the REST API connection and authentication.
 * REST API only.
 */
final class Hello extends AbstractMethod
{
    public function getMethodName(): string
    {
        return 'hello';
    }

    /**
     * @inheritDoc
     */
    protected static function defineParameters(): array
    {
        return [];
    }
}
