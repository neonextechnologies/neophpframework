<?php

declare(strict_types=1);

namespace NeoCore\Security\Csrf;

use NeoCore\Http\Request;
use NeoCore\Http\Response;

/**
 * CSRF Protection Middleware
 * 
 * Validates CSRF tokens on state-changing requests
 */
class VerifyCsrfToken
{
    protected CsrfTokenManager $csrf;
    protected array $except = [];

    public function __construct(CsrfTokenManager $csrf)
    {
        $this->csrf = $csrf;
    }

    /**
     * Handle an incoming request
     */
    public function handle(Request $request, callable $next): Response
    {
        if ($this->shouldSkip($request)) {
            return $next($request);
        }

        if ($this->isReading($request) || $this->tokensMatch($request)) {
            return $this->addCookieToResponse($request, $next($request));
        }

        return $this->handleTokenMismatch($request);
    }

    /**
     * Determine if the request should skip CSRF verification
     */
    protected function shouldSkip(Request $request): bool
    {
        foreach ($this->except as $pattern) {
            if ($request->is($pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Determine if the HTTP request uses a "read" verb
     */
    protected function isReading(Request $request): bool
    {
        return in_array($request->method(), ['HEAD', 'GET', 'OPTIONS']);
    }

    /**
     * Determine if the session and input CSRF tokens match
     */
    protected function tokensMatch(Request $request): bool
    {
        $token = $this->getTokenFromRequest($request);

        return !empty($token) && $this->csrf->validateToken($token);
    }

    /**
     * Get the CSRF token from the request
     */
    protected function getTokenFromRequest(Request $request): ?string
    {
        // Check POST body
        $token = $request->input('_token');

        if (empty($token)) {
            // Check headers
            $token = $request->header('X-CSRF-TOKEN');
        }

        if (empty($token)) {
            // Check X-XSRF-TOKEN header
            $token = $request->header('X-XSRF-TOKEN');
        }

        return $token;
    }

    /**
     * Add the CSRF token to the response cookies
     */
    protected function addCookieToResponse(Request $request, Response $response): Response
    {
        $token = $this->csrf->getToken();

        // Add XSRF-TOKEN cookie for JavaScript frameworks
        setcookie('XSRF-TOKEN', $token, [
            'expires' => time() + 60 * 120, // 2 hours
            'path' => '/',
            'secure' => $request->isSecure(),
            'httponly' => false, // Allow JavaScript access
            'samesite' => 'Lax'
        ]);

        return $response;
    }

    /**
     * Handle a token mismatch
     */
    protected function handleTokenMismatch(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'CSRF token mismatch',
                'message' => 'The CSRF token is invalid or has expired.'
            ], 419);
        }

        return redirect()->back()
            ->with('error', 'The page has expired due to inactivity. Please try again.');
    }

    /**
     * Set URIs that should be excluded from CSRF verification
     */
    public function except(array $uris): self
    {
        $this->except = $uris;
        return $this;
    }
}
