<?php

declare(strict_types=1);

namespace Vocapia\Voxsigma\Model;

/**
 * Builder for file list (list of paths to search in).
 *
 * @example
 * ```php
 * $files = FileList::create()
 *     ->add('/path/to/file1.kar')
 *     ->add('/path/to/file2.xml')
 *     ->addMany(['/path/to/file3.kar', '/path/to/file4.kar']);
 * ```
 */
final class FileList
{
    /** @var string[] */
    private array $files = [];

    public static function create(): self
    {
        return new self();
    }

    /**
     * Create from an array of paths.
     *
     * @param string[] $paths
     */
    public static function from(array $paths): self
    {
        $list = new self();
        $list->files = array_values($paths);
        return $list;
    }

    /**
     * Add a file path.
     *
     * @param string $path Path to .kar or .xml file
     */
    public function add(string $path): self
    {
        $this->files[] = $path;
        return $this;
    }

    /**
     * Add multiple file paths.
     *
     * @param string[] $paths
     */
    public function addMany(array $paths): self
    {
        foreach ($paths as $path) {
            $this->files[] = $path;
        }
        return $this;
    }

    /**
     * Get all file paths.
     *
     * @return string[]
     */
    public function all(): array
    {
        return $this->files;
    }

    /**
     * Get file count.
     */
    public function count(): int
    {
        return count($this->files);
    }

    /**
     * Check if list is empty.
     */
    public function isEmpty(): bool
    {
        return empty($this->files);
    }

    /**
     * Convert to file content (one path per line).
     */
    public function toFileContent(): string
    {
        return implode("\n", $this->files) . "\n";
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
        $path = tempnam($tmpDir, 'voxsigma_lst_') . '.lst';
        return $this->writeToFile($path);
    }
}