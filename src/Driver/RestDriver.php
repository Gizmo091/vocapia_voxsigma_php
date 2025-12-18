<?php

declare(strict_types=1);

namespace Vocapia\Voxsigma\Driver;

use Vocapia\Voxsigma\Auth\CredentialInterface;
use Vocapia\Voxsigma\Exception\DriverException;
use Vocapia\Voxsigma\Parameter\Parameter;

/**
 * REST driver for executing VoxSigma via remote API.
 *
 * Uses cURL to communicate with the VoxSigma REST API.
 */
final class RestDriver implements DriverInterface
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly CredentialInterface $credential,
    ) {
    }

    public function execute(Request $request): Response
    {
        $url = $this->buildUrl($request);
        $postFields = $this->buildPostFields($request);

        $curl = curl_init();

        $headers = [];
        $curlOptions = [
            \CURLOPT_URL => $url,
            \CURLOPT_RETURNTRANSFER => true,
            \CURLOPT_SSL_VERIFYPEER => false,
            \CURLOPT_TIMEOUT => 0, // No timeout for long transcriptions
        ];

        // Apply authentication
        $this->credential->applyTo($headers, $curlOptions);

        // POST with multipart form data if we have files
        if (!empty($postFields)) {
            $curlOptions[\CURLOPT_POST] = true;
            $curlOptions[\CURLOPT_POSTFIELDS] = $postFields;
        }

        if (!empty($headers)) {
            $curlOptions[\CURLOPT_HTTPHEADER] = $headers;
        }

        curl_setopt_array($curl, $curlOptions);

        $response = curl_exec($curl);
        $httpStatus = curl_getinfo($curl, \CURLINFO_HTTP_CODE);
        $error = curl_error($curl);

        curl_close($curl);

        if (!is_string($response)) {
            return Response::failure(
                error: $error ?: 'cURL request failed',
                httpStatus: $httpStatus,
            );
        }

        // Check for error in XML response
        $errorCode = $this->extractErrorCode($response);

        if ($httpStatus === 200 && $errorCode === null) {
            return Response::success($response, httpStatus: $httpStatus);
        }

        return Response::failure(
            error: $this->extractErrorMessage($response) ?: 'Request failed',
            errorCode: $errorCode,
            httpStatus: $httpStatus,
            xml: $response,
        );
    }

    public function executeAsync(Request $request): AsyncHandle
    {
        // Add async parameter
        $asyncRequest = new Request(
            method: $request->method,
            parameters: array_merge($request->parameters, ['async' => true]),
            audioFile: $request->audioFile,
            audioContent: $request->audioContent,
            textFile: $request->textFile,
            stdin: $request->stdin,
        );

        $url = $this->buildUrl($asyncRequest);
        $postFields = $this->buildPostFields($asyncRequest);

        $curl = curl_init();

        $headers = [];
        $curlOptions = [
            \CURLOPT_URL => $url,
            \CURLOPT_RETURNTRANSFER => true,
            \CURLOPT_SSL_VERIFYPEER => false,
            \CURLOPT_TIMEOUT => 60,
        ];

        // Apply authentication
        $this->credential->applyTo($headers, $curlOptions);

        // POST with multipart form data
        if (!empty($postFields)) {
            $curlOptions[\CURLOPT_POST] = true;
            $curlOptions[\CURLOPT_POSTFIELDS] = $postFields;
        }

        if (!empty($headers)) {
            $curlOptions[\CURLOPT_HTTPHEADER] = $headers;
        }

        curl_setopt_array($curl, $curlOptions);

        $response = curl_exec($curl);
        $httpStatus = curl_getinfo($curl, \CURLINFO_HTTP_CODE);
        $error = curl_error($curl);

        curl_close($curl);

        if (!is_string($response)) {
            throw new DriverException('Failed to start async request: ' . ($error ?: 'Unknown error'));
        }

        // Extract session ID from response
        $sessionId = $this->extractSessionId($response);

        if ($sessionId === null) {
            throw new DriverException('Failed to get session ID from async response: ' . $response);
        }

        return new RestAsyncHandle(
            $sessionId,
            $this->baseUrl,
            $this->credential,
        );
    }

    public function supportsPipeline(): bool
    {
        return false;
    }

    /**
     * Generate equivalent curl command for debugging.
     *
     * @param Request $request The request to convert
     * @param bool $async Whether to generate async version
     * @return string The curl command
     */
    public function toCurl(Request $request, bool $async = false): string
    {
        if ($async) {
            $request = new Request(
                method: $request->method,
                parameters: array_merge($request->parameters, ['async' => true]),
                audioFile: $request->audioFile,
                audioContent: $request->audioContent,
                textFile: $request->textFile,
                stdin: $request->stdin,
            );
        }

        $url = $this->buildUrl($request);
        $parts = ['curl', '-k']; // -k for insecure (SSL verify off)

        // Add authentication
        $parts = array_merge($parts, $this->credential->toCurlArgs());

        // Add translated parameters as form fields
        foreach ($this->translateParameters($request) as $key => $value) {
            $parts[] = '-F';
            $parts[] = $key . '=' . $this->escapeArg((string) $value);
        }

        // Add files
        if ($request->audioFile !== null) {
            $parts[] = '-F';
            $parts[] = 'audiofile=@' . $this->escapeArg($request->audioFile);
        }

        if ($request->textFile !== null) {
            $parts[] = '-F';
            $parts[] = 'textfile=@' . $this->escapeArg($request->textFile);
        }

        // Add file parameters using Parameter definitions
        $definitions = $request->parameterDefinitions;
        if ($definitions !== null) {
            foreach ($request->parameters as $name => $value) {
                $param = $definitions->findByName($name);
                if ($param !== null && $param->type === Parameter::TYPE_FILE && $param->restParam !== '') {
                    if (is_string($value)) {
                        $parts[] = '-F';
                        $parts[] = $param->restParam . '=@' . $this->escapeArg($value);
                    }
                }
            }
        }

        // Add URL (quoted)
        $parts[] = $this->escapeArg($url);

        return implode(' ', $parts);
    }

    /**
     * Escape argument for shell.
     */
    private function escapeArg(string $arg): string
    {
        // If no special chars, return as-is
        if (preg_match('/^[a-zA-Z0-9._\/:=-]+$/', $arg)) {
            return $arg;
        }
        // Otherwise, single-quote it
        return "'" . str_replace("'", "'\\''", $arg) . "'";
    }

    /**
     * Build the URL for a request.
     */
    private function buildUrl(Request $request): string
    {
        // Only method goes in URL, all other params go as POST fields
        return $this->baseUrl . '/voxsigma?method=' . urlencode($request->method);
    }

    /**
     * Build POST fields for multipart form data.
     *
     * @return array<string, mixed>
     */
    private function buildPostFields(Request $request): array
    {
        $fields = [];

        // Add all translated parameters as form fields
        foreach ($this->translateParameters($request) as $key => $value) {
            $fields[$key] = (string) $value;
        }

        // Audio file
        if ($request->audioFile !== null) {
            if (!file_exists($request->audioFile)) {
                throw new DriverException('Audio file not found: ' . $request->audioFile);
            }
            $fields['audiofile'] = new \CURLFile($request->audioFile);
        } elseif ($request->audioContent !== null) {
            // For raw audio content, create a temp file
            $tmpFile = tempnam(sys_get_temp_dir(), 'voxsigma_');
            file_put_contents($tmpFile, $request->audioContent);
            $fields['audiofile'] = new \CURLFile($tmpFile);
        }

        // Text file (for alignment)
        if ($request->textFile !== null) {
            if (!file_exists($request->textFile)) {
                throw new DriverException('Text file not found: ' . $request->textFile);
            }
            $fields['textfile'] = new \CURLFile($request->textFile);
        }

        // Add file parameters using Parameter definitions
        $definitions = $request->parameterDefinitions;
        if ($definitions !== null) {
            foreach ($request->parameters as $name => $value) {
                $param = $definitions->findByName($name);
                if ($param !== null && $param->type === Parameter::TYPE_FILE && $param->restParam !== '') {
                    if (is_string($value) && file_exists($value)) {
                        $fields[$param->restParam] = new \CURLFile($value);
                    }
                }
            }
        }

        return $fields;
    }

    /**
     * Translate driver-agnostic parameters to REST query parameters.
     *
     * @return array<string, string|int|float>
     */
    private function translateParameters(Request $request): array
    {
        $result = [];
        $params = $request->parameters;
        $definitions = $request->parameterDefinitions;

        // If no parameter definitions, return empty (shouldn't happen in normal use)
        if ($definitions === null) {
            return $result;
        }

        // Parameters that need special handling (combined into qopt)
        $qoptParams = ['dualChannel', 'noPartitioning', 'quality'];
        $qoptValue = '';

        // Parameters to skip (handled specially or are files)
        $skipParams = ['async', 'vocabularyFile', 'languageListFile', 'speakerListFile'];

        // Iterate through all parameters and translate using definitions
        foreach ($params as $name => $value) {
            if (in_array($name, $skipParams, true)) {
                continue;
            }

            $param = $definitions->findByName($name);
            if ($param === null) {
                continue; // Unknown parameter, skip
            }

            // Skip if REST param is empty (CLI-only parameter)
            if ($param->restParam === '') {
                continue;
            }

            // Skip file parameters (handled separately as CURLFile)
            if ($param->type === Parameter::TYPE_FILE) {
                continue;
            }

            // Special handling for qopt (combining multiple flags)
            if ($param->restParam === 'qopt') {
                if ($param->type === Parameter::TYPE_FLAG && $value) {
                    $qoptValue .= $param->flagValue ?? '';
                } elseif ($param->type === Parameter::TYPE_VALUE) {
                    $qoptValue .= (string) $value;
                }
                continue;
            }

            // Use Parameter to generate REST field
            $fields = $param->toRestField($value);
            foreach ($fields as $key => $val) {
                $result[$key] = $val;
            }
        }

        // Add combined qopt if any
        if ($qoptValue !== '') {
            $result['qopt'] = $qoptValue;
        }

        // Handle async parameter (not in definitions)
        if (!empty($params['async'])) {
            $result['async'] = '1';
        }

        return $result;
    }

    /**
     * Extract session ID from async response.
     *
     * Expected format: <Session>694400c5-5e17c77d-0e36</Session>
     */
    private function extractSessionId(string $xml): ?string
    {
        if (preg_match('/<Session>([^<]+)<\/Session>/', $xml, $matches)) {
            return trim($matches[1]);
        }
        return null;
    }

    /**
     * Extract error code from XML response.
     */
    private function extractErrorCode(string $xml): ?int
    {
        if (preg_match('/<Error\s+code="(\d+)"/', $xml, $matches)) {
            return (int) $matches[1];
        }
        return null;
    }

    /**
     * Extract error message from XML response.
     */
    private function extractErrorMessage(string $xml): ?string
    {
        if (preg_match('/<Error[^>]*>([^<]+)<\/Error>/', $xml, $matches)) {
            return trim($matches[1]);
        }
        return null;
    }
}
