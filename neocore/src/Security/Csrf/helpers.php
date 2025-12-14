<?php

declare(strict_types=1);

use NeoCore\Security\Csrf\CsrfTokenManager;

if (!function_exists('csrf_token')) {
    /**
     * Get the CSRF token value
     */
    function csrf_token(): string
    {
        return app(CsrfTokenManager::class)->getToken();
    }
}

if (!function_exists('csrf_field')) {
    /**
     * Generate a hidden input field containing the CSRF token
     */
    function csrf_field(): string
    {
        return '<input type="hidden" name="_token" value="' . csrf_token() . '">';
    }
}

if (!function_exists('csrf_meta')) {
    /**
     * Generate CSRF meta tags for AJAX requests
     */
    function csrf_meta(): string
    {
        $token = csrf_token();
        return '<meta name="csrf-token" content="' . $token . '">';
    }
}
