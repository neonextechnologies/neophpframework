<?php

namespace NeoCore\System\Core;

/**
 * Config - Configuration loader
 * 
 * No magic. Explicit file loading.
 */
class Config
{
    private static array $config = [];
    private static string $configPath = '';

    /**
     * Initialize config with path
     */
    public static function init(string $configPath): void
    {
        self::$configPath = rtrim($configPath, '/');
    }

    /**
     * Load configuration file
     */
    public static function load(string $file): array
    {
        if (isset(self::$config[$file])) {
            return self::$config[$file];
        }

        $filePath = self::$configPath . '/' . $file . '.php';

        if (!file_exists($filePath)) {
            return [];
        }

        self::$config[$file] = require $filePath;
        return self::$config[$file];
    }

    /**
     * Get configuration value using dot notation
     * 
     * Example: Config::get('database.host')
     */
    public static function get(string $key, $default = null)
    {
        $parts = explode('.', $key);
        $file = array_shift($parts);

        $config = self::load($file);

        foreach ($parts as $part) {
            if (!isset($config[$part])) {
                return $default;
            }
            $config = $config[$part];
        }

        return $config;
    }

    /**
     * Set configuration value
     */
    public static function set(string $key, $value): void
    {
        $parts = explode('.', $key);
        $file = array_shift($parts);

        if (!isset(self::$config[$file])) {
            self::$config[$file] = [];
        }

        $config = &self::$config[$file];

        foreach ($parts as $part) {
            if (!isset($config[$part])) {
                $config[$part] = [];
            }
            $config = &$config[$part];
        }

        $config = $value;
    }

    /**
     * Check if configuration key exists
     */
    public static function has(string $key): bool
    {
        $parts = explode('.', $key);
        $file = array_shift($parts);

        $config = self::load($file);

        foreach ($parts as $part) {
            if (!isset($config[$part])) {
                return false;
            }
            $config = $config[$part];
        }

        return true;
    }

    /**
     * Get all configuration for a file
     */
    public static function getAll(string $file): array
    {
        return self::load($file);
    }

    /**
     * Clear loaded configuration
     */
    public static function clear(?string $file = null): void
    {
        if ($file === null) {
            self::$config = [];
        } else {
            unset(self::$config[$file]);
        }
    }
}
