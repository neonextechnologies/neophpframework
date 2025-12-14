<?php

declare(strict_types=1);

namespace NeoCore\Auth\Middleware;

use NeoCore\Http\Request;
use NeoCore\Http\Response;
use NeoCore\Auth\AuthManager;

/**
 * Redirect If Authenticated Middleware
 * 
 * Redirects authenticated users away from guest-only pages
 */
class RedirectIfAuthenticated
{
    protected AuthManager $auth;

    public function __construct(AuthManager $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request
     */
    public function handle(Request $request, callable $next, ?string $guard = null): Response
    {
        if ($this->auth->guard($guard)->check()) {
            return redirect('/dashboard');
        }

        return $next($request);
    }
}
