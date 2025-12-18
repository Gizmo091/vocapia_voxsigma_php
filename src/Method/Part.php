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
            new Parameter('maxSpeakers', '-k', 'kopt'),
            new Parameter('speakerRange', '-k', 'kopt'), // Same as maxSpeakers, different format
            new Parameter('channel', '-n', 'nopt'),
            new Parameter('dualChannel', '-qd', 'qopt', Parameter::TYPE_FLAG, 'd'),
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
     * Set maximum number of speakers.
     *
     * @param int $k Maximum speakers (default: 10)
     */
    public function maxSpeakers(int $k): self
    {
        $this->parameters['maxSpeakers'] = $k;
        return $this;
    }

    /**
     * Set speaker range (min:max).
     *
     * @param int $min Minimum number of speakers
     * @param int $max Maximum number of speakers
     */
    public function speakerRange(int $min, int $max): self
    {
        $this->parameters['speakerRange'] = $min . ':' . $max;
        return $this;
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
