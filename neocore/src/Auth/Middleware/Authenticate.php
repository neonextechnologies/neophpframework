<?php

declare(strict_types=1);

namespace NeoCore\Auth\Middleware;

use NeoCore\Http\Request;
use NeoCore\Http\Response;
use NeoCore\Auth\AuthManager;

/**
 * Authenticate Middleware
 * 
 * Ensures the user is authenticated before accessing protected routes
 */
class Authenticate
{
    protected AuthManager $auth;
    protected array $guards;

    public function __construct(AuthManager $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request
     */
    public function handle(Request $request, callable $next, ...$guards): Response
    {
        $this->guards = empty($guards) ? [null] : $guards;

        if ($this->authenticate($request)) {
            return $next($request);
        }

        return $this->redirectTo($request);
    }

    /**
     * Determine if the user is logged in to any of the given guards
     */
    protected function authenticate(Request $request): bool
    {
        foreach ($this->guards as $guard) {
            if ($this->auth->guard($guard)->check()) {
                return $this->auth->shouldUse($guard);
            }
        }

        return false;
    }

    /**
     * Get the redirect response
     */
    protected function redirectTo(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Unauthenticated',
                'message' => 'You must be logged in to access this resource'
            ], 401);
        }

        return redirect()->guest(route('login'))
            ->with('error', 'Please login to continue');
    }
}
