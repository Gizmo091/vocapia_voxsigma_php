<?php

declare(strict_types=1);

namespace Vocapia\Voxsigma\Model;

/**
 * Represents a single hit returned by a speaker search (vr_xvfind).
 *
 * Output line format (space separated):
 * <queryId> <score> <channel> <speakerId> <spmFile>
 *
 * The SPM path closes the line, so it may hold spaces.
 */
final class SpeakerHit
{
    public function __construct(
        /** Identifier of the search query that produced this hit */
        public readonly string $queryId,
        /** Proximity score between the query and the hit */
        public readonly float $score,
        /** Channel number of the matching speaker */
        public readonly int $channel,
        /** Speaker id of the matching speaker */
        public readonly string $speakerId,
        /** SPM file the matching speaker was found in */
        public readonly string $spmFile,
    ) {
    }

    /**
     * Parse the output of vr_xvfind.
     *
     * Lines that do not match the hit format (blank lines, comments, verbose
     * messages) are ignored.
     *
     * @param string $output Raw output, as returned by Response::getBody()
     * @return SpeakerHit[]
     */
    public static function fromOutput(string $output): array
    {
        $hits = [];

        foreach (preg_split('/\R/', $output) ?: [] as $line) {
            $hit = self::fromLine($line);
            if ($hit !== null) {
                $hits[] = $hit;
            }
        }

        return $hits;
    }

    /**
     * Parse a single output line, or return null if it is not a hit.
     */
    public static function fromLine(string $line): ?self
    {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#')) {
            return null;
        }

        // Limit to 5 so a path holding spaces is kept whole.
        $fields = preg_split('/\s+/', $line, 5);

        if ($fields === false || count($fields) < 5) {
            return null;
        }

        [$queryId, $score, $channel, $speakerId, $spmFile] = $fields;

        if (!is_numeric($score) || !is_numeric($channel)) {
            return null;
        }

        return new self(
            queryId: $queryId,
            score: (float) $score,
            channel: (int) $channel,
            speakerId: $speakerId,
            spmFile: $spmFile,
        );
    }
}
