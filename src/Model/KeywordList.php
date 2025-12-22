<?php

declare(strict_types=1);

namespace Vocapia\Voxsigma\Model;

/**
 * Builder for keyword list (.kwl format).
 *
 * @example
 * ```php
 * $keywords = KeywordList::create()
 *     ->add('KW001', 0.5, 'Mon Mot')
 *     ->add('KW002', 0.5, 'MyWorld')
 *     ->add('KW003', 0.4, 'Akounamatata');
 * ```
 */
final class KeywordList
{
    /** @var Keyword[] */
    private array $keywords = [];

    /** @var array<string, true> Track used IDs */
    private array $usedIds = [];

    private int $autoIdCounter = 1;

    public static function create(): self
    {
        return new self();
    }

    /**
     * Add a keyword with explicit ID.
     *
     * @param string $id Unique identifier (e.g., KW001)
     * @param float $threshold Minimum hit threshold (0.0-1.0)
     * @param string $text Keyword text to search for
     * @throws \InvalidArgumentException If ID is already used
     */
    public function add(string $id, float $threshold, string $text): self
    {
        if (isset($this->usedIds[$id])) {
            throw new \InvalidArgumentException("Keyword ID already used: $id");
        }

        $this->usedIds[$id] = true;
        $this->keywords[] = new Keyword($id, $threshold, $text);
        return $this;
    }

    /**
     * Add a keyword with auto-generated ID.
     *
     * @param string $text Keyword text to search for
     * @param float $threshold Minimum hit threshold (0.0-1.0, default: 0.5)
     */
    public function addKeyword(string $text, float $threshold = 0.5): self
    {
        $id = $this->generateUniqueId();
        $this->usedIds[$id] = true;
        $this->keywords[] = new Keyword($id, $threshold, $text);
        return $this;
    }

    /**
     * Add a Keyword object.
     *
     * @throws \InvalidArgumentException If ID is already used
     */
    public function addEntry(Keyword $keyword): self
    {
        if (isset($this->usedIds[$keyword->id])) {
            throw new \InvalidArgumentException("Keyword ID already used: {$keyword->id}");
        }

        $this->usedIds[$keyword->id] = true;
        $this->keywords[] = $keyword;
        return $this;
    }

    /**
     * Generate a unique ID that doesn't conflict with existing ones.
     */
    private function generateUniqueId(): string
    {
        do {
            $id = sprintf('KW%03d', $this->autoIdCounter++);
        } while (isset($this->usedIds[$id]));

        return $id;
    }

    /**
     * Get all keywords.
     *
     * @return Keyword[]
     */
    public function all(): array
    {
        return $this->keywords;
    }

    /**
     * Get keyword count.
     */
    public function count(): int
    {
        return count($this->keywords);
    }

    /**
     * Check if list is empty.
     */
    public function isEmpty(): bool
    {
        return empty($this->keywords);
    }

    /**
     * Convert to KWL file content.
     */
    public function toFileContent(): string
    {
        $lines = [];
        foreach ($this->keywords as $keyword) {
            $lines[] = $keyword->toLine();
        }
        return implode("\n", $lines) . "\n";
    }

    /**
     * Write to a file.
     *
     * @return string Path to the written file
     */
    public function writeToFile(string $path): string
    {
        file_put_contents($path, $this->toFileContent());
        return $path;
    }

    /**
     * Write to a temporary file.
     *
     * @return string Path to the temporary file
     */
    public function writeToTempFile(string $tmpDir = '/tmp'): string
    {
        $path = tempnam($tmpDir, 'voxsigma_kwl_') . '.kwl';
        return $this->writeToFile($path);
    }
}