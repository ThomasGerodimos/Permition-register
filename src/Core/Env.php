<?php

namespace App\Core;

/**
 * Simple .env file loader.
 * Parses KEY=VALUE lines and populates $_ENV + putenv().
 */
class Env
{
    private static bool $loaded = false;

    /**
     * Load a .env file into environment variables.
     */
    public static function load(string $path): void
    {
        if (self::$loaded) {
            return;
        }

        if (!is_file($path)) {
            return; // Silently skip if .env doesn't exist
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        foreach ($lines as $line) {
            $line = trim($line);

            // Skip comments
            if ($line === '' || $line[0] === '#') {
                continue;
            }

            // Parse KEY=VALUE
            if (!str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key   = trim($key);
            $value = trim($value);

            // Remove surrounding quotes
            if (strlen($value) >= 2) {
                $first = $value[0];
                $last  = $value[strlen($value) - 1];
                if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                    $value = substr($value, 1, -1);
                }
            }

            // Don't overwrite existing environment variables
            if (!array_key_exists($key, $_ENV) && getenv($key) === false) {
                $_ENV[$key] = $value;
                putenv("{$key}={$value}");
            }
        }

        self::$loaded = true;
    }

    /**
     * Get an environment variable with an optional default.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return $_ENV[$key] ?? (getenv($key) ?: $default);
    }

    /**
     * Get a boolean environment variable.
     */
    public static function bool(string $key, bool $default = false): bool
    {
        $val = self::get($key);
        if ($val === null) {
            return $default;
        }
        return in_array(strtolower((string)$val), ['true', '1', 'yes', 'on'], true);
    }

    /**
     * Get an integer environment variable.
     */
    public static function int(string $key, int $default = 0): int
    {
        $val = self::get($key);
        return $val !== null ? (int)$val : $default;
    }
}
