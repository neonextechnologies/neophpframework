<?php

declare(strict_types=1);

namespace NeoCore\Auth\Middleware;

use NeoCore\Http\Request;
use NeoCore\Http\Response;

/**
 * Ensure Email Is Verified Middleware
 * 
 * Ensures the user has verified their email address
 */
class EnsureEmailIsVerified
{
    /**
     * Handle an incoming request
     */
    public function handle(Request $request, callable $next): Response
    {
        $user = $request->user();

        if (!$user || 
            !method_exists($user, 'hasVerifiedEmail') || 
            !$user->hasVerifiedEmail()) {
            
            return $request->expectsJson()
                ? response()->json(['message' => 'Your email address is not verified.'], 403)
                : redirect()->route('verification.notice');
        }

        return $next($request);
    }
}
