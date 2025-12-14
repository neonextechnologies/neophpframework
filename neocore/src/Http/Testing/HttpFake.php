<?php

declare(strict_types=1);

namespace NeoCore\Http\Testing;

use NeoCore\Http\HttpResponse;

/**
 * HTTP Fake
 * 
 * Fake HTTP responses for testing
 */
class HttpFake
{
    protected array $responses = [];
    protected array $recorded = [];
    protected bool $preventStrayRequests = false;

    /**
     * Create a new fake
     */
    public static function create(): static
    {
        return new static();
    }

    /**
     * Register a fake response
     */
    public function fake(string|array $url, int|HttpResponse|callable $response = 200): static
    {
        $urls = is_array($url) ? $url : [$url];

        foreach ($urls as $url) {
            $this->responses[$url] = $response;
        }

        return $this;
    }

    /**
     * Register multiple fake responses in sequence
     */
    public function sequence(string $url, array $responses): static
    {
        $this->responses[$url] = $responses;
        return $this;
    }

    /**
     * Prevent stray requests
     */
    public function preventStrayRequests(): static
    {
        $this->preventStrayRequests = true;
        return $this;
    }

    /**
     * Get a fake response
     */
    public function response(string $method, string $url, array $options = []): HttpResponse
    {
        $this->recorded[] = compact('method', 'url', 'options');

        $response = $this->findResponse($url);

        if ($response === null) {
            if ($this->preventStrayRequests) {
                throw new \RuntimeException("Unexpected request: {$method} {$url}");
            }

            return new HttpResponse(404, [], '');
        }

        if (is_callable($response)) {
            $response = $response($method, $url, $options);
        }

        if (is_array($response)) {
            $current = array_shift($this->responses[$url]);
            $response = $current;

            if (is_callable($response)) {
                $response = $response($method, $url, $options);
            }
        }

        if (is_int($response)) {
            return new HttpResponse($response, [], '');
        }

        return $response;
    }

    /**
     * Find response for URL
     */
    protected function findResponse(string $url): mixed
    {
        // Exact match
        if (isset($this->responses[$url])) {
            return $this->responses[$url];
        }

        // Pattern match
        foreach ($this->responses as $pattern => $response) {
            if ($this->matchesPattern($pattern, $url)) {
                return $response;
            }
        }

        return null;
    }

    /**
     * Check if URL matches pattern
     */
    protected function matchesPattern(string $pattern, string $url): bool
    {
        $pattern = preg_quote($pattern, '#');
        $pattern = str_replace('\*', '.*', $pattern);

        return preg_match('#^' . $pattern . '$#', $url) === 1;
    }

    /**
     * Assert a request was sent
     */
    public function assertSent(string|callable $url, ?callable $callback = null): void
    {
        $count = 0;

        foreach ($this->recorded as $request) {
            if (is_callable($url)) {
                if ($url($request['method'], $request['url'], $request['options'])) {
                    $count++;
                }
            } elseif ($this->matchesPattern($url, $request['url'])) {
                if ($callback === null || $callback($request['method'], $request['url'], $request['options'])) {
                    $count++;
                }
            }
        }

        if ($count === 0) {
            throw new \AssertionError("Expected request was not sent: {$url}");
        }
    }

    /**
     * Assert a request was not sent
     */
    public function assertNotSent(string|callable $url): void
    {
        foreach ($this->recorded as $request) {
            if (is_callable($url)) {
                if ($url($request['method'], $request['url'], $request['options'])) {
                    throw new \AssertionError("Unexpected request was sent");
                }
            } elseif ($this->matchesPattern($url, $request['url'])) {
                throw new \AssertionError("Unexpected request was sent: {$url}");
            }
        }
    }

    /**
     * Assert request count
     */
    public function assertSentCount(int $count): void
    {
        $actual = count($this->recorded);

        if ($actual !== $count) {
            throw new \AssertionError("Expected {$count} requests to be sent, but {$actual} were sent.");
        }
    }

    /**
     * Assert no requests were sent
     */
    public function assertNothingSent(): void
    {
        $this->assertSentCount(0);
    }

    /**
     * Get recorded requests
     */
    public function recorded(): array
    {
        return $this->recorded;
    }

    /**
     * Clear recorded requests
     */
    public function clear(): void
    {
        $this->recorded = [];
    }
}
