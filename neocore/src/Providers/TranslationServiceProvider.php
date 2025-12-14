<?php

declare(strict_types=1);

namespace NeoCore\Providers;

use NeoCore\Container\ServiceProvider;
use NeoCore\Translation\Translator;

class TranslationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->container->singleton(Translator::class, function ($container) {
            $config = $container->get('config')['translation'] ?? [];

            return new Translator(
                locale: $config['locale'] ?? 'en',
                fallbackLocale: $config['fallback_locale'] ?? 'en',
                path: $config['path'] ?? __DIR__ . '/../../resources/lang'
            );
        });
    }

    public function boot(): void
    {
        // Set locale from environment or config
        $translator = $this->container->get(Translator::class);
        
        if (isset($_SESSION['locale'])) {
            $translator->setLocale($_SESSION['locale']);
        }
    }
}
