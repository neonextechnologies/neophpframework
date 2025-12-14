<?php

declare(strict_types=1);

namespace NeoCore\Middleware;

use NeoCore\Http\Request;
use NeoCore\Http\Response;
use NeoCore\Translation\Translator;
use Closure;

/**
 * Locale Middleware
 * 
 * Detects and sets the application locale
 */
class LocaleMiddleware
{
    protected Translator $translator;
    protected array $availableLocales;

    public function __construct(Translator $translator, array $availableLocales = [])
    {
        $this->translator = $translator;
        $this->availableLocales = $availableLocales;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $this->detectLocale($request);

        if ($locale && $this->isAvailableLocale($locale)) {
            $this->translator->setLocale($locale);
        }

        return $next($request);
    }

    /**
     * Detect locale from request
     */
    protected function detectLocale(Request $request): ?string
    {
        // 1. Check query parameter
        $locale = $request->query('lang') ?? $request->query('locale');
        if ($locale && $this->isAvailableLocale($locale)) {
            return $locale;
        }

        // 2. Check session
        if (isset($_SESSION['locale'])) {
            $locale = $_SESSION['locale'];
            if ($this->isAvailableLocale($locale)) {
                return $locale;
            }
        }

        // 3. Check cookie
        if (isset($_COOKIE['locale'])) {
            $locale = $_COOKIE['locale'];
            if ($this->isAvailableLocale($locale)) {
                return $locale;
            }
        }

        // 4. Check Accept-Language header
        $acceptLanguage = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        if ($acceptLanguage) {
            $locale = $this->parseAcceptLanguage($acceptLanguage);
            if ($locale && $this->isAvailableLocale($locale)) {
                return $locale;
            }
        }

        return null;
    }

    /**
     * Parse Accept-Language header
     */
    protected function parseAcceptLanguage(string $header): ?string
    {
        $languages = [];

        foreach (explode(',', $header) as $lang) {
            $parts = explode(';', $lang);
            $locale = trim($parts[0]);

            // Extract quality factor
            $quality = 1.0;
            if (isset($parts[1]) && str_starts_with(trim($parts[1]), 'q=')) {
                $quality = (float) substr(trim($parts[1]), 2);
            }

            $languages[$locale] = $quality;
        }

        // Sort by quality (highest first)
        arsort($languages);

        foreach (array_keys($languages) as $locale) {
            // Try full locale (e.g., en-US)
            if ($this->isAvailableLocale($locale)) {
                return $locale;
            }

            // Try language code only (e.g., en)
            $languageCode = explode('-', $locale)[0];
            if ($this->isAvailableLocale($languageCode)) {
                return $languageCode;
            }
        }

        return null;
    }

    /**
     * Check if locale is available
     */
    protected function isAvailableLocale(string $locale): bool
    {
        if (empty($this->availableLocales)) {
            return true;
        }

        return in_array($locale, array_keys($this->availableLocales));
    }
}
