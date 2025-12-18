<?php

declare(strict_types=1);

namespace Vocapia\Voxsigma\Driver;

use Vocapia\Voxsigma\Exception\DriverException;
use Vocapia\Voxsigma\Parameter\Parameter;

/**
 * CLI driver for executing VoxSigma binaries locally.
 *
 * Uses proc_open to execute binaries and capture output.
 */
final class CliDriver implements DriverInterface
{
    public function __construct(
        private readonly string $binPath,
        private readonly string $tmpDir = '/tmp',
    ) {
    }

    public function execute(Request $request): Response
    {
        $command = $this->buildCommand($request);
        $descriptors = [
            0 => ['pipe', 'r'], // stdin
            1 => ['pipe', 'w'], // stdout
            2 => ['pipe', 'w'], // stderr
        ];

        $process = proc_open($command, $descriptors, $pipes, null, $this->getEnvironment());

        if (!is_resource($process)) {
            throw new DriverException('Failed to start process: ' . $command);
        }

        // Write stdin if provided
        if ($request->stdin !== null) {
            fwrite($pipes[0], $request->stdin);
        }
        fclose($pipes[0]);

        // Read output
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);

        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        if ($stdout === false) {
            $stdout = '';
        }
        if ($stderr === false) {
            $stderr = '';
        }

        if ($exitCode === 0) {
            return Response::success($stdout, $exitCode);
        }

        return Response::failure(
            error: $stderr ?: 'Process failed with exit code ' . $exitCode,
            errorCode: $exitCode,
            exitCode: $exitCode,
            xml: $stdout,
        );
    }

    public function executeAsync(Request $request): AsyncHandle
    {
        $command = $this->buildCommand($request);
        $descriptors = [
            0 => ['pipe', 'r'], // stdin
            1 => ['pipe', 'w'], // stdout
            2 => ['pipe', 'w'], // stderr
        ];

        $process = proc_open($command, $descriptors, $pipes, null, $this->getEnvironment());

        if (!is_resource($process)) {
            throw new DriverException('Failed to start process: ' . $command);
        }

        // Write stdin if provided and close
        if ($request->stdin !== null) {
            fwrite($pipes[0], $request->stdin);
        }
        fclose($pipes[0]);

        // Set pipes to non-blocking
        stream_set_blocking($pipes[1], false);
        stream_set_blocking($pipes[2], false);

        $status = proc_get_status($process);

        return new CliAsyncHandle(
            $process,
            $pipes[1],
            $pipes[2],
            $status['pid'],
        );
    }

    public function supportsPipeline(): bool
    {
        return true;
    }

    /**
     * Execute multiple requests as a pipeline.
     *
     * @param Request[] $requests
     */
    public function executePipeline(array $requests): Response
    {
        if (empty($requests)) {
            throw new DriverException('Pipeline requires at least one request');
        }

        // Build piped command
        $commands = [];
        foreach ($requests as $i => $request) {
            $cmd = $this->buildCommand($request, $i > 0);
            $commands[] = $cmd;
        }

        $pipelineCommand = implode(' | ', $commands);

        $descriptors = [
            0 => ['pipe', 'r'],
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w'],
        ];

        $process = proc_open($pipelineCommand, $descriptors, $pipes, null, $this->getEnvironment());

        if (!is_resource($process)) {
            throw new DriverException('Failed to start pipeline: ' . $pipelineCommand);
        }

        fclose($pipes[0]);

        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);

        fclose($pipes[1]);
        fclose($pipes[2]);

        $exitCode = proc_close($process);

        if ($stdout === false) {
            $stdout = '';
        }
        if ($stderr === false) {
            $stderr = '';
        }

        if ($exitCode === 0) {
            return Response::success($stdout, $exitCode);
        }

        return Response::failure(
            error: $stderr ?: 'Pipeline failed with exit code ' . $exitCode,
            errorCode: $exitCode,
            exitCode: $exitCode,
            xml: $stdout,
        );
    }

    /**
     * Build the command string for a request.
     */
    private function buildCommand(Request $request, bool $useStdin = false): string
    {
        $binary = $this->binPath . '/' . $request->method;
        $args = $this->translateParameters($request);

        // Add audio file
        if (!$useStdin && $request->audioFile !== null) {
            $args[] = '-f';
            $args[] = escapeshellarg($request->audioFile);
        } elseif ($useStdin) {
            $args[] = '-'; // Read from stdin
        }

        return $binary . ' ' . implode(' ', $args);
    }

    /**
     * Translate driver-agnostic parameters to CLI arguments.
     *
     * @return string[]
     */
    private function translateParameters(Request $request): array
    {
        $args = [];
        $params = $request->parameters;
        $definitions = $request->parameterDefinitions;

        // If no parameter definitions, return empty (shouldn't happen in normal use)
        if ($definitions === null) {
            return $args;
        }

        // Special case: model with forceLanguage modifier
        if (isset($params['model'])) {
            $forceLanguage = $params['forceLanguage'] ?? false;
            $prefix = $forceLanguage ? '-l:' : '-l';
            $args[] = $prefix . $params['model'];
        }

        // Parameters to skip (handled specially or via other params)
        $skipParams = ['model', 'forceLanguage', 'outputFile', 'tmpDir'];

        // Iterate through all parameters and translate using definitions
        foreach ($params as $name => $value) {
            if (in_array($name, $skipParams, true)) {
                continue;
            }

            $param = $definitions->findByName($name);
            if ($param === null) {
                continue; // Unknown parameter, skip
            }

            // Skip if CLI option is empty (REST-only parameter)
            if ($param->cliOption === '') {
                continue;
            }

            // Use Parameter to generate CLI args
            $cliArgs = $param->toCliArgs($value);

            // Escape file paths
            if ($param->type === Parameter::TYPE_FILE && !empty($cliArgs)) {
                $cliArgs[count($cliArgs) - 1] = escapeshellarg($cliArgs[count($cliArgs) - 1]);
            }

            $args = array_merge($args, $cliArgs);
        }

        // Handle outputFile and tmpDir (not in Parameter definitions but in AbstractMethod)
        if (isset($params['outputFile'])) {
            $args[] = '-o';
            $args[] = escapeshellarg($params['outputFile']);
        }

        if (isset($params['tmpDir'])) {
            $args[] = '-t';
            $args[] = escapeshellarg($params['tmpDir']);
        }

        return $args;
    }

    /**
     * Get environment variables for the process.
     *
     * @return array<string, string>
     */
    private function getEnvironment(): array
    {
        $env = [];

        // Pass through VRXS environment variables
        foreach ($_ENV as $key => $value) {
            if (str_starts_with($key, 'VRXS_')) {
                $env[$key] = $value;
            }
        }

        // Set VRXS_TMP if not already set
        if (!isset($env['VRXS_TMP'])) {
            $env['VRXS_TMP'] = $this->tmpDir;
        }

        return $env;
    }
}
