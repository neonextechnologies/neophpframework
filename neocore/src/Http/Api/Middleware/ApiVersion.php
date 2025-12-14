<?php

declare(strict_types=1);

namespace NeoCore\Http\Api\Middleware;

use NeoCore\Http\Request;
use NeoCore\Http\Response;
use Closure;

/**
 * API Versioning Middleware
 * 
 * Handle API versioning via header or URL
 */
class ApiVersion
{
    protected string $defaultVersion;
    protected array $supportedVersions;

    public function __construct(string $defaultVersion = 'v1', array $supportedVersions = ['v1'])
    {
        $this->defaultVersion = $defaultVersion;
        $this->supportedVersions = $supportedVersions;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $version = $this->resolveVersion($request);

        if (!in_array($version, $this->supportedVersions)) {
            return new \NeoCore\Http\JsonResponse([
                'success' => false,
                'message' => "API version '{$version}' is not supported",
                'supported_versions' => $this->supportedVersions,
            ], 400);
        }

        // Set version on request
        $request->attributes['api_version'] = $version;

        return $next($request);
    }

    /**
     * Resolve API version from request
     */
    protected function resolveVersion(Request $request): string
    {
        // 1. Check Accept header (e.g., application/vnd.api.v1+json)
        $accept = $request->header('Accept');
        if ($accept && preg_match('/vnd\.api\.(v\d+)\+json/', $accept, $matches)) {
            return $matches[1];
        }

        // 2. Check API-Version header
        $headerVersion = $request->header('API-Version');
        if ($headerVersion) {
            return $headerVersion;
        }

        // 3. Check URL prefix (e.g., /api/v1/users)
        $path = $request->path();
        if (preg_match('#^api/(v\d+)/#', $path, $matches)) {
            return $matches[1];
        }

        // 4. Check query parameter
        $queryVersion = $request->query('version');
        if ($queryVersion) {
            return $queryVersion;
        }

        // 5. Default version
        return $this->defaultVersion;
    }
}
