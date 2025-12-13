<?php

/**
 * Example Rate Limit Middleware
 * 
 * Prevents API abuse by limiting requests per IP
 */

namespace App\Http\Middleware;

use NeoCore\System\Core\Request;
use NeoCore\System\Core\Response;

class RateLimitMiddleware
{
    private int $maxAttempts;
    private int $decayMinutes;
    private string $storePath;

    public function __construct(int $maxAttempts = 60, int $decayMinutes = 1)
    {
        $this->maxAttempts = $maxAttempts;
        $this->decayMinutes = $decayMinutes;
        $this->storePath = STORAGE_PATH . '/cache/rate_limits';

        if (!is_dir($this->storePath)) {
            mkdir($this->storePath, 0755, true);
        }
    }

    public function handle(Request $request, Response $response, callable $next): Response
    {
        $key = $this->resolveRequestSignature($request);
        $attempts = $this->getAttempts($key);

        if ($attempts >= $this->maxAttempts) {
            return $response
                ->setHeader('X-RateLimit-Limit', (string)$this->maxAttempts)
                ->setHeader('X-RateLimit-Remaining', '0')
                ->setHeader('Retry-After', (string)($this->decayMinutes * 60))
                ->json(['error' => 'Too Many Requests'], 429);
        }

        // Increment attempts
        $this->incrementAttempts($key);

        $response->setHeader('X-RateLimit-Limit', (string)$this->maxAttempts);
        $response->setHeader('X-RateLimit-Remaining', (string)($this->maxAttempts - $attempts - 1));

        return $next($request, $response);
    }

    private function resolveRequestSignature(Request $request): string
    {
        $ip = $request->ip();
        $uri = $request->uri();
        return sha1($ip . '|' . $uri);
    }

    private function getAttempts(string $key): int
    {
        $file = $this->storePath . '/' . $key;

        if (!file_exists($file)) {
            return 0;
        }

        $data = json_decode(file_get_contents($file), true);
        $expiresAt = $data['expires_at'] ?? 0;

        if (time() > $expiresAt) {
            @unlink($file);
            return 0;
        }

        return $data['attempts'] ?? 0;
    }

    private function incrementAttempts(string $key): void
    {
        $file = $this->storePath . '/' . $key;
        $attempts = $this->getAttempts($key);

        $data = [
            'attempts' => $attempts + 1,
            'expires_at' => time() + ($this->decayMinutes * 60)
        ];

        file_put_contents($file, json_encode($data));
    }
}
