<?php

namespace App\Http\Middleware;

use NeoCore\System\Core\Request;
use NeoCore\System\Core\Response;

/**
 * Example Middleware - Authentication
 */
class AuthMiddleware
{
    public function handle(Request $request, Response $response, callable $next): Response
    {
        $token = $request->getHeader('Authorization');

        if (!$token) {
            $response->setStatusCode(401);
            $response->setBody([
                'error' => 'Unauthorized',
                'message' => 'Authentication token required'
            ]);
            return $response;
        }

        // Validate token here
        // Example: check against database, JWT validation, etc.

        return $next($request, $response);
    }
}
