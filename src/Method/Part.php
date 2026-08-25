<?php

declare(strict_types=1);

namespace Vocapia\Voxsigma\Method;

use Vocapia\Voxsigma\Driver\Request;
use Vocapia\Voxsigma\Parameter\Parameter;

/**
 * VoxSigma speaker partitioning method (vrxs_part).
 *
 * Performs speaker diarization (who spoke when) on audio files.
 */
final class Part extends AbstractMethod
{
    public function getMethodName(): string
    {
        return 'vrxs_part';
    }

    /**
     * @inheritDoc
     */
    protected static function defineParameters(): array
    {
        return array_merge(static::commonParameters(), [
            new Parameter('model', '-l', 'model'),
            new Parameter('speakerCount', '-k', 'kopt'),
            new Parameter('channel', '-n', 'nopt'),
            new Parameter('dualChannel', '-q', 'qopt', Parameter::TYPE_FLAG, 'd'),
            new Parameter('outputSpeakerModel', '-q', 'qopt', Parameter::TYPE_FLAG, 'i'),
            new Parameter('threads', '-h', '', Parameter::TYPE_VALUE), // CLI only
            new Parameter('speakerListFile', '-sl', 'slfile', Parameter::TYPE_FILE),
            new Parameter('speakerModelSet', '-j', '', Parameter::TYPE_FILE), // CLI only
        ]);
    }

    /**
     * Set the model for partitioning.
     *
     * @param string $model Model name
     */
    public function model(string $model): self
    {
        $this->parameters['model'] = $model;
        return $this;
    }

    /**
     * Set speaker count with min and/or max.
     * In dualChannel mode, per-channel values can be specified independently.
     *
     * @param int|null $min         Minimum speakers (channel 1, or all channels)
     * @param int|null $max         Maximum speakers (channel 1, or all channels)
     * @param int|null $channel2Min Minimum speakers for channel 2 (dualChannel only)
     * @param int|null $channel2Max Maximum speakers for channel 2 (dualChannel only)
     */
    public function speakerCount(
        ?int $min = null,
        ?int $max = null,
        ?int $channel2Min = null,
        ?int $channel2Max = null,
    ): self {
        $spec1 = self::buildSpeakerSpec($min, $max);

        if ($channel2Min !== null || $channel2Max !== null) {
            $spec2 = self::buildSpeakerSpec($channel2Min, $channel2Max);
            $this->parameters['speakerCount'] = $spec1 . ',' . $spec2;
        } else {
            $this->parameters['speakerCount'] = $spec1;
        }

        return $this;
    }

    private static function buildSpeakerSpec(?int $min, ?int $max): string
    {
        if ($min !== null && $max !== null) {
            return $min . ':' . $max;
        }
        if ($min !== null) {
            return $min . ':';
        }
        return (string) $max;
    }

    /**
     * Set maximum number of speakers.
     *
     * @param int $k Maximum speakers
     */
    public function maxSpeakers(int $k): self
    {
        return $this->speakerCount(max: $k);
    }

    /**
     * Set minimum number of speakers.
     *
     * @param int $k Minimum speakers
     */
    public function minSpeakers(int $k): self
    {
        return $this->speakerCount(min: $k);
    }

    /**
     * Set speaker range (min:max).
     *
     * @param int $min Minimum number of speakers
     * @param int $max Maximum number of speakers
     */
    public function speakerRange(int $min, int $max): self
    {
        return $this->speakerCount($min, $max);
    }

    /**
     * Set audio channel to process.
     *
     * @param int $n Channel number
     */
    public function channel(int $n): self
    {
        $this->parameters['channel'] = $n;
        return $this;
    }

    /**
     * Enable dual channel processing.
     */
    public function dualChannel(bool $d = true): self
    {
        $this->parameters['dualChannel'] = $d;
        return $this;
    }

    /**
     * Enable speaker model (.spm) binary output.
     *
     * When enabled, performs partitioning and returns a binary SPM speaker
     * model file. The response body contains the raw binary SPM data
     * (starting with the ASCII magic bytes "HAR"). On error, the response
     * body contains an XML error document instead.
     *
     * Use Response::isSpm() to distinguish a valid SPM binary from an XML
     * error, and Response::getBody() to access the raw bytes.
     */
    public function outputSpeakerModel(bool $i = true): self
    {
        $this->parameters['outputSpeakerModel'] = $i;
        return $this;
    }

    /**
     * Set number of threads (CLI only).
     *
     * @param int $h Number of threads
     */
    public function threads(int $h): self
    {
        $this->parameters['threads'] = $h;
        return $this;
    }

    /**
     * Set speaker list file for speaker identification.
     *
     * @param string $path Path to speaker list file
     */
    public function speakerListFile(string $path): self
    {
        $this->parameters['speakerListFile'] = $path;
        return $this;
    }

    /**
     * Set speaker model set for identification.
     *
     * @param string $path Path to speaker model set
     */
    public function speakerModelSet(string $path): self
    {
        $this->parameters['speakerModelSet'] = $path;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function toRequest(): Request
    {
        if (
            isset($this->parameters['speakerCount'])
            && str_contains((string) $this->parameters['speakerCount'], ',')
            && empty($this->parameters['dualChannel'])
        ) {
            throw new \InvalidArgumentException(
                'Per-channel speakerCount (comma-separated) requires dualChannel mode.'
            );
        }

        return parent::toRequest();
    }
}
