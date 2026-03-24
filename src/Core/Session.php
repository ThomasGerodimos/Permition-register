<?php

namespace App\Core;

class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            $config = Config::load();
            session_name($config['session_name'] ?? 'PERM_REG_SESS');
            session_set_cookie_params([
                'lifetime' => $config['session_lifetime'] ?? 7200,
                'path'     => '/',
                'secure'   => false,
                'httponly' => true,
                'samesite' => 'Lax',
            ]);
            session_start();
        }
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function remove(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function destroy(): void
    {
        session_unset();
        session_destroy();
    }

    /** Flash messages — set once, read once */
    public static function flash(string $key, mixed $value = null): mixed
    {
        if ($value !== null) {
            $_SESSION['_flash'][$key] = $value;
            return null;
        }
        $val = $_SESSION['_flash'][$key] ?? null;
        unset($_SESSION['_flash'][$key]);
        return $val;
    }

    public static function isLoggedIn(): bool
    {
        return self::has('user_id') && self::has('role');
    }

    public static function userId(): ?int
    {
        return self::get('user_id');
    }

    public static function role(): ?string
    {
        return self::get('role');
    }

    public static function isAdmin(): bool
    {
        return self::get('role') === 'admin';
    }

    public static function isManager(): bool
    {
        return self::get('role') === 'manager';
    }

    public static function department(): ?string
    {
        return self::get('department');
    }

    // ── Impersonate (admin previews another role) ───────────────────

    /** Start impersonating: save real session, override role & department */
    public static function impersonate(string $role, ?string $department = null): void
    {
        // Save original values only if not already impersonating
        if (!self::has('_impersonate_original')) {
            self::set('_impersonate_original', [
                'role'       => self::get('role'),
                'department' => self::get('department'),
            ]);
        }
        self::set('role', $role);
        if ($department !== null) {
            self::set('department', $department);
        }
    }

    /** Stop impersonating: restore original session values */
    public static function stopImpersonate(): void
    {
        $original = self::get('_impersonate_original');
        if ($original) {
            self::set('role', $original['role']);
            self::set('department', $original['department']);
            self::remove('_impersonate_original');
        }
    }

    /** Check if currently impersonating */
    public static function isImpersonating(): bool
    {
        return self::has('_impersonate_original');
    }

    /** Get real role (even when impersonating) */
    public static function realRole(): ?string
    {
        $original = self::get('_impersonate_original');
        return $original ? $original['role'] : self::get('role');
    }
}
