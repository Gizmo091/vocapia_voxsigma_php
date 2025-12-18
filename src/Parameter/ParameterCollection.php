<?php

declare(strict_types=1);

namespace Vocapia\Voxsigma\Parameter;

/**
 * Collection of parameters with search capabilities.
 */
final class ParameterCollection
{
    /** @var array<string, Parameter> Indexed by name */
    private array $byName = [];

    /** @var array<string, Parameter> Indexed by CLI option */
    private array $byCli = [];

    /** @var array<string, Parameter> Indexed by REST param */
    private array $byRest = [];

    /**
     * @param array<Parameter> $parameters
     */
    public function __construct(array $parameters)
    {
        foreach ($parameters as $param) {
            $this->byName[$param->name] = $param;
            $this->byCli[$param->cliOption] = $param;
            $this->byRest[$param->restParam] = $param;
        }
    }

    /**
     * Find parameter by SDK name.
     */
    public function findByName(string $name): ?Parameter
    {
        return $this->byName[$name] ?? null;
    }

    /**
     * Find parameter by CLI option.
     */
    public function findByCli(string $option): ?Parameter
    {
        // Try exact match first
        if (isset($this->byCli[$option])) {
            return $this->byCli[$option];
        }

        // Try with dash prefix
        if (!str_starts_with($option, '-')) {
            return $this->byCli['-' . $option] ?? null;
        }

        return null;
    }

    /**
     * Find parameter by REST param name.
     */
    public function findByRest(string $param): ?Parameter
    {
        return $this->byRest[$param] ?? null;
    }

    /**
     * Check if a parameter exists by name.
     */
    public function has(string $name): bool
    {
        return isset($this->byName[$name]);
    }

    /**
     * Get all parameters.
     *
     * @return array<Parameter>
     */
    public function all(): array
    {
        return array_values($this->byName);
    }

    /**
     * Get all parameter names.
     *
     * @return array<string>
     */
    public function names(): array
    {
        return array_keys($this->byName);
    }

    /**
     * Get parameter by name (throws if not found).
     *
     * @throws \InvalidArgumentException
     */
    public function get(string $name): Parameter
    {
        if (!isset($this->byName[$name])) {
            throw new \InvalidArgumentException("Unknown parameter: $name");
        }

        return $this->byName[$name];
    }
}
