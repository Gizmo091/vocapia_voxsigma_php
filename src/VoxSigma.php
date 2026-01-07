<?php

declare(strict_types=1);

namespace Vocapia\Voxsigma;

use Vocapia\Voxsigma\Auth\CredentialInterface;
use Vocapia\Voxsigma\Config\Configuration;
use Vocapia\Voxsigma\Driver\AsyncHandle;
use Vocapia\Voxsigma\Driver\CliDriver;
use Vocapia\Voxsigma\Driver\DriverInterface;
use Vocapia\Voxsigma\Driver\Response;
use Vocapia\Voxsigma\Driver\RestDriver;
use Vocapia\Voxsigma\Method\AbstractMethod;
use Vocapia\Voxsigma\Method\Align;
use Vocapia\Voxsigma\Method\Dtmf;
use Vocapia\Voxsigma\Method\Hello;
use Vocapia\Voxsigma\Method\Kws;
use Vocapia\Voxsigma\Method\Lid;
use Vocapia\Voxsigma\Method\Part;
use Vocapia\Voxsigma\Method\Status;
use Vocapia\Voxsigma\Method\Trans;
use Vocapia\Voxsigma\Method\Xml2Kar;
use Vocapia\Voxsigma\Pipeline\Pipeline;

/**
 * Main entry point for the VoxSigma SDK.
 *
 * Provides a fluent interface for configuring and executing
 * VoxSigma speech-to-text operations via CLI or REST API.
 *
 * @example CLI usage:
 * ```php
 * $vox = VoxSigma::cli('/usr/local/vrxs');
 * $response = $vox->trans()
 *     ->model('fre')
 *     ->file('/path/audio.wav')
 *     ->run();
 * echo $response->getXml();
 * ```
 *
 * @example REST usage:
 * ```php
 * $vox = VoxSigma::rest('https://your-voxsigma-server.com', $credential);
 * $response = $vox->trans()
 *     ->model('eng')
 *     ->file('/path/audio.mp3')
 *     ->run();
 * ```
 *
 * @example Async REST:
 * ```php
 * $handle = $vox->trans()
 *     ->model('fre')
 *     ->file('/path/audio.wav')
 *     ->runAsync();
 *
 * while ($handle->isRunning()) {
 *     sleep(5);
 * }
 * $response = $handle->wait();
 * ```
 *
 * @example Pipeline (CLI only):
 * ```php
 * $response = $vox->pipeline()
 *     ->input('/path/audio.wav')
 *     ->dtmf()
 *     ->part()->maxSpeakers(2)->done()
 *     ->lid()
 *     ->trans()->model('fre')->done()
 *     ->run();
 * ```
 */
final class VoxSigma
{
    private DriverInterface $driver;
    private Configuration $config;

    public function __construct(Configuration $config)
    {
        $this->config = $config;
        $this->driver = $this->createDriver($config);
    }

    /**
     * Create a VoxSigma instance for CLI execution.
     *
     * @param string $root VoxSigma installation root (default: /usr/local/vrxs)
     * @param string $tmp Temporary directory (default: /tmp)
     */
    public static function cli(string $root = '/usr/local/vrxs', string $tmp = '/tmp'): self
    {
        return new self(Configuration::cli(root: $root, tmp: $tmp));
    }

    /**
     * Create a VoxSigma instance for REST API execution.
     *
     * @param string $baseUrl REST API base URL
     * @param CredentialInterface $credential Authentication credential
     */
    public static function rest(string $baseUrl, CredentialInterface $credential): self
    {
        return new self(Configuration::rest($baseUrl, $credential));
    }

    /**
     * Create a transcription method.
     */
    public function trans(): Trans
    {
        return (new Trans())->withDriver($this->driver);
    }

    /**
     * Create a speaker partitioning method.
     */
    public function part(): Part
    {
        return (new Part())->withDriver($this->driver);
    }

    /**
     * Create a language identification method.
     */
    public function lid(): Lid
    {
        return (new Lid())->withDriver($this->driver);
    }

    /**
     * Create a forced alignment method (REST primarily).
     */
    public function align(): Align
    {
        return (new Align())->withDriver($this->driver);
    }

    /**
     * Create a DTMF detection method (CLI only).
     */
    public function dtmf(): Dtmf
    {
        return (new Dtmf())->withDriver($this->driver);
    }

    /**
     * Create a keyword spotting method (CLI only).
     *
     * Searches for keywords phonetically and textually in transcription files.
     */
    public function kws(): Kws
    {
        return (new Kws())->withDriver($this->driver);
    }

    /**
     * Create a hello method for testing REST connection.
     */
    public function hello(): Hello
    {
        return (new Hello())->withDriver($this->driver);
    }

    /**
     * Create a status method for checking async sessions.
     */
    public function status(): Status
    {
        return (new Status())->withDriver($this->driver);
    }

    /**
     * Create an XML to KAR converter (CLI only).
     *
     * Converts XML transcription files to KAR format for keyword spotting.
     */
    public function xml2kar(): Xml2Kar
    {
        return (new Xml2Kar())->withDriver($this->driver);
    }

    /**
     * Create a pipeline for chaining CLI commands.
     *
     * Only works with CLI driver.
     *
     * @throws \LogicException If driver is not CLI
     */
    public function pipeline(): Pipeline
    {
        if (!$this->driver instanceof CliDriver) {
            throw new \LogicException('Pipeline is only supported with CLI driver');
        }

        return Pipeline::create()->withDriver($this->driver);
    }

    /**
     * Execute a method directly.
     */
    public function execute(AbstractMethod $method): Response
    {
        return $this->driver->execute($method->toRequest());
    }

    /**
     * Execute a method asynchronously.
     */
    public function executeAsync(AbstractMethod $method): AsyncHandle
    {
        return $this->driver->executeAsync($method->toRequest());
    }

    /**
     * Get the underlying driver.
     */
    public function getDriver(): DriverInterface
    {
        return $this->driver;
    }

    /**
     * Get the current configuration.
     */
    public function getConfig(): Configuration
    {
        return $this->config;
    }

    /**
     * Check if pipeline execution is supported.
     */
    public function supportsPipeline(): bool
    {
        return $this->driver->supportsPipeline();
    }

    /**
     * Create the appropriate driver based on configuration.
     */
    private function createDriver(Configuration $config): DriverInterface
    {
        return match ($config->driver) {
            'cli' => new CliDriver(
                $config->bin ?? $config->root . '/bin',
                $config->tmp,
            ),
            'rest' => new RestDriver(
                $config->baseUrl ?? throw new \InvalidArgumentException('REST driver requires baseUrl'),
                $config->credential ?? throw new \InvalidArgumentException('REST driver requires credential'),
            ),
            default => throw new \InvalidArgumentException('Unknown driver type: ' . $config->driver),
        };
    }
}
