<?php

declare(strict_types=1);

use NeoCore\Security\Xss\XssFilter;

if (!function_exists('e')) {
    /**
     * Escape HTML entities in a string
     */
    function e(string $value): string
    {
        return app(XssFilter::class)->escape($value);
    }
}

if (!function_exists('clean')) {
    /**
     * Clean input to prevent XSS
     */
    function clean($value, bool $allowHtml = false)
    {
        return app(XssFilter::class)->clean($value, $allowHtml);
    }
}

if (!function_exists('clean_html')) {
    /**
     * Clean HTML content
     */
    function clean_html(string $html): string
    {
        return app(XssFilter::class)->cleanHtml($html);
    }
}

if (!function_exists('js_escape')) {
    /**
     * Escape for JavaScript context
     */
    function js_escape(string $value): string
    {
        return app(XssFilter::class)->js($value);
    }
}
