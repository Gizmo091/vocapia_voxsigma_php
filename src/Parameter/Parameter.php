<?php

declare(strict_types=1);

namespace Vocapia\Voxsigma\Parameter;

/**
 * Represents a VoxSigma parameter with its SDK, CLI, and REST names.
 */
final class Parameter
{
    public const TYPE_VALUE = 'value';  // Parameter with a value: -l fre, model=fre
    public const TYPE_FLAG = 'flag';    // Boolean flag: -qd, qopt=d
    public const TYPE_FILE = 'file';    // File parameter: -a vocab.txt, vocfile=@file

    public function __construct(
        /** SDK parameter name (e.g., 'lidDuration') */
        public readonly string $name,
        /** CLI option (e.g., '-dl') */
        public readonly string $cliOption,
        /** REST parameter name (e.g., 'dlopt') */
        public readonly string $restParam,
        /** Parameter type: value, flag, or file */
        public readonly string $type = self::TYPE_VALUE,
        /** For flags that combine into one param (e.g., qopt=dp) */
        public readonly ?string $flagValue = null,
    ) {
    }

    /**
     * Convert parameter value to CLI arguments.
     *
     * @return array<string>
     */
    public function toCliArgs(mixed $value): array
    {
        if ($this->type === self::TYPE_FLAG) {
            // Boolean flag: return option only if true
            return $value ? [$this->cliOption] : [];
        }

        if ($this->type === self::TYPE_FILE) {
            // File: -a /path/to/file
            return [$this->cliOption, (string) $value];
        }

        // Value: -l fre or -dl30 (attached)
        // Check if option expects attached value (like -l) or separate (like -dl)
        if (str_ends_with($this->cliOption, ':')) {
            // Separate value: -dl 30
            return [rtrim($this->cliOption, ':'), (string) $value];
        }

        // Attached value: -lfre
        return [$this->cliOption . $value];
    }

    /**
     * Convert parameter value to REST form field.
     *
     * @return array<string, string>
     */
    public function toRestField(mixed $value): array
    {
        if ($this->type === self::TYPE_FLAG) {
            // Boolean flag with specific value (e.g., qopt=d)
            return $value ? [$this->restParam => $this->flagValue ?? '1'] : [];
        }

        if ($this->type === self::TYPE_FILE) {
            // Files are handled separately by the driver
            return [];
        }

        // Regular value
        return [$this->restParam => (string) $value];
    }
}
