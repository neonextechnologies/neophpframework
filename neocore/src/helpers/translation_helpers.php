<?php

declare(strict_types=1);

use NeoCore\Translation\Translator;

if (!function_exists('trans')) {
    /**
     * Translate the given message
     */
    function trans(string $key, array $replace = [], ?string $locale = null): string
    {
        return app(Translator::class)->get($key, $replace, $locale);
    }
}

if (!function_exists('__')) {
    /**
     * Translate the given message (alias)
     */
    function __(string $key, array $replace = [], ?string $locale = null): string
    {
        return trans($key, $replace, $locale);
    }
}

if (!function_exists('trans_choice')) {
    /**
     * Translate the given message with pluralization
     */
    function trans_choice(string $key, int|float $number, array $replace = [], ?string $locale = null): string
    {
        return app(Translator::class)->choice($key, $number, $replace, $locale);
    }
}

if (!function_exists('lang')) {
    /**
     * Get the translator instance
     */
    function lang(): Translator
    {
        return app(Translator::class);
    }
}

if (!function_exists('locale')) {
    /**
     * Get or set the current locale
     */
    function locale(?string $locale = null): string
    {
        $translator = app(Translator::class);

        if ($locale !== null) {
            $translator->setLocale($locale);
        }

        return $translator->getLocale();
    }
}
