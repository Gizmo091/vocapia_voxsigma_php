<?php

declare(strict_types=1);

namespace Vocapia\Voxsigma\Method;

use Vocapia\Voxsigma\Parameter\Parameter;

/**
 * VoxSigma DTMF detection method (vrxs_dtmf).
 *
 * Detects DTMF tones in audio files.
 * CLI only.
 */
final class Dtmf extends AbstractMethod
{
    public function getMethodName(): string
    {
        return 'vrxs_dtmf';
    }

    /**
     * @inheritDoc
     */
    protected static function defineParameters(): array
    {
        return array_merge(static::commonParameters(), [
            new Parameter('channel', '-n', 'nopt'),
        ]);
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
}
