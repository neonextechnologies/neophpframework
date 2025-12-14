<?php

declare(strict_types=1);

use NeoCore\Http\HttpClient;
use NeoCore\Http\PendingRequest;

if (!function_exists('http')) {
    /**
     * Create a new HTTP client
     */
    function http(?string $baseUrl = null): PendingRequest
    {
        $client = new HttpClient($baseUrl);
        return new PendingRequest($client);
    }
}

if (!function_exists('http_get')) {
    /**
     * Make a GET request
     */
    function http_get(string $url, array $query = []): \NeoCore\Http\HttpResponse
    {
        return http()->get($url, $query);
    }
}

if (!function_exists('http_post')) {
    /**
     * Make a POST request
     */
    function http_post(string $url, mixed $data = []): \NeoCore\Http\HttpResponse
    {
        return http()->post($url, $data);
    }
}

if (!function_exists('http_put')) {
    /**
     * Make a PUT request
     */
    function http_put(string $url, mixed $data = []): \NeoCore\Http\HttpResponse
    {
        return http()->put($url, $data);
    }
}

if (!function_exists('http_patch')) {
    /**
     * Make a PATCH request
     */
    function http_patch(string $url, mixed $data = []): \NeoCore\Http\HttpResponse
    {
        return http()->patch($url, $data);
    }
}

if (!function_exists('http_delete')) {
    /**
     * Make a DELETE request
     */
    function http_delete(string $url): \NeoCore\Http\HttpResponse
    {
        return http()->delete($url);
    }
}
