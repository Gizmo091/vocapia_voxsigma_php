<?php

declare(strict_types=1);

namespace Vocapia\Voxsigma\Method;

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
     * When used with dualChannel(), the count applies per channel, not globally.
     *
     * @param int|null $min Minimum number of speakers
     * @param int|null $max Maximum number of speakers
     */
    public function speakerCount(?int $min = null, ?int $max = null): self
    {
        if ($min !== null && $max !== null) {
            $this->parameters['speakerCount'] = $min . ':' . $max;
        } elseif ($min !== null) {
            $this->parameters['speakerCount'] = $min . ':';
        } elseif ($max !== null) {
            $this->parameters['speakerCount'] = $max;
        }

        return $this;
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
}
