<?php

declare(strict_types=1);

namespace Vocapia\Voxsigma\Driver;

use Vocapia\Voxsigma\Auth\CredentialInterface;
use Vocapia\Voxsigma\Exception\DriverException;

/**
 * Async handle for REST driver (wraps a session ID).
 */
final class RestAsyncHandle implements AsyncHandle
{
    private ?Response $result = null;

    public function __construct(
        private readonly string $sessionId,
        private readonly string $baseUrl,
        private readonly CredentialInterface $credential,
    ) {
    }

    public function isRunning(): bool
    {
        if ($this->result !== null) {
            return false;
        }

        $response = $this->checkStatus();

        // Error code 320 means "in progress"
        // Error code 329 means "in queue"
        if ($response->errorCode === 320 || $response->errorCode === 329) {
            return true;
        }

        // Store result for later
        $this->result = $response;
        return false;
    }

    public function isFinished(): bool
    {
        return !$this->isRunning();
    }

    public function wait(?float $timeout = null): Response
    {
        if ($this->result !== null) {
            return $this->result;
        }

        $startTime = microtime(true);

        while (true) {
            $response = $this->checkStatus();

            // Error code 320 means "in progress"
            // Error code 329 means "in queue"
            if ($response->errorCode !== 320 && $response->errorCode !== 329) {
                $this->result = $response;
                return $response;
            }

            if ($timeout !== null && (microtime(true) - $startTime) > $timeout) {
                throw new DriverException('REST status check timeout');
            }

            sleep(2); // Poll every 2 seconds
        }
    }

    public function cancel(): void
    {
        // REST API doesn't support cancellation
        // The session will eventually timeout on the server
    }

    public function getId(): string
    {
        return $this->sessionId;
    }

    /**
     * Check the status of the async operation.
     */
    private function checkStatus(): Response
    {
        $url = $this->baseUrl . '/voxsigma?method=status&session=' . urlencode($this->sessionId);

        $curl = curl_init();

        $headers = [];
        $curlOptions = [
            \CURLOPT_URL => $url,
            \CURLOPT_RETURNTRANSFER => true,
            \CURLOPT_SSL_VERIFYPEER => false,
            \CURLOPT_TIMEOUT => 30,
        ];

        $this->credential->applyTo($headers, $curlOptions);

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

        // In progress or in queue
        if ($errorCode === 320 || $errorCode === 329) {
            return Response::failure(
                error: 'In progress',
                errorCode: $errorCode,
                httpStatus: $httpStatus,
                xml: $response,
            );
        }

        return Response::failure(
            error: $this->extractErrorMessage($response) ?: 'Request failed',
            errorCode: $errorCode,
            httpStatus: $httpStatus,
            xml: $response,
        );
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
