<?php

declare(strict_types=1);

namespace Vocapia\Voxsigma\Method;

use Vocapia\Voxsigma\Driver\Request;
use Vocapia\Voxsigma\Parameter\Parameter;

/**
 * VoxSigma XML to KAR converter (xml2kar).
 *
 * Converts an XML transcription file to a KWS KAR file.
 * CLI only.
 */
final class Xml2Kar extends AbstractMethod
{
    private ?string $xmlFile = null;
    private ?string $karFile = null;

    public function getMethodName(): string
    {
        return 'xml2kar';
    }

    /**
     * @inheritDoc
     */
    protected static function defineParameters(): array
    {
        return [
            new Parameter('verbose', '-v', '', Parameter::TYPE_FLAG),
            new Parameter('workingDir', '-w:', ''),
        ];
    }

    /**
     * Set the input XML file to convert.
     *
     * @param string $path Path to XML transcription file
     */
    public function xmlFile(string $path): self
    {
        $this->xmlFile = $path;
        return $this;
    }

    /**
     * Set the output KAR file path.
     *
     * If not specified, output goes to stdout.
     *
     * @param string $path Path for output KAR file
     */
    public function karFile(string $path): self
    {
        $this->karFile = $path;
        return $this;
    }

    /**
     * Set the working directory.
     *
     * @param string $path Working directory path
     */
    public function workingDir(string $path): self
    {
        $this->parameters['workingDir'] = $path;
        return $this;
    }

    /**
     * @inheritDoc
     */
    public function toRequest(): Request
    {
        $positionalArgs = [];

        if ($this->xmlFile !== null) {
            $positionalArgs[] = $this->xmlFile;
        }

        if ($this->karFile !== null) {
            $positionalArgs[] = $this->karFile;
        }

        return new Request(
            method: $this->getMethodName(),
            parameters: $this->parameters,
            parameterDefinitions: static::parameters(),
            positionalArgs: $positionalArgs,
        );
    }
}