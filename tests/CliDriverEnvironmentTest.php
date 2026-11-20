<?php

declare(strict_types=1);

/**
 * CLI mode must hand the caller's environment to the VoxSigma binaries.
 *
 * Runs the checks in a child PHP process twice: once with $_ENV filled
 * (variables_order=EGPCS) and once with $_ENV empty (variables_order=GPCS,
 * as in php.ini-production). A fake binary prints its environment, which
 * must contain PATH, a VRXS_* variable set by the test and VRXS_TMP.
 *
 * Usage: php tests/CliDriverEnvironmentTest.php
 */

use Vocapia\Voxsigma\Driver\CliDriver;
use Vocapia\Voxsigma\Driver\Request;

const MARKER_NAME = 'VRXS_TEST_MARKER';
const MARKER_VALUE = 'from-caller-42';

if (($argv[1] ?? null) === '--child') {
    exit(runChecks());
}

$failed = 0;
foreach (['EGPCS', 'GPCS'] as $order) {
    $env = getenv();
    $env[MARKER_NAME] = MARKER_VALUE;

    $command = escapeshellarg(PHP_BINARY)
        . ' -n -d variables_order=' . $order
        . ' ' . escapeshellarg(__FILE__) . ' --child';

    $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, null, $env);
    if (!is_resource($process)) {
        fwrite(STDERR, "Cannot start child process\n");
        exit(1);
    }
    $output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $code = proc_close($process);

    echo "variables_order=$order\n" . $output;
    if ($code !== 0) {
        $failed++;
    }
}

echo $failed === 0 ? "OK\n" : "FAILED\n";
exit($failed === 0 ? 0 : 1);

function runChecks(): int
{
    require dirname(__DIR__) . '/vendor/autoload.php';

    $binDir = sys_get_temp_dir() . '/voxsigma-env-test-' . getmypid();
    @mkdir($binDir, 0700, true);
    $binary = $binDir . '/env_dump';
    file_put_contents($binary, "#!/bin/sh\nexec env\n");
    chmod($binary, 0700);

    $tmpDir = sys_get_temp_dir();
    $driver = new CliDriver($binDir, $tmpDir, logging: false);
    $request = new Request('env_dump');

    $outputs = [
        'execute' => $driver->execute($request)->getBody(),
        'executeAsync' => $driver->executeAsync($request)->wait(10.0)->getBody(),
        'executePipeline' => $driver->executePipeline([$request])->getBody(),
    ];

    unlink($binary);
    rmdir($binDir);

    $failures = 0;
    foreach ($outputs as $mode => $output) {
        $vars = parseEnv((string) $output);
        $checks = [
            'PATH is passed' => ($vars['PATH'] ?? '') !== '',
            MARKER_NAME . ' is passed' => ($vars[MARKER_NAME] ?? null) === MARKER_VALUE,
            'VRXS_TMP is set' => ($vars['VRXS_TMP'] ?? '') !== '',
        ];
        foreach ($checks as $label => $ok) {
            echo ($ok ? '  ok   ' : '  FAIL ') . "$mode: $label\n";
            if (!$ok) {
                $failures++;
            }
        }
    }

    return $failures === 0 ? 0 : 1;
}

/**
 * @return array<string, string>
 */
function parseEnv(string $output): array
{
    $vars = [];
    foreach (explode("\n", $output) as $line) {
        $pos = strpos($line, '=');
        if ($pos !== false) {
            $vars[substr($line, 0, $pos)] = substr($line, $pos + 1);
        }
    }
    return $vars;
}
