<?php

declare(strict_types=1);

namespace Vocapia\Voxsigma\Exception;

/**
 * Exception thrown when a VoxSigma request fails.
 */
final class RequestFailedException extends VoxSigmaException
{
    public function __construct(
        string $message,
        public readonly int $errorCode,
        public readonly ?int $exitCode = null,
        public readonly ?int $httpStatus = null,
    ) {
        parent::__construct($message, $errorCode);
    }
}
