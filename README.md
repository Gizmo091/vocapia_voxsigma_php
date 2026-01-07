# VoxSigma PHP SDK

PHP SDK for [Vocapia](https://www.vocapia.com/) VoxSigma speech-to-text, supporting both CLI binaries and REST API.

## Requirements

- PHP 8.1+
- ext-curl (for REST driver)
- VoxSigma binaries installed locally (for CLI driver) OR REST API credentials

## Installation

```bash
composer require vocapia/voxsigma
```

## Quick Start

### CLI Driver (local binaries)

```php
use Vocapia\Voxsigma\VoxSigma;

$vox = VoxSigma::cli('/usr/local/vrxs');

$response = $vox->trans()
    ->model('fre')
    ->file('/path/to/audio.wav')
    ->run();

echo $response->getXml();
```

### REST Driver (remote API)

```php
use Vocapia\Voxsigma\VoxSigma;
use Vocapia\Voxsigma\Auth\UserPasswordCredential;

$credential = new UserPasswordCredential('user', 'password');
$vox = VoxSigma::rest('https://your-voxsigma-server.com', $credential);

$response = $vox->trans()
    ->model('fre')
    ->file('/path/to/audio.wav')
    ->run();

echo $response->getXml();
```

## Authentication (REST)

### HTTP Basic Auth

```php
use Vocapia\Voxsigma\Auth\UserPasswordCredential;

$credential = new UserPasswordCredential('username', 'password');
```

### API Key

```php
use Vocapia\Voxsigma\Auth\ApiKeyCredential;

$credential = new ApiKeyCredential('your-api-key');
```

## Methods

### Transcription (trans)

```php
$response = $vox->trans()
    ->model('fre')                    // Language model (fre, eng-usa, etc.)
    ->file('/path/to/audio.wav')      // Audio file
    ->maxSpeakers(2)                  // Limit speaker count
    ->dualChannel()                   // Enable dual channel mode
    ->noPartitioning()                // Disable speaker partitioning
    ->verbose()                       // Enable verbose output
    ->run();
```

### Speaker Partitioning (part)

```php
$response = $vox->part()
    ->model('fre')
    ->file('/path/to/audio.wav')
    ->maxSpeakers(4)
    ->speakerRange(2, 6)              // Min/max speakers
    ->channel(1)                      // Select channel
    ->run();
```

### Language Identification (lid)

```php
$response = $vox->lid()
    ->model('fre')
    ->file('/path/to/audio.wav')
    ->duration(30.0)                  // Analysis duration
    ->threshold(0.5)                  // Detection threshold
    ->version('7.1')                  // LID version
    ->run();
```

### Forced Alignment (align) - REST

```php
$response = $vox->align()
    ->model('fre')
    ->file('/path/to/audio.wav')
    ->textFile('/path/to/transcript.txt')
    ->speakerSegmentation()
    ->run();
```

### DTMF Detection (dtmf) - CLI only

```php
$response = $vox->dtmf()
    ->file('/path/to/audio.wav')
    ->run();
```

### Keyword Spotting (kws) - CLI only

Search for keywords phonetically and textually in transcription files:

```php
use Vocapia\Voxsigma\Model\KeywordList;
use Vocapia\Voxsigma\Model\FileList;

$response = $vox->kws()
    ->keywordList(
        KeywordList::create()
            ->add('KW001', 0.5, 'hello world')
            ->add('KW002', 0.4, 'bonjour')
            ->addKeyword('keyword', 0.5)  // Auto-generated ID
    )
    ->inputFiles(
        FileList::create()
            ->add('/path/to/transcription1.kar')
            ->add('/path/to/transcription2.kar')
    )
    ->context(5)  // Include 5 seconds of surrounding words
    ->run();
```

Or use existing files:

```php
$response = $vox->kws()
    ->keywordListFile('/path/to/keywords.kwl')
    ->inputKarList('/path/to/files.klst')
    ->context(5)
    ->run();
```

**Keyword list file format (.kwl):**
```
KW001 0.5 hello world
KW002 0.4 bonjour
KW003 0.5 keyword
```
Columns: ID, threshold (0.0-1.0), keyword text

**Input list file format (.klst):**
```
/path/to/file1.kar
/path/to/file2.kar
```

### XML to KAR Converter (xml2kar) - CLI only

Convert XML transcription files to KAR format for keyword spotting:

```php
$response = $vox->xml2kar()
    ->xmlFile('/path/to/transcription.xml')
    ->karFile('/path/to/output.kar')
    ->run();

if ($response->isSuccess()) {
    // KAR file created at /path/to/output.kar
}
```

### Hello (REST connection test)

```php
$response = $vox->hello()->run();

if ($response->isSuccess()) {
    echo "Connected!";
}
```

## Async Execution (REST)

```php
$handle = $vox->trans()
    ->model('fre')
    ->file('/path/to/audio.wav')
    ->runAsync();

// Poll for completion
while ($handle->isRunning()) {
    sleep(5);
}

$response = $handle->wait();
```

Check status of a session:

```php
$response = $vox->status()
    ->session($handle->getId())
    ->run();
```

## Pipeline (CLI only)

Chain multiple operations using Unix pipes:

```php
$response = $vox->pipeline()
    ->input('/path/to/audio.wav')
    ->dtmf()
    ->part()->maxSpeakers(2)->done()
    ->lid()
    ->trans()->model('fre')->done()
    ->run();
```

## Response

```php
$response = $vox->trans()->model('fre')->file($audio)->run();

// Check status
$response->isSuccess();     // bool
$response->getError();      // ?string
$response->getErrorCode();  // ?int (VoxSigma error code)

// Get result
$response->getXml();        // Raw XML string

// HTTP/CLI info
$response->getHttpStatus(); // ?int (REST only)
$response->getExitCode();   // ?int (CLI only)
```

## Configuration

### From environment

```php
use Vocapia\Voxsigma\Config\Configuration;

// Reads VOCAPIA_VOXSIGMA_* environment variables
$config = Configuration::fromEnvironment();
$vox = new VoxSigma($config);
```

Environment variables:
- `VOCAPIA_VOXSIGMA_DRIVER` - `cli` or `rest`
- `VOCAPIA_VOXSIGMA_VRXS_ROOT` - CLI installation path
- `VOCAPIA_VOXSIGMA_REST_URL` - REST API URL
- `VOCAPIA_VOXSIGMA_REST_USER` - REST username
- `VOCAPIA_VOXSIGMA_REST_PASSWORD` - REST password

### Manual configuration

```php
use Vocapia\Voxsigma\Config\Configuration;
use Vocapia\Voxsigma\Auth\UserPasswordCredential;

// CLI
$config = Configuration::cli(
    root: '/usr/local/vrxs',
    tmp: '/tmp'
);

// REST
$config = Configuration::rest(
    baseUrl: 'https://your-voxsigma-server.com',
    credential: new UserPasswordCredential('user', 'pass')
);

$vox = new VoxSigma($config);
```

## Debugging

### CLI

Generate the equivalent CLI command for debugging:

```php
$cli = $vox->trans()
    ->model('fre')
    ->file('/path/to/audio.wav')
    ->toCli();

echo $cli;
// /usr/local/vrxs/bin/vrxs_trans -lfre -f '/path/to/audio.wav'
```

### REST

Generate the equivalent curl command for debugging:

```php
$curl = $vox->trans()
    ->model('fre')
    ->file('/path/to/audio.wav')
    ->toCurl();

echo $curl;
// curl -k -u user:password -F model=fre -F audiofile=@/path/to/audio.wav 'https://server/voxsigma?method=vrxs_trans'
```

For async requests:

```php
$curl = $vox->trans()
    ->model('fre')
    ->file('/path/to/audio.wav')
    ->toCurl(async: true);
```

## Error Handling

```php
use Vocapia\Voxsigma\Exception\DriverException;
use Vocapia\Voxsigma\Exception\RequestFailedException;

try {
    $response = $vox->trans()->model('fre')->file($audio)->run();

    if (!$response->isSuccess()) {
        echo "Error: " . $response->getError();
        echo "Code: " . $response->getErrorCode();
    }
} catch (DriverException $e) {
    // Driver-level error (file not found, connection failed, etc.)
    echo $e->getMessage();
}
```

## License

MIT