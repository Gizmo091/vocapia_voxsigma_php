<?php

declare(strict_types=1);

namespace Vocapia\Voxsigma\Driver;

use Vocapia\Voxsigma\Parameter\ParameterCollection;

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
     * @param ParameterCollection|null $parameterDefinitions Parameter definitions for translation
     * @param string|null $audioFile Path to the audio file
     * @param string|null $audioContent Raw audio content (alternative to audioFile)
     * @param string|null $textFile Path to text file (for alignment)
     * @param string|null $stdin Standard input content (for CLI pipeline)
     * @param array<string> $positionalArgs Positional arguments (CLI only, appended at end)
     */
    public function __construct(
        public readonly string $method,
        public readonly array $parameters = [],
        public readonly ?ParameterCollection $parameterDefinitions = null,
        public readonly ?string $audioFile = null,
        public readonly ?string $audioContent = null,
        public readonly ?string $textFile = null,
        public readonly ?string $stdin = null,
        public readonly array $positionalArgs = [],
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
            parameterDefinitions: $this->parameterDefinitions,
            audioFile: $this->audioFile,
            audioContent: $this->audioContent,
            textFile: $this->textFile,
            stdin: $this->stdin,
            positionalArgs: $this->positionalArgs,
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
            parameterDefinitions: $this->parameterDefinitions,
            audioFile: $audioFile,
            audioContent: null,
            textFile: $this->textFile,
            stdin: $this->stdin,
            positionalArgs: $this->positionalArgs,
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
            parameterDefinitions: $this->parameterDefinitions,
            audioFile: $this->audioFile,
            audioContent: $this->audioContent,
            textFile: $this->textFile,
            stdin: $stdin,
            positionalArgs: $this->positionalArgs,
        );
    }
}
