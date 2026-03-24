<?php

namespace App\Auth;

use App\Core\Database;

class IpRestriction
{
    /**
     * Check whether the given IP is allowed for the given role.
     * If no restrictions exist for a role, access is allowed.
     */
    public static function isAllowed(string $ip, string $role): bool
    {
        $db = Database::getInstance();

        $ranges = $db->fetchAll(
            'SELECT ip_range FROM ip_restrictions WHERE role = ? AND is_active = 1',
            [$role]
        );

        // No restrictions configured → allow all
        if (empty($ranges)) {
            return true;
        }

        foreach ($ranges as $row) {
            if (self::ipInRange($ip, $row['ip_range'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if an IP matches a range (single IP or CIDR notation).
     */
    public static function ipInRange(string $ip, string $range): bool
    {
        // Exact match
        if ($ip === $range) {
            return true;
        }

        // CIDR
        if (str_contains($range, '/')) {
            [$subnet, $bits] = explode('/', $range, 2);
            $bits = (int)$bits;

            // IPv4
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
                && filter_var($subnet, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
            ) {
                $ipLong     = ip2long($ip);
                $subnetLong = ip2long($subnet);
                $mask       = -1 << (32 - $bits);
                return ($ipLong & $mask) === ($subnetLong & $mask);
            }

            // IPv6 (basic support)
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                $ipBin     = inet_pton($ip);
                $subnetBin = inet_pton($subnet);
                if ($ipBin === false || $subnetBin === false) {
                    return false;
                }
                $fullBits = 128;
                $ipInt     = self::binToInt($ipBin);
                $subnetInt = self::binToInt($subnetBin);
                $diff      = $ipInt ^ $subnetInt;
                return self::leadingZeros($diff) >= $bits;
            }
        }

        return false;
    }

    /** Get the real client IP (considers proxies) */
    public static function clientIp(): string
    {
        // Priority: proxy headers first, then REMOTE_ADDR
        $headers = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR',
        ];

        $ip = '0.0.0.0';
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                // X-Forwarded-For may contain: client, proxy1, proxy2 — take the first
                $candidate = trim(explode(',', $_SERVER[$header])[0]);
                if (filter_var($candidate, FILTER_VALIDATE_IP)) {
                    $ip = $candidate;
                    break;
                }
            }
        }

        // Normalize IPv6 loopback (::1) to IPv4 loopback
        if ($ip === '::1') {
            $ip = '127.0.0.1';
        }

        // If loopback (local access), try to detect the machine's LAN IP
        if ($ip === '127.0.0.1') {
            $lanIp = self::detectLanIp();
            if ($lanIp) {
                $ip = $lanIp;
            }
        }

        return $ip;
    }

    /**
     * Detect the server's LAN IP when accessed locally (127.0.0.1).
     * This is useful when the app runs on WAMP and the browser accesses via localhost.
     */
    private static function detectLanIp(): ?string
    {
        // Method 1: SERVER_ADDR if it's a LAN IP (when accessed via LAN hostname)
        if (!empty($_SERVER['SERVER_ADDR'])
            && $_SERVER['SERVER_ADDR'] !== '127.0.0.1'
            && $_SERVER['SERVER_ADDR'] !== '::1'
            && filter_var($_SERVER['SERVER_ADDR'], FILTER_VALIDATE_IP)
        ) {
            return $_SERVER['SERVER_ADDR'];
        }

        // Method 2: gethostbyname — resolve machine hostname to IP
        $hostname = gethostname();
        if ($hostname) {
            $resolved = gethostbyname($hostname);
            if ($resolved !== $hostname
                && $resolved !== '127.0.0.1'
                && filter_var($resolved, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
                && !str_starts_with($resolved, '169.254.')  // skip APIPA
            ) {
                return $resolved;
            }
        }

        return null;
    }

    private static function binToInt(string $bin): \GMP
    {
        $hex = bin2hex($bin);
        return gmp_init($hex, 16);
    }

    private static function leadingZeros(\GMP $n): int
    {
        $bin = gmp_strval($n, 2);
        return 128 - strlen(ltrim($bin, '0'));
    }
}
