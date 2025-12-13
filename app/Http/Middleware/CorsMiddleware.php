<?php

namespace App\Http\Middleware;

use NeoCore\System\Core\Request;
use NeoCore\System\Core\Response;

/**
 * Example Middleware - CORS Headers
 */
class CorsMiddleware
{
    public function handle(Request $request, Response $response, callable $next): Response
    {
        // Add CORS headers
        $response->setHeader('Access-Control-Allow-Origin', '*');
        $response->setHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
        $response->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Tenant-ID');

        // Handle preflight request
        if ($request->getMethod() === 'OPTIONS') {
            $response->setStatusCode(200);
            return $response;
        }

        return $next($request, $response);
    }
}
