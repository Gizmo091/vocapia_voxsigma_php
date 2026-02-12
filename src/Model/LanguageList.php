<?php

declare(strict_types=1);

namespace Vocapia\Voxsigma\Model;

/**
 * Builder for language list file (one language code per line).
 *
 * @example
 * ```php
 * $languages = LanguageList::create()
 *     ->add('fre')
 *     ->add('eng-usa')
 *     ->add('eng-gbr');
 * ```
 */
final class LanguageList
{
    /** @var string[] */
    private array $languages = [];

    /** @var array<string, true> Track added languages to prevent duplicates */
    private array $addedLanguages = [];

    public static function create(): self
    {
        return new self();
    }

    /**
     * Create from an array of language codes (duplicates are silently ignored).
     *
     * @param string[] $languages
     */
    public static function from(array $languages): self
    {
        $list = new self();
        $list->addMany($languages);
        return $list;
    }

    /**
     * Add a language code (duplicates are silently ignored).
     *
     * @param string $language Language code (e.g., 'fre', 'eng-usa')
     */
    public function add(string $language): self
    {
        if (isset($this->addedLanguages[$language])) {
            return $this;
        }

        $this->addedLanguages[$language] = true;
        $this->languages[] = $language;
        return $this;
    }

    /**
     * Add multiple language codes (duplicates are silently ignored).
     *
     * @param string[] $languages
     */
    public function addMany(array $languages): self
    {
        foreach ($languages as $language) {
            $this->add($language);
        }
        return $this;
    }

    /**
     * Get all language codes.
     *
     * @return string[]
     */
    public function all(): array
    {
        return $this->languages;
    }

    /**
     * Get language count.
     */
    public function count(): int
    {
        return count($this->languages);
    }

    /**
     * Check if list is empty.
     */
    public function isEmpty(): bool
    {
        return empty($this->languages);
    }

    /**
     * Convert to file content (one language per line).
     */
    public function toFileContent(): string
    {
        return implode("\n", $this->languages) . "\n";
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
        $path = tempnam($tmpDir, 'voxsigma_ll_') . '.lst';
        return $this->writeToFile($path);
    }
}