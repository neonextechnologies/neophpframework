<?php

declare(strict_types=1);

namespace NeoCore\Auth\Middleware;

use NeoCore\Http\Request;
use NeoCore\Http\Response;
use NeoCore\Auth\Access\Gate;
use NeoCore\Auth\Access\AuthorizationException;

/**
 * Authorize Middleware
 * 
 * Authorizes requests based on abilities
 */
class Authorize
{
    protected Gate $gate;

    public function __construct(Gate $gate)
    {
        $this->gate = $gate;
    }

    /**
     * Handle an incoming request
     */
    public function handle(Request $request, callable $next, string $ability, ...$arguments): Response
    {
        try {
            $this->gate->authorize($ability, $arguments);
        } catch (AuthorizationException $e) {
            return $this->handleUnauthorized($request, $e);
        }

        return $next($request);
    }

    /**
     * Handle unauthorized access
     */
    protected function handleUnauthorized(Request $request, AuthorizationException $e): Response
    {
        if ($request->expectsJson()) {
            return response()->json([
                'error' => 'Forbidden',
                'message' => $e->getMessage()
            ], 403);
        }

        return redirect()->back()
            ->with('error', $e->getMessage());
    }
}
