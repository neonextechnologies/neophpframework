<?php

namespace App\Http\Controllers;

use NeoCore\System\Core\Controller;
use NeoCore\System\Core\Request;
use NeoCore\System\Core\Response;

/**
 * Home Controller using Views
 */
class HomeController extends Controller
{
    /**
     * Homepage - Render Latte template
     */
    public function index(Request $request, Response $response): Response
    {
        return $this->view($response, 'home', [
            'framework' => 'NeoCore Framework',
            'version' => '1.0.0',
            'features' => [
                'Cycle ORM - DataMapper pattern',
                'Latte Template - Fast & Secure',
                'PSR-4 Autoloading',
                'RESTful Routing',
                'CLI Commands',
                'Event System',
                'Queue System',
                'Database Migrations',
            ]
        ]);
    }

    /**
     * Welcome API
     */
    public function welcome(Request $request, Response $response): Response
    {
        return $this->respondSuccess($response, [
            'message' => 'Welcome to NeoCore PHP Framework',
            'version' => '1.0.0',
            'timestamp' => time(),
        ]);
    }

    /**
     * Health check
     */
    public function health(Request $request, Response $response): Response
    {
        return $this->respondSuccess($response, [
            'status' => 'ok',
            'framework' => 'NeoCore',
            'php_version' => PHP_VERSION,
        ]);
    }
}
