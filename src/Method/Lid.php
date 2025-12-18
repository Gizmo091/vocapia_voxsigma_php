<?php

declare(strict_types=1);

namespace Vocapia\Voxsigma\Method;

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
