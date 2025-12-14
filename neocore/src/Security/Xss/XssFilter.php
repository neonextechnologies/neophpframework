<?php

declare(strict_types=1);

namespace NeoCore\Security\Xss;

/**
 * XSS Filter
 * 
 * Provides XSS protection through input sanitization
 */
class XssFilter
{
    /**
     * Clean input to prevent XSS attacks
     */
    public function clean($input, bool $allowHtml = false)
    {
        if (is_array($input)) {
            return array_map(fn($value) => $this->clean($value, $allowHtml), $input);
        }

        if (!is_string($input)) {
            return $input;
        }

        if ($allowHtml) {
            return $this->cleanHtml($input);
        }

        return $this->escape($input);
    }

    /**
     * Escape HTML special characters
     */
    public function escape(string $input): string
    {
        return htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Clean HTML while allowing safe tags
     */
    public function cleanHtml(string $input): string
    {
        // Allowed tags
        $allowedTags = '<p><br><strong><em><u><a><ul><ol><li><h1><h2><h3><h4><h5><h6><blockquote><code><pre>';

        // Strip dangerous tags
        $input = strip_tags($input, $allowedTags);

        // Remove dangerous attributes
        $input = $this->removeDangerousAttributes($input);

        // Remove javascript: protocols
        $input = preg_replace('/javascript:/i', '', $input);
        $input = preg_replace('/on\w+\s*=\s*["\']?[^"\']*["\']?/i', '', $input);

        return $input;
    }

    /**
     * Remove dangerous HTML attributes
     */
    protected function removeDangerousAttributes(string $input): string
    {
        $dangerousAttributes = [
            'onerror', 'onload', 'onclick', 'onmouseover', 'onfocus', 'onblur',
            'onchange', 'onsubmit', 'ondblclick', 'onkeydown', 'onkeypress',
            'onkeyup', 'onmousedown', 'onmouseup', 'onmousemove', 'onmouseout',
            'onscroll', 'onresize', 'ondrag', 'ondrop'
        ];

        foreach ($dangerousAttributes as $attr) {
            $input = preg_replace('/' . $attr . '\s*=\s*["\']?[^"\']*["\']?/i', '', $input);
        }

        return $input;
    }

    /**
     * Sanitize for JavaScript context
     */
    public function js(string $input): string
    {
        return json_encode($input, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP);
    }

    /**
     * Sanitize for URL context
     */
    public function url(string $input): string
    {
        return filter_var($input, FILTER_SANITIZE_URL);
    }

    /**
     * Sanitize for CSS context
     */
    public function css(string $input): string
    {
        // Remove potentially dangerous CSS
        $input = preg_replace('/javascript:/i', '', $input);
        $input = preg_replace('/expression\s*\(/i', '', $input);
        $input = preg_replace('/import\s/i', '', $input);
        return $input;
    }
}
