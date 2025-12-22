<?php

declare(strict_types=1);

namespace Vocapia\Voxsigma\Method;

use Vocapia\Voxsigma\Driver\Request;
use Vocapia\Voxsigma\Model\FileList;
use Vocapia\Voxsigma\Model\KeywordList;
use Vocapia\Voxsigma\Parameter\Parameter;

/**
 * VoxSigma keyword spotting method (vrxs_kws).
 *
 * Searches for keywords phonetically and textually in transcription files.
 * CLI only.
 */
final class Kws extends AbstractMethod
{
    private ?KeywordList $keywordList = null;
    private ?FileList $inputFileList = null;

    public function getMethodName(): string
    {
        return 'vrxs_kws';
    }

    /**
     * @inheritDoc
     */
    protected static function defineParameters(): array
    {
        return array_merge(static::commonParameters(), [
            new Parameter('keywordListFile', '-kl', '', Parameter::TYPE_FILE),
            new Parameter('inputListFile', '-kf', '', Parameter::TYPE_FILE),
            new Parameter('context', '-kc', ''),
        ]);
    }

    /**
     * Set the keyword list file (.kwl).
     *
     * File format (space separated):
     * - Column 1: Keyword ID (e.g., KW001)
     * - Column 2: Minimum hit threshold (e.g., 0.5)
     * - Column 3+: Keyword text (e.g., "Mon Mot")
     *
     * @param string $path Path to .kwl file
     */
    public function keywordListFile(string $path): self
    {
        $this->parameters['keywordListFile'] = $path;
        $this->keywordList = null;
        return $this;
    }

    /**
     * Set keywords from a KeywordList object.
     *
     * A temporary file will be generated automatically.
     *
     * @example
     * ```php
     * $kws->keywordList(
     *     KeywordList::create()
     *         ->add('KW001', 0.5, 'Mon Mot')
     *         ->addKeyword('MyWorld', 0.5)
     * );
     * ```
     */
    public function keywordList(KeywordList $list): self
    {
        $this->keywordList = $list;
        unset($this->parameters['keywordListFile']);
        return $this;
    }

    /**
     * Set the input file list (.klst) - list of kar/xml files to search.
     *
     * @param string $path Path to file containing paths (one per line)
     */
    public function inputListFile(string $path): self
    {
        $this->parameters['inputListFile'] = $path;
        $this->inputFileList = null;
        return $this;
    }

    /**
     * Set input files from a FileList object.
     *
     * A temporary file will be generated automatically.
     *
     * @example
     * ```php
     * $kws->inputFiles(
     *     FileList::create()
     *         ->add('/path/to/file1.kar')
     *         ->add('/path/to/file2.xml')
     * );
     * ```
     */
    public function inputFiles(FileList $list): self
    {
        $this->inputFileList = $list;
        unset($this->parameters['inputListFile']);
        return $this;
    }

    /**
     * Set the keyword context scope in seconds.
     *
     * Defines how many seconds of surrounding words to include
     * in the output XML when a keyword is found.
     *
     * @param int $seconds Context size in seconds
     */
    public function context(int $seconds): self
    {
        $this->parameters['context'] = $seconds;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function toRequest(): Request
    {
        // Get temp directory from parameters or use default
        $tmpDir = $this->parameters['tmpDir'] ?? '/tmp';

        // Generate temp files if using object lists
        if ($this->keywordList !== null) {
            $this->parameters['keywordListFile'] = $this->keywordList->writeToTempFile($tmpDir);
        }

        if ($this->inputFileList !== null) {
            $this->parameters['inputListFile'] = $this->inputFileList->writeToTempFile($tmpDir);
        }

        return parent::toRequest();
    }
}