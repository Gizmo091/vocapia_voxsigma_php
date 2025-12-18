<?php

declare(strict_types=1);

namespace Vocapia\Voxsigma\Driver;

/**
 * Represents a VoxSigma request (driver-agnostic).
 *
 * This class contains all the information needed to execute a VoxSigma method,
 * regardless of whether it's executed via CLI or REST.
 */
final class Request
{
    /**
     * @param string $method The VoxSigma method name (e.g., 'vrxs_trans', 'vrxs_part')
     * @param array<string, mixed> $parameters Method parameters (driver-agnostic names)
     * @param string|null $audioFile Path to the audio file
     * @param string|null $audioContent Raw audio content (alternative to audioFile)
     * @param string|null $textFile Path to text file (for alignment)
     * @param string|null $stdin Standard input content (for CLI pipeline)
     */
    public function __construct(
        public readonly string $method,
        public readonly array $parameters = [],
        public readonly ?string $audioFile = null,
        public readonly ?string $audioContent = null,
        public readonly ?string $textFile = null,
        public readonly ?string $stdin = null,
    ) {
    }

    /**
     * Create a new request with additional parameters.
     *
     * @param array<string, mixed> $parameters
     */
    public function withParameters(array $parameters): self
    {
        return new self(
            method: $this->method,
            parameters: array_merge($this->parameters, $parameters),
            audioFile: $this->audioFile,
            audioContent: $this->audioContent,
            textFile: $this->textFile,
            stdin: $this->stdin,
        );
    }

    /**
     * Create a new request with an audio file.
     */
    public function withAudioFile(string $audioFile): self
    {
        return new self(
            method: $this->method,
            parameters: $this->parameters,
            audioFile: $audioFile,
            audioContent: null,
            textFile: $this->textFile,
            stdin: $this->stdin,
        );
    }

    /**
     * Create a new request with stdin content (for pipeline).
     */
    public function withStdin(string $stdin): self
    {
        return new self(
            method: $this->method,
            parameters: $this->parameters,
            audioFile: $this->audioFile,
            audioContent: $this->audioContent,
            textFile: $this->textFile,
            stdin: $stdin,
        );
    }
}
