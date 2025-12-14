<?php

declare(strict_types=1);

namespace NeoCore\Auth\Middleware;

use NeoCore\Http\Request;
use NeoCore\Http\Response;
use NeoCore\Auth\AuthManager;

/**
 * Authenticate with Basic Auth Middleware
 * 
 * Provides HTTP Basic Authentication
 */
class AuthenticateWithBasicAuth
{
    protected AuthManager $auth;

    public function __construct(AuthManager $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request
     */
    public function handle(Request $request, callable $next, ?string $guard = null, ?string $field = null): Response
    {
        if ($this->auth->guard($guard)->check()) {
            return $next($request);
        }

        return $this->attemptBasicAuth($request, $next, $guard, $field);
    }

    /**
     * Attempt to authenticate using HTTP Basic Auth
     */
    protected function attemptBasicAuth(Request $request, callable $next, ?string $guard, ?string $field): Response
    {
        $credentials = $this->getBasicCredentials($request, $field);

        if (empty($credentials)) {
            return $this->failedBasicResponse();
        }

        if ($this->auth->guard($guard)->attempt($credentials)) {
            return $next($request);
        }

        return $this->failedBasicResponse();
    }

    /**
     * Get the credentials from the basic auth header
     */
    protected function getBasicCredentials(Request $request, ?string $field): array
    {
        $username = $request->getUser();
        $password = $request->getPassword();

        if (empty($username) || empty($password)) {
            return [];
        }

        $field = $field ?? 'email';

        return [
            $field => $username,
            'password' => $password,
        ];
    }

    /**
     * Get the failed basic auth response
     */
    protected function failedBasicResponse(): Response
    {
        return response('Invalid credentials', 401)
            ->header('WWW-Authenticate', 'Basic realm="Protected"');
    }
}
