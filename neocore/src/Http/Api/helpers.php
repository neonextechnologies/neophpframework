<?php

declare(strict_types=1);

use NeoCore\Http\Api\ApiResponse;
use NeoCore\Http\Api\Resource;
use NeoCore\Http\Api\ResourceCollection;
use NeoCore\Http\Api\ApiPaginator;

if (!function_exists('api_response')) {
    /**
     * Create an API response
     */
    function api_response(): ApiResponse
    {
        return new ApiResponse();
    }
}

if (!function_exists('api_success')) {
    /**
     * Create a success API response
     */
    function api_success(mixed $data = null, ?string $message = null, int $status = 200): \NeoCore\Http\JsonResponse
    {
        return ApiResponse::success($data, $message, $status);
    }
}

if (!function_exists('api_error')) {
    /**
     * Create an error API response
     */
    function api_error(string $message, int $status = 400, array $errors = []): \NeoCore\Http\JsonResponse
    {
        return ApiResponse::error($message, $status, $errors);
    }
}

if (!function_exists('api_created')) {
    /**
     * Create a created API response
     */
    function api_created(mixed $data = null, string $message = 'Resource created successfully'): \NeoCore\Http\JsonResponse
    {
        return ApiResponse::created($data, $message);
    }
}

if (!function_exists('api_not_found')) {
    /**
     * Create a not found API response
     */
    function api_not_found(string $message = 'Resource not found'): \NeoCore\Http\JsonResponse
    {
        return ApiResponse::notFound($message);
    }
}

if (!function_exists('api_unauthorized')) {
    /**
     * Create an unauthorized API response
     */
    function api_unauthorized(string $message = 'Unauthorized'): \NeoCore\Http\JsonResponse
    {
        return ApiResponse::unauthorized($message);
    }
}

if (!function_exists('api_forbidden')) {
    /**
     * Create a forbidden API response
     */
    function api_forbidden(string $message = 'Forbidden'): \NeoCore\Http\JsonResponse
    {
        return ApiResponse::forbidden($message);
    }
}

if (!function_exists('api_validation_error')) {
    /**
     * Create a validation error API response
     */
    function api_validation_error(array $errors, string $message = 'Validation failed'): \NeoCore\Http\JsonResponse
    {
        return ApiResponse::validationError($errors, $message);
    }
}

if (!function_exists('api_paginated')) {
    /**
     * Create a paginated API response
     */
    function api_paginated(array $items, int $total, int $perPage, int $currentPage): \NeoCore\Http\JsonResponse
    {
        return ApiResponse::paginated($items, $total, $perPage, $currentPage);
    }
}

if (!function_exists('api_resource')) {
    /**
     * Create an API resource
     */
    function api_resource(string $resourceClass, mixed $data): Resource
    {
        return new $resourceClass($data);
    }
}

if (!function_exists('api_collection')) {
    /**
     * Create an API resource collection
     */
    function api_collection(string $resourceClass, iterable $data): ResourceCollection
    {
        return new ResourceCollection($data, $resourceClass);
    }
}

if (!function_exists('api_paginate')) {
    /**
     * Create an API paginator
     */
    function api_paginate(array $items, int $perPage = 15, int $page = 1): ApiPaginator
    {
        return ApiPaginator::make($items, $perPage, $page);
    }
}

if (!function_exists('trigger_webhook')) {
    /**
     * Trigger webhooks for an event
     */
    function trigger_webhook(string $event, array $payload): array
    {
        $webhookManager = app(\NeoCore\Webhooks\WebhookManager::class);
        return $webhookManager->trigger($event, $payload);
    }
}

if (!function_exists('api_token')) {
    /**
     * Get API token from request
     */
    function api_token(\NeoCore\Http\Request $request): ?\App\Entities\ApiToken
    {
        return $request->attributes['api_token'] ?? null;
    }
}

if (!function_exists('api_version')) {
    /**
     * Get API version from request
     */
    function api_version(\NeoCore\Http\Request $request): string
    {
        return $request->attributes['api_version'] ?? 'v1';
    }
}
