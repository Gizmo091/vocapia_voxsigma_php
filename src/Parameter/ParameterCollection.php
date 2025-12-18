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

    /** @var array<string, array<Parameter>> Indexed by CLI option (can have multiple) */
    private array $byCli = [];

    /** @var array<string, array<Parameter>> Indexed by REST param (can have multiple) */
    private array $byRest = [];

    /**
     * @param array<Parameter> $parameters
     */
    public function __construct(array $parameters)
    {
        foreach ($parameters as $param) {
            $this->byName[$param->name] = $param;

            if ($param->cliOption !== '') {
                $this->byCli[$param->cliOption][] = $param;
            }

            if ($param->restParam !== '') {
                $this->byRest[$param->restParam][] = $param;
            }
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
     * Find parameter by CLI option and optional value.
     *
     * @param string $option CLI option (e.g., '-q')
     * @param string|null $value Optional value to match against flagValue (e.g., 'd' for dualChannel)
     */
    public function findByCli(string $option, ?string $value = null): ?Parameter
    {
        // Normalize option with dash prefix
        $normalizedOption = str_starts_with($option, '-') ? $option : '-' . $option;

        $candidates = $this->byCli[$normalizedOption] ?? $this->byCli[$option] ?? [];

        if (empty($candidates)) {
            return null;
        }

        // If no value specified, return first (or only) parameter
        if ($value === null) {
            return $candidates[0];
        }

        // Find parameter matching the value
        foreach ($candidates as $param) {
            // Flag parameter with matching flagValue
            if ($param->type === Parameter::TYPE_FLAG && $param->flagValue === $value) {
                return $param;
            }
            // Regular value parameter (no flagValue) accepts any value
            if ($param->type === Parameter::TYPE_VALUE && $param->flagValue === null) {
                return $param;
            }
        }

        return null;
    }

    /**
     * Find parameter by REST param and optional value.
     *
     * @param string $param REST param name (e.g., 'qopt')
     * @param string|null $value Optional value to match against flagValue
     */
    public function findByRest(string $param, ?string $value = null): ?Parameter
    {
        $candidates = $this->byRest[$param] ?? [];

        if (empty($candidates)) {
            return null;
        }

        // If no value specified, return first (or only) parameter
        if ($value === null) {
            return $candidates[0];
        }

        // Find parameter matching the value
        foreach ($candidates as $candidate) {
            // Flag parameter with matching flagValue
            if ($candidate->type === Parameter::TYPE_FLAG && $candidate->flagValue === $value) {
                return $candidate;
            }
            // Regular value parameter (no flagValue) accepts any value
            if ($candidate->type === Parameter::TYPE_VALUE && $candidate->flagValue === null) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * Get all parameters sharing a CLI option.
     *
     * @return array<Parameter>
     */
    public function findAllByCli(string $option): array
    {
        $normalizedOption = str_starts_with($option, '-') ? $option : '-' . $option;
        return $this->byCli[$normalizedOption] ?? $this->byCli[$option] ?? [];
    }

    /**
     * Get all parameters sharing a REST param.
     *
     * @return array<Parameter>
     */
    public function findAllByRest(string $param): array
    {
        return $this->byRest[$param] ?? [];
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