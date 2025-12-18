<?php

declare(strict_types=1);

namespace Vocapia\Voxsigma\Pipeline;

use Vocapia\Voxsigma\Method\AbstractMethod;

/**
 * Helper for fluent pipeline stage configuration.
 *
 * Wraps a method and proxies configuration calls,
 * then returns to the pipeline when done.
 */
final class PipelineStage
{
    public function __construct(
        private readonly Pipeline $pipeline,
        private readonly AbstractMethod $method,
    ) {
    }

    /**
     * Finalize this stage and return to the pipeline.
     */
    public function done(): Pipeline
    {
        $this->pipeline->add($this->method);
        return $this->pipeline;
    }

    /**
     * Proxy method calls to the underlying method.
     *
     * @param string $name
     * @param array<mixed> $arguments
     * @return $this
     */
    public function __call(string $name, array $arguments): self
    {
        if (!method_exists($this->method, $name)) {
            throw new \BadMethodCallException(
                sprintf('Method %s does not exist on %s', $name, $this->method::class)
            );
        }

        $this->method->$name(...$arguments);
        return $this;
    }
}
