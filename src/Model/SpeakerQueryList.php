<?php

declare(strict_types=1);

namespace Vocapia\Voxsigma\Model;

/**
 * Builder for a speaker search file (vr_xvfind), one query per line.
 *
 * Query ids are echoed back on every hit. They do not have to be unique,
 * but unique ids make the output easier to map back to its query.
 *
 * @example
 * ```php
 * $queries = SpeakerQueryList::create()
 *     ->add('Q001', 1, 'spk1', '/path/to/interview.spm')
 *     ->addQuery(2, 'spk3', '/path/to/meeting.spm');  // Auto-generated id
 * ```
 */
final class SpeakerQueryList
{
    /** @var SpeakerQuery[] */
    private array $queries = [];

    private int $autoIdCounter = 1;

    public static function create(): self
    {
        return new self();
    }

    /**
     * Add a query with an explicit id.
     *
     * @param string $id Query identifier, echoed back on every hit
     * @param int $channel Channel number the speaker was identified on
     * @param string $speakerId Speaker id, as found in the XML transcription
     * @param string $spmFile Path to the SPM matching that XML
     */
    public function add(string $id, int $channel, string $speakerId, string $spmFile): self
    {
        $this->queries[] = new SpeakerQuery($id, $channel, $speakerId, $spmFile);
        return $this;
    }

    /**
     * Add a query with an auto-generated id.
     *
     * @param int $channel Channel number the speaker was identified on
     * @param string $speakerId Speaker id, as found in the XML transcription
     * @param string $spmFile Path to the SPM matching that XML
     */
    public function addQuery(int $channel, string $speakerId, string $spmFile): self
    {
        return $this->add($this->generateId(), $channel, $speakerId, $spmFile);
    }

    /**
     * Add a SpeakerQuery object.
     */
    public function addEntry(SpeakerQuery $query): self
    {
        $this->queries[] = $query;
        return $this;
    }

    /**
     * Get all queries.
     *
     * @return SpeakerQuery[]
     */
    public function all(): array
    {
        return $this->queries;
    }

    /**
     * Get query count.
     */
    public function count(): int
    {
        return count($this->queries);
    }

    /**
     * Check if list is empty.
     */
    public function isEmpty(): bool
    {
        return empty($this->queries);
    }

    /**
     * Check whether an id is shared by several queries.
     *
     * Allowed by vr_xvfind, but hits of those queries cannot be told apart.
     */
    public function hasDuplicateIds(): bool
    {
        $ids = array_map(static fn (SpeakerQuery $q): string => $q->id, $this->queries);

        return count(array_unique($ids)) !== count($ids);
    }

    /**
     * Convert to search file content (one query per line).
     */
    public function toFileContent(): string
    {
        $lines = array_map(static fn (SpeakerQuery $q): string => $q->toLine(), $this->queries);

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
     * Write to a temporary file with a deterministic name based on content hash.
     *
     * If the file already exists, it is not rewritten.
     *
     * @return string Path to the temporary file
     */
    public function writeToTempFile(string $tmpDir = '/tmp'): string
    {
        $content = $this->toFileContent();
        $hash = md5($content);
        $path = rtrim($tmpDir, '/') . '/xvsearch_' . $hash . '.lst';

        if (!file_exists($path)) {
            file_put_contents($path, $content);
        }

        return $path;
    }

    /**
     * Generate an id that is not already used by this list.
     */
    private function generateId(): string
    {
        $used = [];
        foreach ($this->queries as $query) {
            $used[$query->id] = true;
        }

        do {
            $id = sprintf('Q%03d', $this->autoIdCounter++);
        } while (isset($used[$id]));

        return $id;
    }
}
