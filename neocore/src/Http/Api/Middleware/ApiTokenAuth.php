<?php

declare(strict_types=1);

namespace NeoCore\Http\Api\Middleware;

use NeoCore\Http\Request;
use NeoCore\Http\Response;
use NeoCore\Http\Api\ApiResponse;
use App\Entities\ApiToken;
use Closure;

/**
 * API Token Authentication Middleware
 */
class ApiTokenAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $this->getTokenFromRequest($request);

        if (!$token) {
            return ApiResponse::unauthorized('API token is required');
        }

        $apiToken = $this->findToken($token);

        if (!$apiToken) {
            return ApiResponse::unauthorized('Invalid API token');
        }

        if ($apiToken->isExpired()) {
            return ApiResponse::unauthorized('API token has expired');
        }

        // Update last used timestamp
        $apiToken->touch();
        $this->saveToken($apiToken);

        // Set token on request
        $request->attributes['api_token'] = $apiToken;

        return $next($request);
    }

    /**
     * Get token from request
     */
    protected function getTokenFromRequest(Request $request): ?string
    {
        // Check Authorization header
        $header = $request->header('Authorization');
        
        if ($header && str_starts_with($header, 'Bearer ')) {
            return substr($header, 7);
        }

        // Check query parameter
        return $request->query('api_token');
    }

    /**
     * Find token in database
     */
    protected function findToken(string $token): ?ApiToken
    {
        $orm = app('orm');
        $repository = $orm->getRepository(ApiToken::class);

        return $repository->findOne(['token' => $token]);
    }

    /**
     * Save token
     */
    protected function saveToken(ApiToken $token): void
    {
        $entityManager = app('entityManager');
        $entityManager->persist($token)->run();
    }
}
