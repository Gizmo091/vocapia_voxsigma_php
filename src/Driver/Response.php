<?php

declare(strict_types=1);

namespace Vocapia\Voxsigma\Driver;

/**
 * Represents a VoxSigma response (driver-agnostic).
 */
final class Response
{
    public function __construct(
        public readonly bool $success,
        public readonly string $xml,
        public readonly ?int $exitCode = null,
        public readonly ?int $httpStatus = null,
        public readonly ?string $error = null,
        public readonly ?int $errorCode = null,
    ) {
    }

    /**
     * Get the XML content.
     */
    public function getXml(): string
    {
        return $this->xml;
    }

    /**
     * Check if the request was successful.
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * Create a successful response.
     */
    public static function success(
        string $xml,
        ?int $exitCode = null,
        ?int $httpStatus = null,
    ): self {
        return new self(
            success: true,
            xml: $xml,
            exitCode: $exitCode,
            httpStatus: $httpStatus,
        );
    }

    /**
     * Create a failed response.
     */
    public static function failure(
        string $error,
        ?int $errorCode = null,
        ?int $exitCode = null,
        ?int $httpStatus = null,
        string $xml = '',
    ): self {
        return new self(
            success: false,
            xml: $xml,
            exitCode: $exitCode,
            httpStatus: $httpStatus,
            error: $error,
            errorCode: $errorCode,
        );
    }
}
