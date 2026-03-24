<?php

namespace App\Core;

class Config
{
    private static ?array $data = null;

    /** Load config once, return cached array */
    public static function load(): array
    {
        if (self::$data === null) {
            self::$data = require ROOT_PATH . '/config/config.php';
        }
        return self::$data;
    }

    /** Get a config value by key (supports dot notation: 'db.host') */
    public static function get(string $key, mixed $default = null): mixed
    {
        $config = self::load();

        if (str_contains($key, '.')) {
            $keys = explode('.', $key);
            $value = $config;
            foreach ($keys as $k) {
                if (!is_array($value) || !array_key_exists($k, $value)) {
                    return $default;
                }
                $value = $value[$k];
            }
            return $value;
        }

        return $config[$key] ?? $default;
    }

    /** Get the app URL (trimmed) */
    public static function appUrl(): string
    {
        return rtrim(self::get('app_url', ''), '/');
    }
}
