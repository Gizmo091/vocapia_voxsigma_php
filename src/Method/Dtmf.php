<?php

declare(strict_types=1);

namespace Vocapia\Voxsigma\Method;

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
