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
            new Parameter('speakerCount', '-k', 'kopt'),
            new Parameter('channel', '-n', 'nopt'),
            new Parameter('dualChannel', '-q', 'qopt', Parameter::TYPE_FLAG, 'd'),
            new Parameter('outputSpeakerModel', '-q', 'qopt', Parameter::TYPE_FLAG, 'i'),
            new Parameter('threads', '-h', '', Parameter::TYPE_VALUE), // CLI only
            new Parameter('speakerListFile', '-sl', 'slfile', Parameter::TYPE_FILE),
            new Parameter('speakerModelSet', '-j', '', Parameter::TYPE_FILE), // CLI only
            new Parameter('model', '', 'model'), // REST only: vrxs_part has no -l option
            // CLI only (not accepted by the REST API)
            new Parameter('speakerThreshold', '-a', ''),
            new Parameter('knownSpeakerPrior', '-b', ''),
            new Parameter('timeout', '-e', ''),
            new Parameter('segmentFile', '-i', '', Parameter::TYPE_FILE),
            new Parameter('withDtmf', '-x', ''),
            // -q letters are chained (-qdij), the SAD model is a separate -q@<model>
            new Parameter('includeNonSpeech', '-q', 'qopt', Parameter::TYPE_FLAG, 'j'),
            new Parameter('sadModel', '-q', 'qopt'),
            new Parameter('speakerEmbeddingsFile', '-sc', '', Parameter::TYPE_FILE),
            new Parameter('workingDir', '-w', '', Parameter::TYPE_FILE),
            new Parameter('sequenceId', '-y', ''),
            new Parameter('segmentPenalty', '--alpha=', ''),
            new Parameter('clusterPenalty', '--beta=', ''),
            new Parameter('clusteringThreshold', '--cthr=', ''),
            new Parameter('collar', '--collar=', ''),
            new Parameter('maxSegmentDuration', '--msdur=', ''),
            new Parameter('sadDownsampling', '--sads=', ''),
            new Parameter('sadSegmentPenalty', '--spty=', ''),
            new Parameter('speechFramePenalty', '--sfpty=', ''),
        ]);
    }

    /**
     * Set the model (REST only).
     *
     * vrxs_part has no language option: the value is sent as the REST `model`
     * argument but ignored in CLI mode (no -l is passed to the binary).
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
     * Set the speaker confidence threshold (CLI only, default 0.5).
     */
    public function speakerThreshold(float $a): self
    {
        $this->parameters['speakerThreshold'] = $a;
        return $this;
    }

    /**
     * Set the known speaker prior (CLI only, default 0.01).
     */
    public function knownSpeakerPrior(float $b): self
    {
        $this->parameters['knownSpeakerPrior'] = $b;
        return $this;
    }

    /**
     * Set the maximal elapsed time in seconds (CLI only).
     */
    public function timeout(int $e): self
    {
        $this->parameters['timeout'] = $e;
        return $this;
    }

    /**
     * Use an input segment file (CLI only).
     */
    public function segmentFile(string $path): self
    {
        $this->parameters['segmentFile'] = $path;
        return $this;
    }

    /**
     * Enable the DTMF decoder and audio event detection (CLI only).
     *
     * @param bool|int $x true for -x, an integer for -x<num>, false to disable
     */
    public function withDtmf(bool|int $x = true): self
    {
        if ($x === false) {
            unset($this->parameters['withDtmf']);
        } else {
            $this->parameters['withDtmf'] = $x === true ? '' : $x;
        }
        return $this;
    }

    /**
     * Include non-speech segments in the output (-qj / qopt=j).
     */
    public function includeNonSpeech(bool $j = true): self
    {
        $this->parameters['includeNonSpeech'] = $j;
        return $this;
    }

    /**
     * Set the speech activity detection model (-q@<model> / qopt=@<model>).
     *
     * Sent as a separate option, it cannot be chained: -qdj -q@<model>.
     */
    public function sadModel(string $model): self
    {
        $this->parameters['sadModel'] = '@' . self::assertSafeToken('sadModel', $model);
        return $this;
    }

    /**
     * Write the speaker embeddings to a file (CLI only, -sc).
     */
    public function speakerEmbeddingsFile(string $path): self
    {
        $this->parameters['speakerEmbeddingsFile'] = $path;
        return $this;
    }

    /**
     * Set the working directory (CLI only).
     */
    public function workingDir(string $path): self
    {
        $this->parameters['workingDir'] = $path;
        return $this;
    }

    /**
     * Set the sequence ID (CLI only).
     */
    public function sequenceId(string $id): self
    {
        $this->parameters['sequenceId'] = self::assertSafeToken('sequenceId', $id);
        return $this;
    }

    /**
     * Set the speaker segment penalty (CLI only, --alpha, default 90.0).
     */
    public function segmentPenalty(float $alpha): self
    {
        $this->parameters['segmentPenalty'] = $alpha;
        return $this;
    }

    /**
     * Set the cluster penalty (CLI only, --beta, default 70.0).
     */
    public function clusterPenalty(float $beta): self
    {
        $this->parameters['clusterPenalty'] = $beta;
        return $this;
    }

    /**
     * Set the clustering threshold (CLI only, --cthr, default 0.70).
     */
    public function clusteringThreshold(float $cthr): self
    {
        $this->parameters['clusteringThreshold'] = $cthr;
        return $this;
    }

    /**
     * Set the collar added on each segment side, in seconds (CLI only, --collar, default 0.21).
     */
    public function collar(float $collar): self
    {
        $this->parameters['collar'] = $collar;
        return $this;
    }

    /**
     * Set the maximum segment duration, in seconds (CLI only, --msdur, default 60.0).
     */
    public function maxSegmentDuration(float $msdur): self
    {
        $this->parameters['maxSegmentDuration'] = $msdur;
        return $this;
    }

    /**
     * Set the SAD downsampling factor (CLI only, --sads, default 1).
     */
    public function sadDownsampling(int $sads): self
    {
        $this->parameters['sadDownsampling'] = $sads;
        return $this;
    }

    /**
     * Set the SAD segment penalty (CLI only, --spty, default 30.0).
     */
    public function sadSegmentPenalty(float $spty): self
    {
        $this->parameters['sadSegmentPenalty'] = $spty;
        return $this;
    }

    /**
     * Set the speech frame penalty (CLI only, --sfpty, default 0.00).
     */
    public function speechFramePenalty(float $sfpty): self
    {
        $this->parameters['speechFramePenalty'] = $sfpty;
        return $this;
    }

    /**
     * Values attached to an option (-y<id>, -q@<model>) are not shell-escaped.
     */
    private static function assertSafeToken(string $name, string $value): string
    {
        if (!preg_match('/^[A-Za-z0-9._:\/-]+$/', $value)) {
            throw new \InvalidArgumentException(sprintf('Invalid %s: %s', $name, $value));
        }
        return $value;
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
