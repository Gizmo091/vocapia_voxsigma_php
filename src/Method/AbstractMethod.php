<?php

declare(strict_types=1);

namespace Vocapia\Voxsigma\Method;

use Vocapia\Voxsigma\Driver\AsyncHandle;
use Vocapia\Voxsigma\Driver\DriverInterface;
use Vocapia\Voxsigma\Driver\Request;
use Vocapia\Voxsigma\Driver\Response;

/**
 * Abstract base class for VoxSigma methods.
 *
 * Provides fluent builder pattern for configuring method parameters.
 * Each concrete method class defines its specific parameters and method name.
 */
abstract class AbstractMethod
{
    /** @var array<string, mixed> */
    protected array $parameters = [];

    protected ?string $audioFile = null;
    protected ?string $audioContent = null;
    protected ?string $textFile = null;
    protected ?DriverInterface $driver = null;

    /**
     * Get the VoxSigma method name (e.g., 'vrxs_trans', 'vrxs_part').
     */
    abstract public function getMethodName(): string;

    /**
     * Set the audio file path.
     */
    public function file(string $path): static
    {
        $this->audioFile = $path;
        return $this;
    }

    /**
     * Set the raw audio content.
     */
    public function audioContent(string $content): static
    {
        $this->audioContent = $content;
        return $this;
    }

    /**
     * Enable verbose output.
     */
    public function verbose(bool $v = true): static
    {
        $this->parameters['verbose'] = $v;
        return $this;
    }

    /**
     * Set output file path (CLI only).
     */
    public function outputFile(string $path): static
    {
        $this->parameters['outputFile'] = $path;
        return $this;
    }

    /**
     * Set temporary directory (CLI only).
     */
    public function tmpDir(string $path): static
    {
        $this->parameters['tmpDir'] = $path;
        return $this;
    }

    /**
     * Set priority (REST only, 1-10).
     */
    public function priority(int $p): static
    {
        $this->parameters['priority'] = $p;
        return $this;
    }

    /**
     * Bind this method to a driver for execution.
     */
    public function withDriver(DriverInterface $driver): static
    {
        $this->driver = $driver;
        return $this;
    }

    /**
     * Build the driver-agnostic request.
     */
    public function toRequest(): Request
    {
        return new Request(
            method: $this->getMethodName(),
            parameters: $this->parameters,
            audioFile: $this->audioFile,
            audioContent: $this->audioContent,
            textFile: $this->textFile,
        );
    }

    /**
     * Execute the method synchronously.
     */
    public function run(?DriverInterface $driver = null): Response
    {
        $driver = $driver ?? $this->driver;

        if ($driver === null) {
            throw new \LogicException('No driver provided. Use withDriver() or pass a driver to run().');
        }

        return $driver->execute($this->toRequest());
    }

    /**
     * Execute the method asynchronously.
     */
    public function runAsync(?DriverInterface $driver = null): AsyncHandle
    {
        $driver = $driver ?? $this->driver;

        if ($driver === null) {
            throw new \LogicException('No driver provided. Use withDriver() or pass a driver to runAsync().');
        }

        return $driver->executeAsync($this->toRequest());
    }

    /**
     * Get the current parameters.
     *
     * @return array<string, mixed>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * Get the audio file path.
     */
    public function getAudioFile(): ?string
    {
        return $this->audioFile;
    }
}
