<?php

namespace NeoCore\System\Core;

/**
 * Controller - Base controller class
 * 
 * Controllers must remain thin.
 * Business logic belongs in Services.
 */
abstract class Controller
{
    /**
     * Validate input data
     * 
     * Simple validation - no magic rules
     */
    protected function validate(array $data, array $rules): array
    {
        $errors = [];

        foreach ($rules as $field => $rule) {
            $ruleList = explode('|', $rule);
            
            foreach ($ruleList as $r) {
                if ($r === 'required' && empty($data[$field])) {
                    $errors[$field][] = "$field is required";
                }
                
                if (strpos($r, 'min:') === 0 && isset($data[$field])) {
                    $min = (int)substr($r, 4);
                    if (strlen($data[$field]) < $min) {
                        $errors[$field][] = "$field must be at least $min characters";
                    }
                }
                
                if (strpos($r, 'max:') === 0 && isset($data[$field])) {
                    $max = (int)substr($r, 4);
                    if (strlen($data[$field]) > $max) {
                        $errors[$field][] = "$field must not exceed $max characters";
                    }
                }
                
                if ($r === 'email' && isset($data[$field])) {
                    if (!filter_var($data[$field], FILTER_VALIDATE_EMAIL)) {
                        $errors[$field][] = "$field must be a valid email";
                    }
                }
                
                if ($r === 'numeric' && isset($data[$field])) {
                    if (!is_numeric($data[$field])) {
                        $errors[$field][] = "$field must be numeric";
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * Return JSON response
     */
    protected function respondJson(Response $response, array $data, int $statusCode = 200): Response
    {
        return $response->json($data, $statusCode);
    }

    /**
     * Return success response
     */
    protected function respondSuccess(Response $response, $data = null, string $message = 'Success'): Response
    {
        return $response->json([
            'success' => true,
            'message' => $message,
            'data' => $data
        ], 200);
    }

    /**
     * Return error response
     */
    protected function respondError(Response $response, string $message, int $statusCode = 400, ?array $errors = null): Response
    {
        $body = [
            'success' => false,
            'message' => $message
        ];

        if ($errors !== null) {
            $body['errors'] = $errors;
        }

        return $response->json($body, $statusCode);
    }

    /**
     * Return validation error response
     */
    protected function respondValidationError(Response $response, array $errors): Response
    {
        return $this->respondError($response, 'Validation failed', 422, $errors);
    }

    /**
     * Return not found response
     */
    protected function respondNotFound(Response $response, string $message = 'Resource not found'): Response
    {
        return $this->respondError($response, $message, 404);
    }

    /**
     * Return unauthorized response
     */
    protected function respondUnauthorized(Response $response, string $message = 'Unauthorized'): Response
    {
        return $this->respondError($response, $message, 401);
    }

    /**
     * Return forbidden response
     */
    protected function respondForbidden(Response $response, string $message = 'Forbidden'): Response
    {
        return $this->respondError($response, $message, 403);
    }

    /**
     * Render view with Latte template
     */
    protected function view(Response $response, string $template, array $data = []): Response
    {
        try {
            $html = ViewService::render($template, $data);
            return $response->html($html);
        } catch (\Exception $e) {
            return $this->respondError($response, 'Template error: ' . $e->getMessage(), 500);
        }
    }

    /**
     * Check if view exists
     */
    protected function viewExists(string $template): bool
    {
        return ViewService::exists($template);
    }
}
