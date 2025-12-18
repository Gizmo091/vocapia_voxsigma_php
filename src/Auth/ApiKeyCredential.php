<?php

declare(strict_types=1);

namespace Vocapia\Voxsigma\Auth;

/**
 * API Key authentication credential.
 *
 * Uses api-key header.
 */
final class ApiKeyCredential implements CredentialInterface
{
    public function __construct(
        public readonly string $apiKey,
    ) {
    }

    public function applyTo(array &$headers, array &$curlOptions): void
    {
        $headers[] = 'api-key: ' . $this->apiKey;
    }
}
