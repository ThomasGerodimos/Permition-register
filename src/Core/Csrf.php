<?php

namespace App\Core;

class Csrf
{
    private const TOKEN_KEY = '_csrf_token';

    public static function generate(): string
    {
        if (empty($_SESSION[self::TOKEN_KEY])) {
            $_SESSION[self::TOKEN_KEY] = bin2hex(random_bytes(32));
        }
        return $_SESSION[self::TOKEN_KEY];
    }

    public static function validate(?string $token): bool
    {
        if (empty($token) || empty($_SESSION[self::TOKEN_KEY])) {
            return false;
        }
        return hash_equals($_SESSION[self::TOKEN_KEY], $token);
    }

    public static function field(): string
    {
        $token = self::generate();
        return '<input type="hidden" name="_csrf" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
    }

    /** Validate and abort if invalid */
    public static function check(): void
    {
        $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
        if (!self::validate($token)) {
            http_response_code(403);
            die('CSRF token mismatch. Please go back and try again.');
        }
    }
}
