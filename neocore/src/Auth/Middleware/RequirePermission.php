<?php

declare(strict_types=1);

namespace NeoCore\Auth\Middleware;

use NeoCore\Http\Request;
use NeoCore\Http\Response;

/**
 * Require Permission Middleware
 * 
 * Ensures the user has a specific permission
 */
class RequirePermission
{
    /**
     * Handle an incoming request
     */
    public function handle(Request $request, callable $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (!$user) {
            return $this->unauthorized($request, 'You must be logged in.');
        }

        if (!$user->hasAnyPermission($permissions)) {
            return $this->unauthorized($request, 'You do not have the required permission.');
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
