<?php

declare(strict_types=1);

namespace Vocapia\Voxsigma\Config;

use Vocapia\Voxsigma\Auth\CredentialInterface;

/**
 * Configuration for the VoxSigma SDK.
 *
 * Supports both CLI (local binaries) and REST (API) drivers.
 */
final class Configuration
{
    public function __construct(
        /** Driver type: 'cli' or 'rest' */
        public readonly string $driver = 'cli',

        /** CLI: VoxSigma root directory (e.g., /usr/local/vrxs) */
        public readonly string $root = '/usr/local/vrxs',

        /** CLI: Binary directory (defaults to $root/bin) */
        public readonly ?string $bin = null,

        /** CLI: Temporary directory */
        public readonly string $tmp = '/tmp',

        /** REST: Base URL */
        public readonly ?string $baseUrl = null,

        /** REST: Authentication credential */
        public readonly ?CredentialInterface $credential = null,
    ) {
    }

    /**
     * Create configuration from environment variables.
     *
     * Reads: VRXS_ROOT, VRXS_BIN, VRXS_TMP, VOXSIGMA_URL, VOXSIGMA_USER, VOXSIGMA_PASS, VOXSIGMA_API_KEY
     */
    public static function fromEnvironment(): self
    {
        $root = getenv('VRXS_ROOT') ?: '/usr/local/vrxs';
        $bin = getenv('VRXS_BIN') ?: null;
        $tmp = getenv('VRXS_TMP') ?: '/tmp';

        $baseUrl = getenv('VOXSIGMA_URL') ?: null;

        return new self(
            driver: $baseUrl ? 'rest' : 'cli',
            root: $root,
            bin: $bin ?: null,
            tmp: $tmp,
            baseUrl: $baseUrl ?: null,
        );
    }

    /**
     * Create CLI configuration.
     */
    public static function cli(
        string $root = '/usr/local/vrxs',
        ?string $bin = null,
        string $tmp = '/tmp',
    ): self {
        return new self(
            driver: 'cli',
            root: $root,
            bin: $bin,
            tmp: $tmp,
        );
    }

    /**
     * Create REST configuration.
     */
    public static function rest(
        string $baseUrl,
        CredentialInterface $credential,
    ): self {
        return new self(
            driver: 'rest',
            baseUrl: $baseUrl,
            credential: $credential,
        );
    }

    /**
     * Get the binary directory path.
     */
    public function getBinPath(): string
    {
        return $this->bin ?? ($this->root . '/bin');
    }
}
