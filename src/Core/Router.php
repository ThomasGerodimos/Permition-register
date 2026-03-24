<?php

namespace App\Core;

class Router
{
    private array $routes = [];

    public function get(string $path, callable|array $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    public function post(string $path, callable|array $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    public function dispatch(string $method, string $uri): void
    {
        // Strip query string
        $uri = parse_url($uri, PHP_URL_PATH);

        // Remove base path (/permissions/public or /permissions)
        $basePaths = ['/permissions/public', '/permissions'];
        foreach ($basePaths as $base) {
            if (str_starts_with($uri, $base)) {
                $uri = substr($uri, strlen($base));
                break;
            }
        }

        $uri = '/' . ltrim($uri, '/');
        if ($uri !== '/' && str_ends_with($uri, '/')) {
            $uri = rtrim($uri, '/');
        }

        $routes = $this->routes[$method] ?? [];

        // Exact match
        if (isset($routes[$uri])) {
            $this->invoke($routes[$uri], []);
            return;
        }

        // Pattern match (e.g. /permissions/{id}/edit)
        foreach ($routes as $pattern => $handler) {
            $regex = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $pattern);
            $regex = '#^' . $regex . '$#';
            if (preg_match($regex, $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $this->invoke($handler, $params);
                return;
            }
        }

        // 404
        http_response_code(404);
        echo '<h1>404 — Σελίδα δεν βρέθηκε</h1>';
    }

    private function invoke(callable|array $handler, array $params): void
    {
        if (is_array($handler)) {
            [$class, $method] = $handler;
            $instance = new $class();
            $instance->$method(...array_values($params));
        } else {
            $handler(...array_values($params));
        }
    }
}
