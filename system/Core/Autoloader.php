<?php

namespace NeoCore\System\Core;

/**
 * Autoloader - PSR-4 compatible autoloader
 * 
 * No Composer dependency. Simple class loading.
 */
class Autoloader
{
    private static array $prefixes = [];

    /**
     * Register autoloader
     */
    public static function register(): void
    {
        spl_autoload_register([self::class, 'load']);
        
        // Register framework namespaces
        self::addNamespace('NeoCore\\System', __DIR__ . '/..');
        self::addNamespace('App', __DIR__ . '/../../../app');
        self::addNamespace('Modules', __DIR__ . '/../../../modules');
    }

    /**
     * Add namespace prefix
     */
    public static function addNamespace(string $prefix, string $baseDir): void
    {
        $prefix = trim($prefix, '\\') . '\\';
        $baseDir = rtrim($baseDir, '/') . '/';
        
        if (!isset(self::$prefixes[$prefix])) {
            self::$prefixes[$prefix] = [];
        }
        
        self::$prefixes[$prefix][] = $baseDir;
    }

    /**
     * Load class
     */
    public static function load(string $class): bool
    {
        $prefix = $class;

        // Work backwards through namespace names
        while (false !== $pos = strrpos($prefix, '\\')) {
            $prefix = substr($class, 0, $pos + 1);
            $relativeClass = substr($class, $pos + 1);

            // Try to load mapped file
            $mappedFile = self::loadMappedFile($prefix, $relativeClass);
            if ($mappedFile) {
                return true;
            }

            $prefix = rtrim($prefix, '\\');
        }

        return false;
    }

    /**
     * Load mapped file for namespace prefix and relative class
     */
    private static function loadMappedFile(string $prefix, string $relativeClass): bool
    {
        if (!isset(self::$prefixes[$prefix])) {
            return false;
        }

        foreach (self::$prefixes[$prefix] as $baseDir) {
            $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

            if (self::requireFile($file)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Require file if exists
     */
    private static function requireFile(string $file): bool
    {
        if (file_exists($file)) {
            require $file;
            return true;
        }
        return false;
    }
}
