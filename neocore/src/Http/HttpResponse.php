<?php

declare(strict_types=1);

namespace NeoCore\Http;

/**
 * HTTP Response
 * 
 * Represents an HTTP response
 */
class HttpResponse
{
    public function __construct(
        protected int $status,
        protected array $headers,
        protected string $body
    ) {
    }

    /**
     * Get status code
     */
    public function status(): int
    {
        return $this->status;
    }

    /**
     * Get headers
     */
    public function headers(): array
    {
        return $this->headers;
    }

    /**
     * Get header
     */
    public function header(string $key): ?string
    {
        return $this->headers[$key] ?? null;
    }

    /**
     * Get body
     */
    public function body(): string
    {
        return $this->body;
    }

    /**
     * Get JSON decoded body
     */
    public function json(?string $key = null, mixed $default = null): mixed
    {
        $data = json_decode($this->body, true);

        if ($key === null) {
            return $data;
        }

        return data_get($data, $key, $default);
    }

    /**
     * Get response as object
     */
    public function object(): ?object
    {
        return json_decode($this->body);
    }

    /**
     * Get response as array
     */
    public function array(): ?array
    {
        return json_decode($this->body, true);
    }

    /**
     * Check if successful
     */
    public function successful(): bool
    {
        return $this->status >= 200 && $this->status < 300;
    }

    /**
     * Check if response is OK
     */
    public function ok(): bool
    {
        return $this->status === 200;
    }

    /**
     * Check if redirect
     */
    public function redirect(): bool
    {
        return $this->status >= 300 && $this->status < 400;
    }

    /**
     * Check if failed
     */
    public function failed(): bool
    {
        return $this->serverError() || $this->clientError();
    }

    /**
     * Check if client error
     */
    public function clientError(): bool
    {
        return $this->status >= 400 && $this->status < 500;
    }

    /**
     * Check if server error
     */
    public function serverError(): bool
    {
        return $this->status >= 500;
    }

    /**
     * Throw exception if failed
     */
    public function throw(): static
    {
        if ($this->failed()) {
            throw new \Exception("HTTP request failed with status {$this->status}");
        }

        return $this;
    }

    /**
     * Throw exception if client or server error
     */
    public function throwIf(bool $condition): static
    {
        if ($condition) {
            return $this->throw();
        }

        return $this;
    }

    /**
     * Convert to string
     */
    public function __toString(): string
    {
        return $this->body;
    }
}
