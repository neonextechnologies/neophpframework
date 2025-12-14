<?php

declare(strict_types=1);

namespace NeoCore\Security\RateLimit;

use NeoCore\Http\Request;
use NeoCore\Http\Response;

/**
 * Throttle Middleware
 * 
 * Rate limits requests to prevent abuse
 */
class ThrottleRequests
{
    protected RateLimiter $limiter;

    public function __construct(RateLimiter $limiter)
    {
        $this->limiter = $limiter;
    }

    /**
     * Handle an incoming request
     */
    public function handle(Request $request, callable $next, int $maxAttempts = 60, int $decayMinutes = 1): Response
    {
        $key = $this->resolveRequestSignature($request);

        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            return $this->buildTooManyAttemptsResponse($key, $maxAttempts);
        }

        $this->limiter->hit($key, $decayMinutes);

        $response = $next($request);

        return $this->addHeaders(
            $response,
            $maxAttempts,
            $this->calculateRemainingAttempts($key, $maxAttempts)
        );
    }

    /**
     * Resolve the request signature
     */
    protected function resolveRequestSignature(Request $request): string
    {
        $user = $request->user();

        if ($user) {
            return 'throttle:' . $user->getAuthIdentifier();
        }

        return 'throttle:' . $request->ip();
    }

    /**
     * Create a 'too many attempts' response
     */
    protected function buildTooManyAttemptsResponse(string $key, int $maxAttempts): Response
    {
        $retryAfter = $this->limiter->availableIn($key);

        if ($this->isJsonRequest()) {
            return response()->json([
                'error' => 'Too Many Requests',
                'message' => 'Too many attempts. Please try again in ' . $retryAfter . ' seconds.',
                'retry_after' => $retryAfter
            ], 429)->header('Retry-After', (string) $retryAfter);
        }

        return response('Too Many Requests', 429)
            ->header('Retry-After', (string) $retryAfter);
    }

    /**
     * Add rate limit headers to response
     */
    protected function addHeaders(Response $response, int $maxAttempts, int $remainingAttempts): Response
    {
        return $response
            ->header('X-RateLimit-Limit', (string) $maxAttempts)
            ->header('X-RateLimit-Remaining', (string) max(0, $remainingAttempts));
    }

    /**
     * Calculate the number of remaining attempts
     */
    protected function calculateRemainingAttempts(string $key, int $maxAttempts): int
    {
        return $this->limiter->retriesLeft($key, $maxAttempts);
    }

    /**
     * Determine if the request is expecting JSON
     */
    protected function isJsonRequest(): bool
    {
        return isset($_SERVER['HTTP_ACCEPT']) && 
               str_contains($_SERVER['HTTP_ACCEPT'], 'application/json');
    }
}
