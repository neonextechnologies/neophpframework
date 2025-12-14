<?php

declare(strict_types=1);

namespace NeoCore\Testing;

/**
 * Test Response
 * 
 * Represents an HTTP response for testing
 */
class TestResponse
{
    protected string $content;
    protected int $statusCode;
    protected array $headers;
    protected ?array $json = null;

    public function __construct(string $content, int $statusCode, array $headers = [])
    {
        $this->content = $content;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    /**
     * Get response content
     */
    public function content(): string
    {
        return $this->content;
    }

    /**
     * Get status code
     */
    public function status(): int
    {
        return $this->statusCode;
    }

    /**
     * Get all headers
     */
    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * Get specific header
     */
    public function header(string $name): ?string
    {
        return $this->headers[$name] ?? null;
    }

    /**
     * Check if header exists
     */
    public function hasHeader(string $name): bool
    {
        return isset($this->headers[$name]);
    }

    /**
     * Get JSON data
     */
    public function json(): array
    {
        if ($this->json === null) {
            $this->json = json_decode($this->content, true) ?? [];
        }
        
        return $this->json;
    }

    /**
     * Get specific JSON value
     */
    public function jsonPath(string $path): mixed
    {
        $data = $this->json();
        $keys = explode('.', $path);
        
        foreach ($keys as $key) {
            if (!isset($data[$key])) {
                return null;
            }
            $data = $data[$key];
        }
        
        return $data;
    }

    /**
     * Check if response is successful (2xx)
     */
    public function successful(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    /**
     * Check if response is OK (200)
     */
    public function ok(): bool
    {
        return $this->statusCode === 200;
    }

    /**
     * Check if response is created (201)
     */
    public function created(): bool
    {
        return $this->statusCode === 201;
    }

    /**
     * Check if response is no content (204)
     */
    public function noContent(): bool
    {
        return $this->statusCode === 204;
    }

    /**
     * Check if response is redirect (3xx)
     */
    public function redirect(): bool
    {
        return $this->statusCode >= 300 && $this->statusCode < 400;
    }

    /**
     * Check if response is client error (4xx)
     */
    public function clientError(): bool
    {
        return $this->statusCode >= 400 && $this->statusCode < 500;
    }

    /**
     * Check if response is server error (5xx)
     */
    public function serverError(): bool
    {
        return $this->statusCode >= 500 && $this->statusCode < 600;
    }

    /**
     * Check if response is not found (404)
     */
    public function notFound(): bool
    {
        return $this->statusCode === 404;
    }

    /**
     * Check if response is unauthorized (401)
     */
    public function unauthorized(): bool
    {
        return $this->statusCode === 401;
    }

    /**
     * Check if response is forbidden (403)
     */
    public function forbidden(): bool
    {
        return $this->statusCode === 403;
    }

    /**
     * Get cookies from response
     */
    public function cookies(): array
    {
        $cookies = [];
        
        foreach ($this->headers as $name => $value) {
            if (strtolower($name) === 'set-cookie') {
                $cookieParts = explode(';', $value);
                $cookieData = explode('=', $cookieParts[0], 2);
                
                if (count($cookieData) === 2) {
                    $cookies[trim($cookieData[0])] = trim($cookieData[1]);
                }
            }
        }
        
        return $cookies;
    }

    /**
     * Get specific cookie
     */
    public function cookie(string $name): ?string
    {
        $cookies = $this->cookies();
        return $cookies[$name] ?? null;
    }

    /**
     * Check if cookie exists
     */
    public function hasCookie(string $name): bool
    {
        return isset($this->cookies()[$name]);
    }

    /**
     * Dump response content
     */
    public function dump(): self
    {
        echo "Status: {$this->statusCode}\n";
        echo "Headers:\n";
        print_r($this->headers);
        echo "Content:\n";
        echo $this->content . "\n";
        
        return $this;
    }

    /**
     * Dump and die
     */
    public function dd(): void
    {
        $this->dump();
        die();
    }
}
