<?php

declare(strict_types=1);

namespace Vocapia\Voxsigma\Auth;

/**
 * HTTP Basic Authentication credential.
 *
 * Uses Authorization: Basic base64(username:password)
 */
final class UserPasswordCredential implements CredentialInterface
{
    public function __construct(
        public readonly string $username,
        public readonly string $password,
    ) {
    }

    public function applyTo(array &$headers, array &$curlOptions): void
    {
        $curlOptions[\CURLOPT_USERPWD] = $this->username . ':' . $this->password;
    }

    public function toCurlArgs(): array
    {
        return ['-u', $this->username . ':' . $this->password];
    }
}
