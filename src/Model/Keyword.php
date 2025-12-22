<?php

declare(strict_types=1);

namespace Vocapia\Voxsigma\Model;

/**
 * Represents a keyword entry for keyword spotting.
 */
final class Keyword
{
    public function __construct(
        /** Unique identifier (e.g., KW001) */
        public readonly string $id,
        /** Minimum hit threshold (0.0-1.0) */
        public readonly float $threshold,
        /** Keyword text to search for */
        public readonly string $text,
    ) {
    }

    /**
     * Convert to KWL file format line.
     */
    public function toLine(): string
    {
        return sprintf('%s %.2f %s', $this->id, $this->threshold, $this->text);
    }
}