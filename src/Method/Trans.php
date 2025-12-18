<?php

declare(strict_types=1);

namespace Vocapia\Voxsigma\Method;

/**
 * VoxSigma transcription method (vrxs_trans / vrbs_trans / vrcts_trans).
 *
 * Performs speech-to-text transcription on audio files.
 */
final class Trans extends AbstractMethod
{
    public function getMethodName(): string
    {
        return 'vrxs_trans';
    }

    /**
     * Set the language/model for transcription.
     *
     * @param string $model Language code (e.g., 'fre', 'eng-usa', 'eng-gbr')
     */
    public function model(string $model): self
    {
        $this->parameters['model'] = $model;
        return $this;
    }

    /**
     * Force the specified language (no automatic language detection).
     * Requires model() to be set.
     */
    public function forceLanguage(bool $force = true): self
    {
        $this->parameters['forceLanguage'] = $force;
        return $this;
    }

    /**
     * Set maximum number of speakers for diarization.
     *
     * @param int $k Maximum speakers (default: 10)
     */
    public function maxSpeakers(int $k): self
    {
        $this->parameters['maxSpeakers'] = $k;
        return $this;
    }

    /**
     * Set the duration for language identification (seconds).
     *
     * @param float $sec Duration in seconds (default: 30)
     */
    public function lidDuration(float $sec): self
    {
        $this->parameters['lidDuration'] = $sec;
        return $this;
    }

    /**
     * Set the language identification confidence threshold.
     *
     * @param float $t Threshold (0.0-1.0, default: 0.5)
     */
    public function lidThreshold(float $t): self
    {
        $this->parameters['lidThreshold'] = $t;
        return $this;
    }

    /**
     * Set the language identification model version.
     *
     * @param string $v Version (e.g., '7.1', '8.0')
     */
    public function lidVersion(string $v): self
    {
        $this->parameters['lidVersion'] = $v;
        return $this;
    }

    /**
     * Enable dual channel processing.
     *
     * Each audio channel is processed independently and assigned
     * to a different speaker.
     */
    public function dualChannel(bool $d = true): self
    {
        $this->parameters['dualChannel'] = $d;
        return $this;
    }

    /**
     * Disable speaker partitioning.
     *
     * Treat the entire audio as a single speaker.
     */
    public function noPartitioning(bool $p = true): self
    {
        $this->parameters['noPartitioning'] = $p;
        return $this;
    }

    /**
     * Set quality level.
     *
     * @param int $q Quality (0=default, 1=fast, 2=best)
     */
    public function quality(int $q): self
    {
        $this->parameters['quality'] = $q;
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
     * Set processing timeout in seconds (CLI only).
     *
     * @param int $e Timeout in seconds
     */
    public function timeout(int $e): self
    {
        $this->parameters['timeout'] = $e;
        return $this;
    }

    /**
     * Enable DTMF detection (CLI only).
     */
    public function withDtmf(bool $x = true): self
    {
        $this->parameters['withDtmf'] = $x;
        return $this;
    }

    /**
     * Set vocabulary file for custom words.
     *
     * @param string $path Path to vocabulary file
     */
    public function vocabularyFile(string $path): self
    {
        $this->parameters['vocabularyFile'] = $path;
        return $this;
    }

    /**
     * Set language list file for multi-language detection.
     *
     * @param string $path Path to language list file
     */
    public function languageListFile(string $path): self
    {
        $this->parameters['languageListFile'] = $path;
        return $this;
    }

    /**
     * Set user model (adapted language model).
     *
     * @param string $model User model name
     */
    public function userModel(string $model): self
    {
        $this->parameters['userModel'] = $model;
        return $this;
    }
}
