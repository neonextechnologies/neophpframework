<?php

declare(strict_types=1);

namespace NeoCore\Http\Api;

use NeoCore\Http\JsonResponse;

/**
 * API Response Builder
 * 
 * Standardized JSON API responses
 */
class ApiResponse
{
    protected array $data = [];
    protected array $meta = [];
    protected array $errors = [];
    protected int $statusCode = 200;
    protected array $headers = [];

    public function __construct()
    {
    }

    /**
     * Create a success response
     */
    public static function success(mixed $data = null, string $message = null, int $statusCode = 200): JsonResponse
    {
        $response = [
            'success' => true,
        ];

        if ($message !== null) {
            $response['message'] = $message;
        }

        if ($data !== null) {
            $response['data'] = $data;
        }

        return new JsonResponse($response, $statusCode);
    }

    /**
     * Create an error response
     */
    public static function error(string $message, int $statusCode = 400, array $errors = []): JsonResponse
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return new JsonResponse($response, $statusCode);
    }

    /**
     * Create a validation error response
     */
    public static function validationError(array $errors, string $message = 'Validation failed'): JsonResponse
    {
        return new JsonResponse([
            'success' => false,
            'message' => $message,
            'errors' => $errors,
        ], 422);
    }

    /**
     * Create a not found response
     */
    public static function notFound(string $message = 'Resource not found'): JsonResponse
    {
        return self::error($message, 404);
    }

    /**
     * Create an unauthorized response
     */
    public static function unauthorized(string $message = 'Unauthorized'): JsonResponse
    {
        return self::error($message, 401);
    }

    /**
     * Create a forbidden response
     */
    public static function forbidden(string $message = 'Forbidden'): JsonResponse
    {
        return self::error($message, 403);
    }

    /**
     * Create a created response
     */
    public static function created(mixed $data = null, string $message = 'Resource created successfully'): JsonResponse
    {
        return self::success($data, $message, 201);
    }

    /**
     * Create an accepted response
     */
    public static function accepted(mixed $data = null, string $message = 'Request accepted'): JsonResponse
    {
        return self::success($data, $message, 202);
    }

    /**
     * Create a no content response
     */
    public static function noContent(): JsonResponse
    {
        return new JsonResponse(null, 204);
    }

    /**
     * Create a paginated response
     */
    public static function paginated(array $items, int $total, int $perPage, int $currentPage): JsonResponse
    {
        return new JsonResponse([
            'success' => true,
            'data' => $items,
            'meta' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $currentPage,
                'last_page' => (int) ceil($total / $perPage),
                'from' => ($currentPage - 1) * $perPage + 1,
                'to' => min($currentPage * $perPage, $total),
            ],
        ]);
    }

    /**
     * Create a response with metadata
     */
    public static function withMeta(mixed $data, array $meta, int $statusCode = 200): JsonResponse
    {
        return new JsonResponse([
            'success' => true,
            'data' => $data,
            'meta' => $meta,
        ], $statusCode);
    }

    /**
     * Create a response with links
     */
    public static function withLinks(mixed $data, array $links, int $statusCode = 200): JsonResponse
    {
        return new JsonResponse([
            'success' => true,
            'data' => $data,
            'links' => $links,
        ], $statusCode);
    }

    /**
     * Create a rate limit exceeded response
     */
    public static function rateLimitExceeded(int $retryAfter = null): JsonResponse
    {
        $response = self::error('Rate limit exceeded', 429);

        if ($retryAfter !== null) {
            $response->header('Retry-After', (string) $retryAfter);
            $response->header('X-RateLimit-Reset', (string) (time() + $retryAfter));
        }

        return $response;
    }

    /**
     * Create a server error response
     */
    public static function serverError(string $message = 'Internal server error'): JsonResponse
    {
        return self::error($message, 500);
    }

    /**
     * Create a service unavailable response
     */
    public static function serviceUnavailable(string $message = 'Service unavailable'): JsonResponse
    {
        return self::error($message, 503);
    }

    /**
     * Create a conflict response
     */
    public static function conflict(string $message = 'Conflict'): JsonResponse
    {
        return self::error($message, 409);
    }

    /**
     * Create a method not allowed response
     */
    public static function methodNotAllowed(string $message = 'Method not allowed'): JsonResponse
    {
        return self::error($message, 405);
    }

    /**
     * Builder methods
     */
    public function setData(mixed $data): self
    {
        $this->data = $data;
        return $this;
    }

    public function setMeta(array $meta): self
    {
        $this->meta = $meta;
        return $this;
    }

    public function addMeta(string $key, mixed $value): self
    {
        $this->meta[$key] = $value;
        return $this;
    }

    public function setErrors(array $errors): self
    {
        $this->errors = $errors;
        return $this;
    }

    public function addError(string $field, string $message): self
    {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = [];
        }
        $this->errors[$field][] = $message;
        return $this;
    }

    public function setStatusCode(int $statusCode): self
    {
        $this->statusCode = $statusCode;
        return $this;
    }

    public function setHeader(string $key, string $value): self
    {
        $this->headers[$key] = $value;
        return $this;
    }

    public function build(): JsonResponse
    {
        $response = [
            'success' => $this->statusCode >= 200 && $this->statusCode < 300,
        ];

        if (!empty($this->data)) {
            $response['data'] = $this->data;
        }

        if (!empty($this->meta)) {
            $response['meta'] = $this->meta;
        }

        if (!empty($this->errors)) {
            $response['errors'] = $this->errors;
        }

        $jsonResponse = new JsonResponse($response, $this->statusCode);

        foreach ($this->headers as $key => $value) {
            $jsonResponse->header($key, $value);
        }

        return $jsonResponse;
    }
}
