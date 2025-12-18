<?php

declare(strict_types=1);

namespace Vocapia\Voxsigma\Pipeline;

use Vocapia\Voxsigma\Driver\CliDriver;
use Vocapia\Voxsigma\Driver\Request;
use Vocapia\Voxsigma\Driver\Response;
use Vocapia\Voxsigma\Exception\DriverException;
use Vocapia\Voxsigma\Method\AbstractMethod;
use Vocapia\Voxsigma\Method\Dtmf;
use Vocapia\Voxsigma\Method\Lid;
use Vocapia\Voxsigma\Method\Part;
use Vocapia\Voxsigma\Method\Trans;

/**
 * Pipeline for chaining VoxSigma CLI commands.
 *
 * Only supported with CLI driver (uses Unix pipes).
 */
final class Pipeline
{
    /** @var AbstractMethod[] */
    private array $stages = [];

    private ?string $inputFile = null;
    private ?CliDriver $driver = null;

    /**
     * Create a new pipeline.
     */
    public static function create(): self
    {
        return new self();
    }

    /**
     * Set the input audio file.
     */
    public function input(string $audioFile): self
    {
        $this->inputFile = $audioFile;
        return $this;
    }

    /**
     * Bind this pipeline to a CLI driver.
     */
    public function withDriver(CliDriver $driver): self
    {
        $this->driver = $driver;
        return $this;
    }

    /**
     * Add a method stage to the pipeline.
     */
    public function add(AbstractMethod $method): self
    {
        $this->stages[] = $method;
        return $this;
    }

    /**
     * Add DTMF detection stage.
     */
    public function dtmf(): self
    {
        $this->stages[] = new Dtmf();
        return $this;
    }

    /**
     * Create and return a Part stage builder.
     *
     * After configuring, call done() to return to the pipeline.
     */
    public function part(): PipelineStage
    {
        return new PipelineStage($this, new Part());
    }

    /**
     * Create and return a Lid stage builder.
     *
     * After configuring, call done() to return to the pipeline.
     */
    public function lid(): PipelineStage
    {
        return new PipelineStage($this, new Lid());
    }

    /**
     * Create and return a Trans stage builder.
     *
     * After configuring, call done() to return to the pipeline.
     */
    public function trans(): PipelineStage
    {
        return new PipelineStage($this, new Trans());
    }

    /**
     * Execute the pipeline.
     */
    public function run(?CliDriver $driver = null): Response
    {
        $driver = $driver ?? $this->driver;

        if ($driver === null) {
            throw new \LogicException('No driver provided. Use withDriver() or pass a driver to run().');
        }

        if (empty($this->stages)) {
            throw new DriverException('Pipeline requires at least one stage');
        }

        // Build requests from stages
        $requests = [];
        foreach ($this->stages as $i => $stage) {
            $request = $stage->toRequest();

            // First stage gets the input file
            if ($i === 0 && $this->inputFile !== null) {
                $request = new Request(
                    method: $request->method,
                    parameters: $request->parameters,
                    audioFile: $this->inputFile,
                    audioContent: $request->audioContent,
                    textFile: $request->textFile,
                );
            }

            $requests[] = $request;
        }

        return $driver->executePipeline($requests);
    }

    /**
     * Get the current stages.
     *
     * @return AbstractMethod[]
     */
    public function getStages(): array
    {
        return $this->stages;
    }
}
