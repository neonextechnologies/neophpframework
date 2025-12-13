<?php

// Application routes
// Routes are loaded explicitly, no auto-discovery

return function($router) {
    // Home routes
    $router->get('/', 'App\\Http\\Controllers\\HomeController@index');
    $router->get('/health', 'App\\Http\\Controllers\\HomeController@health');
    $router->get('/welcome', 'App\\Http\\Controllers\\HomeController@welcome');

    // Users - HTML views
    $router->get('/users', 'App\\Http\\Controllers\\UserORMController@index');

    // API v1 - RESTful endpoints
    $router->prefix('/api/v1')->group(function($router) {
        // Users API
        $router->get('/users', 'App\\Http\\Controllers\\UserORMController@apiIndex');
        $router->post('/users', 'App\\Http\\Controllers\\UserORMController@store');
        $router->get('/users/{id}', 'App\\Http\\Controllers\\UserORMController@show');
        $router->put('/users/{id}', 'App\\Http\\Controllers\\UserORMController@update');
        $router->delete('/users/{id}', 'App\\Http\\Controllers\\UserORMController@delete');
        $router->get('/users/search', 'App\\Http\\Controllers\\UserORMController@search');

        // Products API
        $router->get('/products', 'App\\Http\\Controllers\\ProductORMController@apiIndex');
        $router->post('/products', 'App\\Http\\Controllers\\ProductORMController@store');
        $router->get('/products/{slug}', 'App\\Http\\Controllers\\ProductORMController@show');
        $router->patch('/products/{id}/stock', 'App\\Http\\Controllers\\ProductORMController@updateStock');
    });

    // Products - HTML views
    $router->get('/products', 'App\\Http\\Controllers\\ProductORMController@index');
};
