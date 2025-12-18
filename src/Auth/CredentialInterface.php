<?php

declare(strict_types=1);

namespace Vocapia\Voxsigma\Auth;

/**
 * Interface for REST API authentication credentials.
 */
interface CredentialInterface
{
    /**
     * Apply credentials to the HTTP request.
     *
     * @param array<string> $headers HTTP headers (passed by reference)
     * @param array<int, mixed> $curlOptions cURL options (passed by reference)
     */
    public function applyTo(array &$headers, array &$curlOptions): void;
}
