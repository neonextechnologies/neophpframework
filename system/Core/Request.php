<?php

namespace NeoCore\System\Core;

/**
 * Request - HTTP Request wrapper
 * 
 * No magic. Explicit access to request data.
 */
class Request
{
    private array $query = [];
    private array $post = [];
    private array $server = [];
    private array $headers = [];
    private array $files = [];
    private array $cookies = [];
    private array $routeParams = [];
    private ?string $body = null;
    private ?array $json = null;

    public function __construct()
    {
        $this->query = $_GET;
        $this->post = $_POST;
        $this->server = $_SERVER;
        $this->files = $_FILES;
        $this->cookies = $_COOKIE;
        $this->headers = $this->parseHeaders();
        $this->body = file_get_contents('php://input');

        // Parse JSON body if content-type is application/json
        if ($this->getHeader('Content-Type') === 'application/json') {
            $this->json = json_decode($this->body, true);
        }
    }

    /**
     * Parse headers from $_SERVER
     */
    private function parseHeaders(): array
    {
        $headers = [];
        foreach ($this->server as $key => $value) {
            if (strpos($key, 'HTTP_') === 0) {
                $headerName = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
                $headers[$headerName] = $value;
            }
        }
        return $headers;
    }

    /**
     * Get HTTP method
     */
    public function getMethod(): string
    {
        return $this->server['REQUEST_METHOD'] ?? 'GET';
    }

    /**
     * Get request URI
     */
    public function getUri(): string
    {
        return $this->server['REQUEST_URI'] ?? '/';
    }

    /**
     * Get query parameter
     */
    public function query(string $key, $default = null)
    {
        return $this->query[$key] ?? $default;
    }

    /**
     * Get all query parameters
     */
    public function allQuery(): array
    {
        return $this->query;
    }

    /**
     * Get POST parameter
     */
    public function post(string $key, $default = null)
    {
        return $this->post[$key] ?? $default;
    }

    /**
     * Get all POST parameters
     */
    public function allPost(): array
    {
        return $this->post;
    }

    /**
     * Get JSON body parameter
     */
    public function json(string $key, $default = null)
    {
        return $this->json[$key] ?? $default;
    }

    /**
     * Get all JSON body data
     */
    public function allJson(): ?array
    {
        return $this->json;
    }

    /**
     * Get input from POST or JSON
     */
    public function input(string $key, $default = null)
    {
        if ($this->json !== null) {
            return $this->json($key, $default);
        }
        return $this->post($key, $default);
    }

    /**
     * Get all input data
     */
    public function all(): array
    {
        if ($this->json !== null) {
            return $this->json;
        }
        return $this->post;
    }

    /**
     * Get header
     */
    public function getHeader(string $name): ?string
    {
        return $this->headers[$name] ?? null;
    }

    /**
     * Get all headers
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Get uploaded file
     */
    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    /**
     * Get cookie
     */
    public function cookie(string $key, $default = null)
    {
        return $this->cookies[$key] ?? $default;
    }

    /**
     * Get route parameter
     */
    public function param(string $key, $default = null)
    {
        return $this->routeParams[$key] ?? $default;
    }

    /**
     * Set route parameters (called by Router)
     */
    public function setRouteParams(array $params): void
    {
        $this->routeParams = $params;
    }

    /**
     * Get server variable
     */
    public function server(string $key, $default = null)
    {
        return $this->server[$key] ?? $default;
    }

    /**
     * Get client IP address
     */
    public function getIp(): ?string
    {
        return $this->server['REMOTE_ADDR'] ?? null;
    }

    /**
     * Get user agent
     */
    public function getUserAgent(): ?string
    {
        return $this->server['HTTP_USER_AGENT'] ?? null;
    }

    /**
     * Check if request is AJAX
     */
    public function isAjax(): bool
    {
        return $this->getHeader('X-Requested-With') === 'XMLHttpRequest';
    }

    /**
     * Check if request expects JSON
     */
    public function expectsJson(): bool
    {
        $accept = $this->getHeader('Accept') ?? '';
        return strpos($accept, 'application/json') !== false;
    }

    /**
     * Get raw request body
     */
    public function getBody(): ?string
    {
        return $this->body;
    }
}
