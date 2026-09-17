<?php

declare(strict_types=1);

namespace Vocapia\Voxsigma\Model;

/**
 * Represents a single query of a speaker search file (vr_xvfind).
 *
 * File line format (space separated):
 * <id> <channel> <speakerId> <spmFile>
 *
 * The SPM path closes the line, so it may hold spaces.
 */
final class SpeakerQuery
{
    public function __construct(
        /** Query identifier, echoed back on every hit (alphanumeric) */
        public readonly string $id,
        /** Channel number the speaker was identified on */
        public readonly int $channel,
        /** Speaker id, as found in the XML transcription */
        public readonly string $speakerId,
        /** Path to the SPM matching the XML the speaker was identified in */
        public readonly string $spmFile,
    ) {
        self::assertNoWhitespace($id, 'Query id');
        self::assertNoWhitespace($speakerId, 'Speaker id');
        self::assertSingleLine($spmFile, 'SPM path');
    }

    /**
     * Convert to a search file line.
     */
    public function toLine(): string
    {
        return sprintf('%s %d %s %s', $this->id, $this->channel, $this->speakerId, $this->spmFile);
    }

    /**
     * The search file is space separated, so the leading fields may not hold
     * whitespace. The SPM path closes the line and is exempt.
     *
     * @throws \InvalidArgumentException
     */
    private static function assertNoWhitespace(string $value, string $label): void
    {
        self::assertNotEmpty($value, $label);

        if (preg_match('/\s/', $value) === 1) {
            throw new \InvalidArgumentException("$label cannot contain whitespace: $value");
        }
    }

    /**
     * The SPM path may hold spaces, being the last field of the line, but a
     * line break would split the query in two.
     *
     * @throws \InvalidArgumentException
     */
    private static function assertSingleLine(string $value, string $label): void
    {
        self::assertNotEmpty($value, $label);

        if (preg_match('/\R/', $value) === 1) {
            throw new \InvalidArgumentException("$label cannot contain a line break: $value");
        }
    }

    /**
     * @throws \InvalidArgumentException
     */
    private static function assertNotEmpty(string $value, string $label): void
    {
        if ($value === '') {
            throw new \InvalidArgumentException("$label cannot be empty.");
        }
    }
}
