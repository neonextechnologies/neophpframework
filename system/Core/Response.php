<?php

namespace NeoCore\System\Core;

/**
 * Response - HTTP Response wrapper
 * 
 * No magic. Explicit response building.
 */
class Response
{
    private int $statusCode = 200;
    private array $headers = [];
    private $body = null;
    private bool $sent = false;

    /**
     * Set HTTP status code
     */
    public function setStatusCode(int $code): self
    {
        $this->statusCode = $code;
        return $this;
    }

    /**
     * Get status code
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Set header
     */
    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;
        return $this;
    }

    /**
     * Set multiple headers
     */
    public function setHeaders(array $headers): self
    {
        foreach ($headers as $name => $value) {
            $this->setHeader($name, $value);
        }
        return $this;
    }

    /**
     * Set response body
     */
    public function setBody($body): self
    {
        $this->body = $body;
        return $this;
    }

    /**
     * Send JSON response
     */
    public function json(array $data, int $statusCode = 200): self
    {
        $this->setStatusCode($statusCode);
        $this->setHeader('Content-Type', 'application/json');
        $this->setBody($data);
        return $this;
    }

    /**
     * Send HTML response
     */
    public function html(string $html, int $statusCode = 200): self
    {
        $this->setStatusCode($statusCode);
        $this->setHeader('Content-Type', 'text/html');
        $this->setBody($html);
        return $this;
    }

    /**
     * Send plain text response
     */
    public function text(string $text, int $statusCode = 200): self
    {
        $this->setStatusCode($statusCode);
        $this->setHeader('Content-Type', 'text/plain');
        $this->setBody($text);
        return $this;
    }

    /**
     * Redirect to URL
     */
    public function redirect(string $url, int $statusCode = 302): self
    {
        $this->setStatusCode($statusCode);
        $this->setHeader('Location', $url);
        return $this;
    }

    /**
     * Send file download
     */
    public function download(string $filePath, ?string $filename = null): self
    {
        if (!file_exists($filePath)) {
            $this->setStatusCode(404);
            $this->setBody(['error' => 'File not found']);
            return $this;
        }

        $filename = $filename ?? basename($filePath);
        
        $this->setHeader('Content-Type', 'application/octet-stream');
        $this->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
        $this->setHeader('Content-Length', (string)filesize($filePath));
        $this->setBody(file_get_contents($filePath));
        
        return $this;
    }

    /**
     * Send response to client
     */
    public function send(): void
    {
        if ($this->sent) {
            return;
        }

        // Send status code
        http_response_code($this->statusCode);

        // Send headers
        foreach ($this->headers as $name => $value) {
            header($name . ': ' . $value);
        }

        // Send body
        if ($this->body !== null) {
            if (is_array($this->body)) {
                echo json_encode($this->body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } else {
                echo $this->body;
            }
        }

        $this->sent = true;
    }

    /**
     * Check if response has been sent
     */
    public function isSent(): bool
    {
        return $this->sent;
    }

    /**
     * Get response body
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Get headers
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }
}
