<?php

declare(strict_types=1);

namespace NeoCore\Http;

use NeoCore\Http\Exceptions\HttpException;

/**
 * HTTP Client
 * 
 * Simple HTTP client for making requests
 */
class HttpClient
{
    protected array $options = [];
    protected array $headers = [];
    protected int $timeout = 30;
    protected ?string $baseUrl = null;

    public function __construct(?string $baseUrl = null, array $options = [])
    {
        $this->baseUrl = $baseUrl;
        $this->options = $options;
    }

    /**
     * Create a new HTTP client
     */
    public static function make(?string $baseUrl = null): static
    {
        return new static($baseUrl);
    }

    /**
     * Make a GET request
     */
    public function get(string $url, array $query = [], array $headers = []): HttpResponse
    {
        return $this->request('GET', $url, [
            'query' => $query,
            'headers' => $headers,
        ]);
    }

    /**
     * Make a POST request
     */
    public function post(string $url, mixed $data = [], array $headers = []): HttpResponse
    {
        return $this->request('POST', $url, [
            'body' => $data,
            'headers' => $headers,
        ]);
    }

    /**
     * Make a PUT request
     */
    public function put(string $url, mixed $data = [], array $headers = []): HttpResponse
    {
        return $this->request('PUT', $url, [
            'body' => $data,
            'headers' => $headers,
        ]);
    }

    /**
     * Make a PATCH request
     */
    public function patch(string $url, mixed $data = [], array $headers = []): HttpResponse
    {
        return $this->request('PATCH', $url, [
            'body' => $data,
            'headers' => $headers,
        ]);
    }

    /**
     * Make a DELETE request
     */
    public function delete(string $url, array $headers = []): HttpResponse
    {
        return $this->request('DELETE', $url, [
            'headers' => $headers,
        ]);
    }

    /**
     * Make a HEAD request
     */
    public function head(string $url, array $query = [], array $headers = []): HttpResponse
    {
        return $this->request('HEAD', $url, [
            'query' => $query,
            'headers' => $headers,
        ]);
    }

    /**
     * Make a request
     */
    public function request(string $method, string $url, array $options = []): HttpResponse
    {
        $url = $this->buildUrl($url, $options['query'] ?? []);
        $headers = array_merge($this->headers, $options['headers'] ?? []);

        $ch = curl_init();

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CUSTOMREQUEST => strtoupper($method),
            CURLOPT_HTTPHEADER => $this->formatHeaders($headers),
        ]);

        // Set body
        if (isset($options['body']) && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            $body = $options['body'];

            if (is_array($body)) {
                if (isset($headers['Content-Type']) && str_contains($headers['Content-Type'], 'application/json')) {
                    $body = json_encode($body);
                } else {
                    $body = http_build_query($body);
                }
            }

            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        // Additional options
        foreach ($this->options as $option => $value) {
            curl_setopt($ch, $option, $value);
        }

        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new HttpException("HTTP request failed: {$error}");
        }

        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);

        $responseHeaders = substr($response, 0, $headerSize);
        $responseBody = substr($response, $headerSize);

        return new HttpResponse($statusCode, $this->parseHeaders($responseHeaders), $responseBody);
    }

    /**
     * Set timeout
     */
    public function timeout(int $seconds): static
    {
        $this->timeout = $seconds;
        return $this;
    }

    /**
     * Set headers
     */
    public function withHeaders(array $headers): static
    {
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }

    /**
     * Set bearer token
     */
    public function withToken(string $token, string $type = 'Bearer'): static
    {
        return $this->withHeaders([
            'Authorization' => "{$type} {$token}",
        ]);
    }

    /**
     * Set basic auth
     */
    public function withBasicAuth(string $username, string $password): static
    {
        return $this->withHeaders([
            'Authorization' => 'Basic ' . base64_encode("{$username}:{$password}"),
        ]);
    }

    /**
     * Send as JSON
     */
    public function asJson(): static
    {
        return $this->withHeaders([
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ]);
    }

    /**
     * Send as form data
     */
    public function asForm(): static
    {
        return $this->withHeaders([
            'Content-Type' => 'application/x-www-form-urlencoded',
        ]);
    }

    /**
     * Send as multipart
     */
    public function asMultipart(): static
    {
        return $this->withHeaders([
            'Content-Type' => 'multipart/form-data',
        ]);
    }

    /**
     * Accept JSON response
     */
    public function acceptJson(): static
    {
        return $this->withHeaders([
            'Accept' => 'application/json',
        ]);
    }

    /**
     * Set base URL
     */
    public function baseUrl(string $url): static
    {
        $this->baseUrl = rtrim($url, '/');
        return $this;
    }

    /**
     * Set curl options
     */
    public function withOptions(array $options): static
    {
        $this->options = array_merge($this->options, $options);
        return $this;
    }

    /**
     * Build URL
     */
    protected function buildUrl(string $url, array $query = []): string
    {
        if ($this->baseUrl && !str_starts_with($url, 'http')) {
            $url = $this->baseUrl . '/' . ltrim($url, '/');
        }

        if (!empty($query)) {
            $separator = str_contains($url, '?') ? '&' : '?';
            $url .= $separator . http_build_query($query);
        }

        return $url;
    }

    /**
     * Format headers for curl
     */
    protected function formatHeaders(array $headers): array
    {
        $formatted = [];

        foreach ($headers as $key => $value) {
            $formatted[] = "{$key}: {$value}";
        }

        return $formatted;
    }

    /**
     * Parse headers from response
     */
    protected function parseHeaders(string $headerString): array
    {
        $headers = [];
        $lines = explode("\r\n", $headerString);

        foreach ($lines as $line) {
            if (str_contains($line, ':')) {
                [$key, $value] = explode(':', $line, 2);
                $headers[trim($key)] = trim($value);
            }
        }

        return $headers;
    }
}
