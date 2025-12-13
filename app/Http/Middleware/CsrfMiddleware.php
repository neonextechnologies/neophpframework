<?php

/**
 * Example CSRF Middleware
 * 
 * Protects against Cross-Site Request Forgery attacks
 */

namespace App\Http\Middleware;

use NeoCore\System\Core\Request;
use NeoCore\System\Core\Response;

class CsrfMiddleware
{
    public function handle(Request $request, Response $response, callable $next): Response
    {
        // Skip CSRF check for GET, HEAD, OPTIONS
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'])) {
            return $next($request, $response);
        }

        // Get token from request
        $token = $request->input('_csrf_token') 
            ?? $request->header('X-CSRF-TOKEN');

        // Verify token
        if (!$this->verifyToken($token)) {
            return $response
                ->json(['error' => 'CSRF token mismatch'], 403);
        }

        return $next($request, $response);
    }

    private function verifyToken(?string $token): bool
    {
        if (!isset($_SESSION)) {
            session_start();
        }

        if (!$token || !isset($_SESSION['_csrf_token'])) {
            return false;
        }

        return hash_equals($_SESSION['_csrf_token'], $token);
    }

    /**
     * Generate CSRF token
     */
    public static function generateToken(): string
    {
        if (!isset($_SESSION)) {
            session_start();
        }

        if (!isset($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['_csrf_token'];
    }
}
