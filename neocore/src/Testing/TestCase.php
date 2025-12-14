<?php

declare(strict_types=1);

namespace NeoCore\Testing;

use PHPUnit\Framework\TestCase as PHPUnitTestCase;

/**
 * Base Test Case
 * 
 * Provides testing utilities for NeoCore Framework
 */
abstract class TestCase extends PHPUnitTestCase
{
    protected array $headers = [];
    protected ?string $baseUrl = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->baseUrl = $_ENV['APP_URL'] ?? 'http://localhost';
    }

    /**
     * Make GET request
     */
    public function get(string $uri, array $headers = []): TestResponse
    {
        return $this->call('GET', $uri, [], $headers);
    }

    /**
     * Make POST request
     */
    public function post(string $uri, array $data = [], array $headers = []): TestResponse
    {
        return $this->call('POST', $uri, $data, $headers);
    }

    /**
     * Make PUT request
     */
    public function put(string $uri, array $data = [], array $headers = []): TestResponse
    {
        return $this->call('PUT', $uri, $data, $headers);
    }

    /**
     * Make PATCH request
     */
    public function patch(string $uri, array $data = [], array $headers = []): TestResponse
    {
        return $this->call('PATCH', $uri, $data, $headers);
    }

    /**
     * Make DELETE request
     */
    public function delete(string $uri, array $data = [], array $headers = []): TestResponse
    {
        return $this->call('DELETE', $uri, $data, $headers);
    }

    /**
     * Make HTTP request
     */
    public function call(string $method, string $uri, array $data = [], array $headers = []): TestResponse
    {
        $url = $this->prepareUrl($uri);
        $headers = array_merge($this->headers, $headers);

        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

        if (!empty($data)) {
            if (isset($headers['Content-Type']) && $headers['Content-Type'] === 'application/json') {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
            }
        }

        if (!empty($headers)) {
            $headerArray = [];
            foreach ($headers as $key => $value) {
                $headerArray[] = "{$key}: {$value}";
            }
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headerArray);
        }

        $response = curl_exec($ch);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);

        $responseHeaders = substr($response, 0, $headerSize);
        $responseBody = substr($response, $headerSize);

        return new TestResponse($responseBody, $statusCode, $this->parseHeaders($responseHeaders));
    }

    /**
     * Prepare URL
     */
    protected function prepareUrl(string $uri): string
    {
        if (str_starts_with($uri, 'http://') || str_starts_with($uri, 'https://')) {
            return $uri;
        }

        return rtrim($this->baseUrl, '/') . '/' . ltrim($uri, '/');
    }

    /**
     * Parse response headers
     */
    protected function parseHeaders(string $headerText): array
    {
        $headers = [];
        $lines = explode("\r\n", $headerText);

        foreach ($lines as $line) {
            if (strpos($line, ':') !== false) {
                [$key, $value] = explode(':', $line, 2);
                $headers[trim($key)] = trim($value);
            }
        }

        return $headers;
    }

    /**
     * Set default headers
     */
    public function withHeaders(array $headers): self
    {
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }

    /**
     * Set bearer token
     */
    public function withToken(string $token): self
    {
        return $this->withHeaders(['Authorization' => "Bearer {$token}"]);
    }

    /**
     * Set basic auth
     */
    public function withBasicAuth(string $username, string $password): self
    {
        $credentials = base64_encode("{$username}:{$password}");
        return $this->withHeaders(['Authorization' => "Basic {$credentials}"]);
    }

    /**
     * Set JSON content type
     */
    public function asJson(): self
    {
        return $this->withHeaders(['Content-Type' => 'application/json']);
    }

    /**
     * Assert status code
     */
    public function assertStatus(TestResponse $response, int $status): void
    {
        $this->assertEquals($status, $response->status(), 
            "Expected status {$status}, got {$response->status()}");
    }

    /**
     * Assert OK status (200)
     */
    public function assertOk(TestResponse $response): void
    {
        $this->assertStatus($response, 200);
    }

    /**
     * Assert created status (201)
     */
    public function assertCreated(TestResponse $response): void
    {
        $this->assertStatus($response, 201);
    }

    /**
     * Assert no content status (204)
     */
    public function assertNoContent(TestResponse $response): void
    {
        $this->assertStatus($response, 204);
    }

    /**
     * Assert not found status (404)
     */
    public function assertNotFound(TestResponse $response): void
    {
        $this->assertStatus($response, 404);
    }

    /**
     * Assert unauthorized status (401)
     */
    public function assertUnauthorized(TestResponse $response): void
    {
        $this->assertStatus($response, 401);
    }

    /**
     * Assert forbidden status (403)
     */
    public function assertForbidden(TestResponse $response): void
    {
        $this->assertStatus($response, 403);
    }

    /**
     * Assert JSON response
     */
    public function assertJson(TestResponse $response, array $data = []): void
    {
        $responseData = $response->json();
        
        foreach ($data as $key => $value) {
            $this->assertArrayHasKey($key, $responseData, "Key '{$key}' not found in response");
            
            if (is_array($value)) {
                $this->assertEquals($value, $responseData[$key]);
            } else {
                $this->assertEquals($value, $responseData[$key], 
                    "Expected '{$value}' for key '{$key}', got '{$responseData[$key]}'");
            }
        }
    }

    /**
     * Assert JSON structure
     */
    public function assertJsonStructure(TestResponse $response, array $structure): void
    {
        $data = $response->json();
        
        foreach ($structure as $key => $value) {
            if (is_array($value)) {
                $this->assertArrayHasKey($key, $data);
                $this->assertIsArray($data[$key]);
                
                if (isset($data[$key][0])) {
                    // Array of objects
                    foreach ($data[$key] as $item) {
                        foreach ($value as $subKey) {
                            $this->assertArrayHasKey($subKey, $item);
                        }
                    }
                } else {
                    // Nested object
                    foreach ($value as $subKey) {
                        $this->assertArrayHasKey($subKey, $data[$key]);
                    }
                }
            } else {
                $this->assertArrayHasKey($value, $data);
            }
        }
    }

    /**
     * Assert header exists
     */
    public function assertHeader(TestResponse $response, string $header, ?string $value = null): void
    {
        $this->assertTrue($response->hasHeader($header), "Header '{$header}' not found");
        
        if ($value !== null) {
            $this->assertEquals($value, $response->header($header));
        }
    }

    /**
     * Assert cookie exists
     */
    public function assertCookie(TestResponse $response, string $name): void
    {
        $this->assertTrue($response->hasCookie($name), "Cookie '{$name}' not found");
    }
}
