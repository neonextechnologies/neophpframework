<?php

declare(strict_types=1);

namespace NeoCore\Http\Api;

use NeoCore\Http\Request;
use NeoCore\Http\Api\ApiResponse;
use Throwable;

/**
 * API Exception Handler
 */
class ApiExceptionHandler
{
    protected bool $debug;

    public function __construct(bool $debug = false)
    {
        $this->debug = $debug;
    }

    /**
     * Render exception as JSON response
     */
    public function render(Throwable $e): \NeoCore\Http\JsonResponse
    {
        $statusCode = $this->getStatusCode($e);

        if ($this->debug) {
            return $this->renderDebugResponse($e, $statusCode);
        }

        return $this->renderProductionResponse($e, $statusCode);
    }

    /**
     * Render debug response
     */
    protected function renderDebugResponse(Throwable $e, int $statusCode): \NeoCore\Http\JsonResponse
    {
        return new \NeoCore\Http\JsonResponse([
            'success' => false,
            'message' => $e->getMessage(),
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTrace(),
        ], $statusCode);
    }

    /**
     * Render production response
     */
    protected function renderProductionResponse(Throwable $e, int $statusCode): \NeoCore\Http\JsonResponse
    {
        $message = $this->getMessageForStatusCode($statusCode, $e);

        return ApiResponse::error($message, $statusCode);
    }

    /**
     * Get status code from exception
     */
    protected function getStatusCode(Throwable $e): int
    {
        if (method_exists($e, 'getStatusCode')) {
            return $e->getStatusCode();
        }

        if ($e instanceof \InvalidArgumentException) {
            return 400;
        }

        if ($e instanceof \UnauthorizedException) {
            return 401;
        }

        if ($e instanceof \ForbiddenException) {
            return 403;
        }

        if ($e instanceof \NotFoundException) {
            return 404;
        }

        if ($e instanceof \MethodNotAllowedException) {
            return 405;
        }

        if ($e instanceof \ValidationException) {
            return 422;
        }

        return 500;
    }

    /**
     * Get message for status code
     */
    protected function getMessageForStatusCode(int $statusCode, Throwable $e): string
    {
        // For client errors, show the exception message
        if ($statusCode >= 400 && $statusCode < 500) {
            return $e->getMessage() ?: $this->getDefaultMessage($statusCode);
        }

        // For server errors, use generic message in production
        return $this->getDefaultMessage($statusCode);
    }

    /**
     * Get default message for status code
     */
    protected function getDefaultMessage(int $statusCode): string
    {
        return match ($statusCode) {
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            422 => 'Validation Failed',
            429 => 'Too Many Requests',
            500 => 'Internal Server Error',
            503 => 'Service Unavailable',
            default => 'An error occurred',
        };
    }

    /**
     * Convert validation exception to API response
     */
    public function renderValidationException(array $errors): \NeoCore\Http\JsonResponse
    {
        return ApiResponse::validationError($errors);
    }
}
