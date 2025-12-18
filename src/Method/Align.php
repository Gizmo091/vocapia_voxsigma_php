<?php

declare(strict_types=1);

namespace Vocapia\Voxsigma\Method;

use Vocapia\Voxsigma\Parameter\Parameter;

/**
 * VoxSigma forced alignment method (vrbs_align).
 *
 * Aligns a known transcription with audio to get word-level timestamps.
 * Primarily used via REST API.
 */
final class Align extends AbstractMethod
{
    public function getMethodName(): string
    {
        return 'vrbs_align';
    }

    /**
     * @inheritDoc
     */
    protected static function defineParameters(): array
    {
        return array_merge(static::commonParameters(), [
            new Parameter('model', '-l', 'model'),
            new Parameter('speakerSegmentation', '-qs', 'qsopt', Parameter::TYPE_FLAG),
            new Parameter('quality', '-q', 'qopt'),
        ]);
    }

    /**
     * Set the model for alignment.
     *
     * @param string $model Language model (e.g., 'fre', 'eng-usa')
     */
    public function model(string $model): self
    {
        $this->parameters['model'] = $model;
        return $this;
    }

    /**
     * Set the text file containing the transcription to align.
     *
     * @param string $path Path to text file
     */
    public function textFile(string $path): self
    {
        $this->textFile = $path;
        return $this;
    }

    /**
     * Enable speaker segmentation in alignment output.
     */
    public function speakerSegmentation(bool $s = true): self
    {
        $this->parameters['speakerSegmentation'] = $s;
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
}
