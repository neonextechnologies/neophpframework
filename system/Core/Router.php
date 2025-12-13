<?php

namespace NeoCore\System\Core;

/**
 * Router - Explicit Table-Driven Routing
 * 
 * No auto-discovery. No magic binding.
 * Routes must be registered explicitly.
 */
class Router
{
    private array $routes = [];
    private array $middlewareGroups = [];
    private string $prefix = '';
    private array $currentMiddleware = [];

    /**
     * Add GET route
     */
    public function get(string $path, string $handler, array $middleware = []): void
    {
        $this->addRoute('GET', $path, $handler, $middleware);
    }

    /**
     * Add POST route
     */
    public function post(string $path, string $handler, array $middleware = []): void
    {
        $this->addRoute('POST', $path, $handler, $middleware);
    }

    /**
     * Add PUT route
     */
    public function put(string $path, string $handler, array $middleware = []): void
    {
        $this->addRoute('PUT', $path, $handler, $middleware);
    }

    /**
     * Add DELETE route
     */
    public function delete(string $path, string $handler, array $middleware = []): void
    {
        $this->addRoute('DELETE', $path, $handler, $middleware);
    }

    /**
     * Add PATCH route
     */
    public function patch(string $path, string $handler, array $middleware = []): void
    {
        $this->addRoute('PATCH', $path, $handler, $middleware);
    }

    /**
     * Set route prefix for grouping
     */
    public function prefix(string $prefix): self
    {
        $this->prefix = '/' . trim($prefix, '/');
        return $this;
    }

    /**
     * Set middleware for route group
     */
    public function middleware(array $middleware): self
    {
        $this->currentMiddleware = $middleware;
        return $this;
    }

    /**
     * Group routes with shared attributes
     */
    public function group(callable $callback): void
    {
        $callback($this);
        $this->prefix = '';
        $this->currentMiddleware = [];
    }

    /**
     * Register middleware group
     */
    public function registerMiddlewareGroup(string $name, array $middleware): void
    {
        $this->middlewareGroups[$name] = $middleware;
    }

    /**
     * Add route to routing table
     */
    private function addRoute(string $method, string $path, string $handler, array $middleware): void
    {
        $fullPath = $this->prefix . '/' . ltrim($path, '/');
        $fullPath = rtrim($fullPath, '/') ?: '/';

        $allMiddleware = array_merge($this->currentMiddleware, $middleware);

        $this->routes[] = [
            'method' => $method,
            'path' => $fullPath,
            'handler' => $handler,
            'middleware' => $allMiddleware,
            'pattern' => $this->compilePattern($fullPath)
        ];
    }

    /**
     * Compile path pattern to regex
     */
    private function compilePattern(string $path): string
    {
        // Convert {id} to named capture groups
        $pattern = preg_replace('/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/', '(?P<$1>[^/]+)', $path);
        return '#^' . $pattern . '$#';
    }

    /**
     * Match request to route
     */
    public function match(string $method, string $uri): ?array
    {
        $uri = parse_url($uri, PHP_URL_PATH);
        $uri = rtrim($uri, '/') ?: '/';

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (preg_match($route['pattern'], $uri, $matches)) {
                // Extract parameters
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);

                return [
                    'handler' => $route['handler'],
                    'middleware' => $route['middleware'],
                    'params' => $params
                ];
            }
        }

        return null;
    }

    /**
     * Dispatch route
     */
    public function dispatch(Request $request, Response $response): Response
    {
        $route = $this->match($request->getMethod(), $request->getUri());

        if ($route === null) {
            $response->setStatusCode(404);
            $response->setBody(['error' => 'Route not found']);
            return $response;
        }

        // Set route parameters in request
        $request->setRouteParams($route['params']);

        // Execute middleware chain
        $middlewareChain = $this->buildMiddlewareChain($route['middleware'], function($req, $res) use ($route) {
            return $this->executeHandler($route['handler'], $req, $res);
        });

        return $middlewareChain($request, $response);
    }

    /**
     * Build middleware execution chain
     */
    private function buildMiddlewareChain(array $middleware, callable $coreHandler): callable
    {
        $next = $coreHandler;

        foreach (array_reverse($middleware) as $mw) {
            $next = function($request, $response) use ($mw, $next) {
                $middlewareInstance = new $mw();
                return $middlewareInstance->handle($request, $response, $next);
            };
        }

        return $next;
    }

    /**
     * Execute controller handler
     */
    private function executeHandler(string $handler, Request $request, Response $response): Response
    {
        [$controllerClass, $method] = explode('@', $handler);

        if (!class_exists($controllerClass)) {
            $response->setStatusCode(500);
            $response->setBody(['error' => 'Controller not found: ' . $controllerClass]);
            return $response;
        }

        $controller = new $controllerClass();

        if (!method_exists($controller, $method)) {
            $response->setStatusCode(500);
            $response->setBody(['error' => 'Method not found: ' . $method]);
            return $response;
        }

        return $controller->$method($request, $response);
    }

    /**
     * Load routes from file
     */
    public function loadRoutesFromFile(string $filePath): void
    {
        if (!file_exists($filePath)) {
            return;
        }

        $router = $this;
        require $filePath;
    }

    /**
     * Get all registered routes
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }
}
