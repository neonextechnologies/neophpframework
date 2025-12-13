<?php

/**
 * Example JSON Middleware
 * 
 * Automatically parses JSON request body
 */

namespace App\Http\Middleware;

use NeoCore\System\Core\Request;
use NeoCore\System\Core\Response;

class JsonMiddleware
{
    public function handle(Request $request, Response $response, callable $next): Response
    {
        $contentType = $request->header('Content-Type');

        if (strpos($contentType, 'application/json') !== false) {
            $body = file_get_contents('php://input');
            $data = json_decode($body, true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($data)) {
                // Merge JSON data into request
                $_POST = array_merge($_POST, $data);
            }
        }

        return $next($request, $response);
    }
}
