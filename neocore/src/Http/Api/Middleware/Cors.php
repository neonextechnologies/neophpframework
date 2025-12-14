<?php

declare(strict_types=1);

namespace NeoCore\Http\Api\Middleware;

use NeoCore\Http\Request;
use NeoCore\Http\Response;
use Closure;

/**
 * CORS Middleware
 * 
 * Handle Cross-Origin Resource Sharing
 */
class Cors
{
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'allowed_origins' => ['*'],
            'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
            'allowed_headers' => ['*'],
            'exposed_headers' => [],
            'max_age' => 86400,
            'supports_credentials' => false,
        ], $config);
    }

    public function handle(Request $request, Closure $next): Response
    {
        // Handle preflight OPTIONS request
        if ($request->method() === 'OPTIONS') {
            return $this->handlePreflightRequest($request);
        }

        $response = $next($request);

        return $this->addCorsHeaders($request, $response);
    }

    /**
     * Handle preflight request
     */
    protected function handlePreflightRequest(Request $request): Response
    {
        $response = new Response('', 204);

        return $this->addCorsHeaders($request, $response);
    }

    /**
     * Add CORS headers to response
     */
    protected function addCorsHeaders(Request $request, Response $response): Response
    {
        $origin = $request->header('Origin');

        if ($this->isOriginAllowed($origin)) {
            $response->header('Access-Control-Allow-Origin', $origin ?? '*');
        }

        if ($this->config['supports_credentials']) {
            $response->header('Access-Control-Allow-Credentials', 'true');
        }

        if (!empty($this->config['exposed_headers'])) {
            $response->header('Access-Control-Expose-Headers', implode(', ', $this->config['exposed_headers']));
        }

        // For preflight requests
        if ($request->method() === 'OPTIONS') {
            $response->header('Access-Control-Allow-Methods', implode(', ', $this->config['allowed_methods']));

            $requestHeaders = $request->header('Access-Control-Request-Headers');
            if ($requestHeaders) {
                if (in_array('*', $this->config['allowed_headers'])) {
                    $response->header('Access-Control-Allow-Headers', $requestHeaders);
                } else {
                    $response->header('Access-Control-Allow-Headers', implode(', ', $this->config['allowed_headers']));
                }
            }

            $response->header('Access-Control-Max-Age', (string) $this->config['max_age']);
        }

        return $response;
    }

    /**
     * Check if origin is allowed
     */
    protected function isOriginAllowed(?string $origin): bool
    {
        if (!$origin) {
            return false;
        }

        if (in_array('*', $this->config['allowed_origins'])) {
            return true;
        }

        return in_array($origin, $this->config['allowed_origins']);
    }
}
