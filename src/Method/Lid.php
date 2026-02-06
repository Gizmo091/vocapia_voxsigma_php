<?php

declare(strict_types=1);

namespace Vocapia\Voxsigma\Method;

use Vocapia\Voxsigma\Parameter\Parameter;

/**
 * VoxSigma language identification method (vrxs_lid).
 *
 * Identifies the spoken language(s) in audio files.
 */
final class Lid extends AbstractMethod
{
    public function getMethodName(): string
    {
        return 'vrxs_lid';
    }

    /**
     * @inheritDoc
     */
    protected static function defineParameters(): array
    {
        return array_merge(static::commonParameters(), [
            new Parameter('model', '-l', 'model'),
            new Parameter('duration', '-dl', 'dlopt'),
            new Parameter('threshold', '-ql', 'qlopt'),
            new Parameter('version', '-r', 'ropt'),
            new Parameter('multilingual', '-q', 'qopt'),
            new Parameter('threads', '-h', '', Parameter::TYPE_VALUE), // CLI only
            new Parameter('languageListFile', '-m', 'llfile', Parameter::TYPE_FILE),
        ]);
    }

    /**
     * Set the model for language identification.
     *
     * @param string $model Model name
     */
    public function model(string $model): self
    {
        $this->parameters['model'] = $model;
        return $this;
    }

    /**
     * Set the duration for language identification (seconds).
     *
     * @param float $sec Duration in seconds (default: 30)
     */
    public function duration(float $sec): self
    {
        $this->parameters['lidDuration'] = $sec;
        return $this;
    }

    /**
     * Set the confidence threshold.
     *
     * @param float $t Threshold (0.0-1.0, default: 0.5)
     */
    public function threshold(float $t): self
    {
        $this->parameters['lidThreshold'] = $t;
        return $this;
    }

    /**
     * Set the language identification model version.
     *
     * @param string $v Version (e.g., '7.1', '8.0')
     */
    public function version(string $v): self
    {
        $this->parameters['lidVersion'] = $v;
        return $this;
    }

    /**
     * Enable multilingual processing.
     * When used with dualChannel(), the max languages applies per channel, not globally.
     *
     * @param bool $perSegment If true, language can change per segment; otherwise per speaker
     * @param int|null $maxLanguages Maximum number of languages (recommended)
     */
    public function multilingual(bool $perSegment = false, ?int $maxLanguages = null): self
    {
        $value = $perSegment ? 'xs' : 'x';
        if ($maxLanguages !== null) {
            $value .= $maxLanguages;
        }
        $this->parameters['multilingual'] = $value;
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
     * Set language list file for restricted language set.
     *
     * @param string $path Path to language list file
     */
    public function languageListFile(string $path): self
    {
        $this->parameters['languageListFile'] = $path;
        return $this;
    }
}
