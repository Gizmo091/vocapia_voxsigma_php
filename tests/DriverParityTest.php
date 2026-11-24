<?php

declare(strict_types=1);

/**
 * CLI and REST drivers must ask VoxSigma for the same processing.
 *
 * Compares toCli() and toCurl() for the same request. The REST side is
 * mapped to the command line vr_wsapi builds: -l:<model>, or -l<model>
 * when a qopt contains 'x'.
 *
 * Usage: php tests/DriverParityTest.php
 */

use Vocapia\Voxsigma\Auth\UserPasswordCredential;
use Vocapia\Voxsigma\Driver\CliDriver;
use Vocapia\Voxsigma\Driver\RestDriver;
use Vocapia\Voxsigma\Method\AbstractMethod;
use Vocapia\Voxsigma\Method\Lid;
use Vocapia\Voxsigma\Method\Part;
use Vocapia\Voxsigma\Method\Trans;
use Vocapia\Voxsigma\Pipeline\Pipeline;

require __DIR__ . '/../vendor/autoload.php';

$cli = new CliDriver(__DIR__, logging: false);
$rest = new RestDriver('https://localhost', new UserPasswordCredential('u', 'p'), logging: false);
$failed = 0;

function check(string $label, mixed $expected, mixed $actual): void
{
    global $failed;
    $ok = $expected === $actual;
    $failed += $ok ? 0 : 1;
    echo $ok ? '  ok   ' : '  FAIL ', $label, "\n";
    if (!$ok) {
        echo '    expected: ', json_encode($expected), "\n    actual:   ", json_encode($actual), "\n";
    }
}

/** @return list<string> sorted options of the generated command */
function cliOptions(AbstractMethod $m, CliDriver $driver): array
{
    $options = array_values(array_filter(
        explode(' ', $m->withDriver($driver)->toCli()),
        static fn (string $a): bool => str_starts_with($a, '-') && $a !== '-',
    ));
    sort($options);
    return $options;
}

/** @return list<string> key=value fields, in order */
function restFields(AbstractMethod $m, RestDriver $driver, bool $async = false): array
{
    preg_match_all('/(?:-F|--form-string) (\S+)/', $m->withDriver($driver)->toCurl($async), $found);
    return array_map(static fn (string $f): string => str_replace("'", '', $f), $found[1]);
}

/**
 * @param list<string> $fields
 * @return list<string>
 */
function serverOptions(array $fields): array
{
    $options = [];
    $model = null;
    $lid = false;
    $dl = false;
    foreach ($fields as $field) {
        [$key, $value] = explode('=', $field, 2);
        if ($key === 'model') {
            $model = $value;
        } elseif ($key === 'qopt') {
            $options[] = '-q' . $value;
            $lid = $lid || (!str_starts_with($value, '@') && str_contains($value, 'x'));
        } elseif ($key === 'dlopt') {
            $options[] = '-dl' . $value;
            $dl = true;
        } else {
            $options[] = 'unexpected ' . $field;
        }
    }
    if ($model === 'und') {
        if (!$lid && !$dl) {
            $options[] = '-dl30';
        }
    } elseif ($model !== null) {
        $options[] = ($lid ? '-l' : '-l:') . $model;
    }
    sort($options);
    return $options;
}

$cases = [
    'model' => [fn () => (new Trans())->model('fre'), ['-l:fre']],
    'forceLanguage' => [fn () => (new Trans())->model('fre')->forceLanguage(), ['-l:fre']],
    'forceLanguage(false)' => [fn () => (new Trans())->model('fre')->forceLanguage(false), ['-lfre', '-qx1']],
    'forceLanguage(false) + multilingual' => [
        fn () => (new Trans())->model('fre')->forceLanguage(false)->multilingual(true),
        ['-lfre', '-qxs'],
    ],
    'multilingual' => [fn () => (new Trans())->model('fre')->multilingual(true), ['-lfre', '-qxs']],
    'multilingual + dualChannel' => [
        fn () => (new Trans())->model('fre')->multilingual(true)->dualChannel(),
        ['-lfre', '-qdxs'],
    ],
    'dualChannel + multilingual' => [
        fn () => (new Trans())->model('fre')->dualChannel()->multilingual(true),
        ['-lfre', '-qdxs'],
    ],
    'multilingual without model' => [fn () => (new Trans())->multilingual(), ['-qx']],
    'multilingual + dualChannel + quality' => [
        fn () => (new Trans())->model('fre')->multilingual(true)->quality(2)->dualChannel(),
        ['-lfre', '-q2', '-qdxs'],
    ],
    'lidDuration does not enable LID' => [
        fn () => (new Trans())->model('fre')->lidDuration(10),
        ['-dl10', '-l:fre'],
    ],
    'und' => [fn () => (new Trans())->model('und'), ['-dl30']],
    'und + lidDuration' => [fn () => (new Trans())->model('und')->lidDuration(10), ['-dl10']],
    'und + multilingual' => [fn () => (new Trans())->model('und')->multilingual(), ['-qx']],
    'Part sadModel + dualChannel' => [
        fn () => (new Part())->sadModel('s')->dualChannel(),
        ['-q@s', '-qd'],
    ],
];

foreach ($cases as $label => [$build, $expected]) {
    sort($expected);
    check("$label: CLI", $expected, cliOptions($build(), $cli));
    check("$label: REST", $expected, serverOptions(restFields($build(), $rest)));
}

foreach (['cli' => $cli, 'rest' => $rest] as $name => $driver) {
    try {
        $m = (new Trans())->model('fre')->forceLanguage()->multilingual()->withDriver($driver);
        $name === 'cli' ? $m->toCli() : $m->toCurl();
        check("forceLanguage + multilingual refused: $name", true, false);
    } catch (InvalidArgumentException) {
        check("forceLanguage + multilingual refused: $name", true, true);
    }
}

$m = (new Trans())->model('fre')->forceLanguage(false);
$m->toRequest();
check('toRequest() leaves the builder untouched', ['model' => 'fre', 'forceLanguage' => false], $m->getParameters());

check(
    'async keeps parameters',
    ['model=fre', 'qopt=2', 'qopt=dxs', 'async=1'],
    restFields((new Trans())->model('fre')->multilingual(true)->dualChannel()->quality(2), $rest, true),
);

// Executed requests, sync and async, must carry the parameters and repeated qopt
$server = stream_socket_server('tcp://127.0.0.1:0');
$port = explode(':', stream_socket_get_name($server, false))[1];
foreach (['execute', 'executeAsync'] as $call) {
    $code = 'require ' . var_export(__DIR__ . '/../vendor/autoload.php', true) . ';'
        . '$d=new ' . RestDriver::class . '("http://127.0.0.1:' . $port . '",new '
        . UserPasswordCredential::class . '("u","p"),logging:false);'
        . '$d->' . $call . '((new ' . Trans::class . '())->model("fre")->multilingual(true)->quality(2)->toRequest());';
    $process = proc_open([PHP_BINARY, '-r', $code], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    $connection = stream_socket_accept($server, 10);
    stream_set_timeout($connection, 1);
    $raw = '';
    while (($chunk = fread($connection, 8192)) !== '' && $chunk !== false) {
        $raw .= $chunk;
    }
    $body = '<Session>1</Session>';
    fwrite($connection, "HTTP/1.1 200 OK\r\nContent-Length: " . strlen($body) . "\r\nConnection: close\r\n\r\n" . $body);
    fclose($connection);
    proc_close($process);
    preg_match_all('/name="(\w+)"\r\n\r\n([^\r]*)/', $raw, $found, PREG_SET_ORDER);
    $sent = array_map(static fn (array $f): string => $f[1] . '=' . $f[2], $found);
    $expected = ['model=fre', 'qopt=2', 'qopt=xs'];
    if ($call === 'executeAsync') {
        $expected[] = 'async=1';
    }
    check("$call sends the parameters", $expected, $sent);
}

// Pipelines: fake binaries print their arguments, so the output is the last stage's
$bin = sys_get_temp_dir() . '/voxsigma_parity_' . getmypid();
mkdir($bin);
foreach (['vrxs_lid', 'vrxs_part', 'vrxs_trans'] as $name) {
    file_put_contents("$bin/$name", "#!/bin/sh\necho \"\$@\"\n");
    chmod("$bin/$name", 0755);
}
$fake = new CliDriver($bin, logging: false);
$pipeline = static fn (): Pipeline => Pipeline::create()->withDriver($fake)->input('a.wav');
$run = static fn (Pipeline $p): string => trim((string) $p->run()->getXml());
$fre = static fn (): Trans => (new Trans())->model('fre');

check('pipeline without lid', '-l:fre -', $run($pipeline()->add(new Part())->add($fre())));
check('pipeline trans alone', '-l:fre -f a.wav', $run($pipeline()->add($fre())));
check('pipeline after lid', '-lfre -', $run($pipeline()->add(new Lid())->add($fre())));
check('pipeline lid, part, trans', '-lfre -', $run($pipeline()->add(new Lid())->add(new Part())->add($fre())));
check('pipeline after lid, forced', '-l:fre -', $run($pipeline()->add(new Lid())->add($fre()->forceLanguage())));
check('pipeline after lid, not forced', '-lfre -', $run($pipeline()->add(new Lid())->add($fre()->forceLanguage(false))));
check('pipeline after lid, multilingual', '-lfre -qxs -', $run($pipeline()->add(new Lid())->add($fre()->multilingual(true))));
check('pipeline after lid, no model', '-', $run($pipeline()->add(new Lid())->add(new Trans())));
check('pipeline keeps first stage options', '-k2 -f a.wav', $run($pipeline()->add((new Part())->maxSpeakers(2))));
array_map('unlink', glob("$bin/*"));
rmdir($bin);

echo $failed === 0 ? "OK\n" : "$failed failure(s)\n";
exit($failed === 0 ? 0 : 1);
