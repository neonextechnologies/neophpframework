<?php

declare(strict_types=1);

namespace NeoCore\Auth\Middleware;

use NeoCore\Http\Request;
use NeoCore\Http\Response;

/**
 * Require Role Middleware
 * 
 * Ensures the user has a specific role
 */
class RequireRole
{
    /**
     * Handle an incoming request
     */
    public function handle(Request $request, callable $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return $this->unauthorized($request, 'You must be logged in.');
        }

        if (!$user->hasAnyRole($roles)) {
            return $this->unauthorized($request, 'You do not have the required role.');
        }

        return $next($request);
    }

    /**
     * Return unauthorized response
     */
    protected function unauthorized(Request $request, string $message): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => $message
            ], 403);
        }

        return redirect('/')->with('error', $message);
    }
}
