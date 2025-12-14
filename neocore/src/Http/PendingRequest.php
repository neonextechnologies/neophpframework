<?php

declare(strict_types=1);

namespace NeoCore\Http;

/**
 * Pending HTTP Request
 * 
 * Fluent interface for building HTTP requests
 */
class PendingRequest
{
    protected HttpClient $client;
    protected string $method = 'GET';
    protected string $url = '';
    protected array $options = [];

    public function __construct(HttpClient $client)
    {
        $this->client = $client;
    }

    /**
     * Set base URL
     */
    public function baseUrl(string $url): static
    {
        $this->client->baseUrl($url);
        return $this;
    }

    /**
     * Set timeout
     */
    public function timeout(int $seconds): static
    {
        $this->client->timeout($seconds);
        return $this;
    }

    /**
     * Set headers
     */
    public function withHeaders(array $headers): static
    {
        $this->client->withHeaders($headers);
        return $this;
    }

    /**
     * Set bearer token
     */
    public function withToken(string $token, string $type = 'Bearer'): static
    {
        $this->client->withToken($token, $type);
        return $this;
    }

    /**
     * Set basic auth
     */
    public function withBasicAuth(string $username, string $password): static
    {
        $this->client->withBasicAuth($username, $password);
        return $this;
    }

    /**
     * Send as JSON
     */
    public function asJson(): static
    {
        $this->client->asJson();
        return $this;
    }

    /**
     * Send as form
     */
    public function asForm(): static
    {
        $this->client->asForm();
        return $this;
    }

    /**
     * Send as multipart
     */
    public function asMultipart(): static
    {
        $this->client->asMultipart();
        return $this;
    }

    /**
     * Accept JSON response
     */
    public function acceptJson(): static
    {
        $this->client->acceptJson();
        return $this;
    }

    /**
     * Set curl options
     */
    public function withOptions(array $options): static
    {
        $this->client->withOptions($options);
        return $this;
    }

    /**
     * Set query parameters
     */
    public function withQuery(array $query): static
    {
        $this->options['query'] = array_merge($this->options['query'] ?? [], $query);
        return $this;
    }

    /**
     * Set body data
     */
    public function withBody(mixed $body): static
    {
        $this->options['body'] = $body;
        return $this;
    }

    /**
     * Make a GET request
     */
    public function get(string $url, array $query = []): HttpResponse
    {
        return $this->client->get($url, array_merge($this->options['query'] ?? [], $query));
    }

    /**
     * Make a POST request
     */
    public function post(string $url, mixed $data = []): HttpResponse
    {
        return $this->client->post($url, $data ?: $this->options['body'] ?? []);
    }

    /**
     * Make a PUT request
     */
    public function put(string $url, mixed $data = []): HttpResponse
    {
        return $this->client->put($url, $data ?: $this->options['body'] ?? []);
    }

    /**
     * Make a PATCH request
     */
    public function patch(string $url, mixed $data = []): HttpResponse
    {
        return $this->client->patch($url, $data ?: $this->options['body'] ?? []);
    }

    /**
     * Make a DELETE request
     */
    public function delete(string $url): HttpResponse
    {
        return $this->client->delete($url);
    }

    /**
     * Make a HEAD request
     */
    public function head(string $url, array $query = []): HttpResponse
    {
        return $this->client->head($url, array_merge($this->options['query'] ?? [], $query));
    }

    /**
     * Send the request
     */
    public function send(string $method, string $url, array $options = []): HttpResponse
    {
        return $this->client->request($method, $url, array_merge($this->options, $options));
    }
}
