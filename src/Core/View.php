<?php

namespace App\Core;

class View
{
    private static string $viewsPath = '';

    public static function setPath(string $path): void
    {
        self::$viewsPath = rtrim($path, '/\\');
    }

    public static function render(string $template, array $data = [], bool $withLayout = true): void
    {
        // Make variables available in view
        extract($data, EXTR_SKIP);

        $viewFile = self::$viewsPath . '/' . ltrim($template, '/') . '.php';

        if (!file_exists($viewFile)) {
            throw new \RuntimeException("View not found: {$viewFile}");
        }

        if ($withLayout) {
            $content = self::capture($viewFile, $data);

            $layoutFile = self::$viewsPath . '/layout/main.php';
            if (file_exists($layoutFile)) {
                extract(['content' => $content] + $data, EXTR_SKIP);
                require $layoutFile;
            } else {
                echo $content;
            }
        } else {
            require $viewFile;
        }
    }

    public static function capture(string $file, array $data = []): string
    {
        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        return ob_get_clean();
    }

    /** Redirect helper */
    public static function redirect(string $path, array $flash = []): never
    {
        foreach ($flash as $key => $msg) {
            Session::flash($key, $msg);
        }
        $baseUrl = Config::appUrl();
        header('Location: ' . $baseUrl . '/' . ltrim($path, '/'));
        exit;
    }

    /** JSON response */
    public static function json(mixed $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /** Escape output */
    public static function e(mixed $value): string
    {
        return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
