<?php

declare(strict_types=1);

namespace Vocapia\Voxsigma\Method;

use Vocapia\Voxsigma\Driver\Request;
use Vocapia\Voxsigma\Model\FileList;
use Vocapia\Voxsigma\Model\SpeakerQueryList;
use Vocapia\Voxsigma\Parameter\Parameter;

/**
 * VoxSigma speaker search (vr_xvfind).
 *
 * Looks up the speakers of a trial file in a reference database of speaker
 * embeddings, and reports the similar speakers found. Both files may hold one
 * or several speakers, so 1:N, N:1 and N:N searches are all supported.
 * CLI only.
 *
 * Usage: vr_xvfind [options] <ref-file> <trial-file> [output]
 *
 * The trial file (search file) holds one query per line, space separated, the
 * SPM path closing the line:
 * <id> <channel> <speakerId> <spmFile>
 *
 * The reference file (database file) holds one SPM path per line: every SPM
 * the queries are searched in.
 *
 * Hits are written to the output file, or to stdout when none is given, one
 * hit per line, space separated:
 * <queryId> <score> <channel> <speakerId> <spmFile>
 *
 * @example
 * ```php
 * $response = $vox->xvfind()
 *     ->trialQueries(
 *         SpeakerQueryList::create()
 *             ->add('Q001', 1, 'spk1', '/path/to/interview.spm')
 *     )
 *     ->referenceFiles(
 *         FileList::create()
 *             ->add('/path/to/archive1.spm')
 *             ->add('/path/to/archive2.spm')
 *     )
 *     ->run();
 *
 * $hits = SpeakerHit::fromOutput($response->getBody());
 * ```
 */
final class XvFind extends AbstractMethod
{
    private ?string $refFile = null;
    private ?string $trialFile = null;
    private ?string $output = null;

    private ?FileList $referenceFileList = null;
    private ?SpeakerQueryList $trialQueryList = null;

    private string $tempDir = '/tmp';

    public function getMethodName(): string
    {
        return 'vr_xvfind';
    }

    /**
     * @inheritDoc
     */
    protected static function defineParameters(): array
    {
        return [
            new Parameter('verbose', '-v', '', Parameter::TYPE_FLAG),
            new Parameter('cohortFile', '-c', '', Parameter::TYPE_FILE),
            new Parameter('cohortShortListSize', '-n', ''),
            new Parameter('prior', '-p', ''),
            new Parameter('threshold', '-t', ''),
        ];
    }

    /**
     * Set the reference file (database file): the SPM paths that are searched,
     * one per line.
     *
     * @param string $path Path to the reference file
     */
    public function refFile(string $path): self
    {
        $this->refFile = $path;
        $this->referenceFileList = null;
        return $this;
    }

    /**
     * Set the searched SPM files from a FileList object.
     *
     * A temporary reference file will be generated automatically.
     *
     * @example
     * ```php
     * $xvfind->referenceFiles(
     *     FileList::create()
     *         ->add('/path/to/archive1.spm')
     *         ->add('/path/to/archive2.spm')
     * );
     * ```
     */
    public function referenceFiles(FileList $list): self
    {
        $this->referenceFileList = $list;
        $this->refFile = null;
        return $this;
    }

    /**
     * Set the trial file (search file): the speakers looked up in the
     * reference database, one query per line.
     *
     * @param string $path Path to the trial file
     */
    public function trialFile(string $path): self
    {
        $this->trialFile = $path;
        $this->trialQueryList = null;
        return $this;
    }

    /**
     * Set the searched speakers from a SpeakerQueryList object.
     *
     * A temporary trial file will be generated automatically.
     *
     * @example
     * ```php
     * $xvfind->trialQueries(
     *     SpeakerQueryList::create()
     *         ->add('Q001', 1, 'spk1', '/path/to/interview.spm')
     *         ->addQuery(2, 'spk3', '/path/to/meeting.spm')
     * );
     * ```
     */
    public function trialQueries(SpeakerQueryList $list): self
    {
        $this->trialQueryList = $list;
        $this->trialFile = null;
        return $this;
    }

    /**
     * Set the output file path.
     *
     * If not specified, hits are written to stdout and can be read from the
     * response.
     *
     * @param string $path Path for the output file
     */
    public function output(string $path): self
    {
        $this->output = $path;
        return $this;
    }

    /**
     * Set the cohort vectors file.
     *
     * @param string $path Path to the cohort vectors file
     */
    public function cohortFile(string $path): self
    {
        $this->parameters['cohortFile'] = $path;
        return $this;
    }

    /**
     * Set the cohort short list size.
     *
     * Number of cohort vectors kept in the short list, not the size of the
     * cohort itself (the cohort is defined by cohortFile()).
     *
     * @param int $n Short list size (default: 200)
     */
    public function cohortShortListSize(int $n): self
    {
        $this->parameters['cohortShortListSize'] = $n;
        return $this;
    }

    /**
     * Set the known target prior.
     *
     * Prior probability that a searched speaker is present in the reference
     * database.
     *
     * @param float $p Prior probability (default: 0.01)
     */
    public function prior(float $p): self
    {
        $this->parameters['prior'] = $p;
        return $this;
    }

    /**
     * Set the similarity threshold.
     *
     * @param float $t Threshold (default: 0.70)
     */
    public function threshold(float $t): self
    {
        $this->parameters['threshold'] = $t;
        return $this;
    }

    /**
     * Set the output file path.
     *
     * vr_xvfind takes the output as a positional argument, so this is an
     * alias of output().
     */
    public function outputFile(string $path): static
    {
        return $this->output($path);
    }

    /**
     * Set the directory holding the generated trial and reference files.
     *
     * Not passed to the binary: the -t option of vr_xvfind carries the
     * similarity threshold.
     */
    public function tmpDir(string $path): static
    {
        $this->tempDir = $path;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function toRequest(): Request
    {
        if ($this->referenceFileList !== null) {
            $this->refFile = $this->referenceFileList->writeToTempFile($this->tempDir);
        }

        if ($this->trialQueryList !== null) {
            $this->trialFile = $this->trialQueryList->writeToTempFile($this->tempDir);
        }

        if ($this->refFile === null) {
            throw new \LogicException('vr_xvfind requires a reference file. Use refFile() or referenceFiles().');
        }

        if ($this->trialFile === null) {
            throw new \LogicException('vr_xvfind requires a trial file. Use trialFile() or trialQueries().');
        }

        $positionalArgs = [$this->refFile, $this->trialFile];

        if ($this->output !== null) {
            $positionalArgs[] = $this->output;
        }

        return new Request(
            method: $this->getMethodName(),
            parameters: $this->parameters,
            parameterDefinitions: static::parameters(),
            positionalArgs: $positionalArgs,
        );
    }
}
