<?php

declare(strict_types=1);

namespace App\Core;

class Router
{
    private array $routes = [];
    private string $basePath;

    public function __construct(string $basePath = '')
    {
        $this->basePath = rtrim($basePath, '/');
    }

    public function get(string $uri, callable|array $handler): void
    {
        $this->add('GET', $uri, $handler);
    }

    public function post(string $uri, callable|array $handler): void
    {
        $this->add('POST', $uri, $handler);
    }

    private function add(string $method, string $uri, callable|array $handler): void
    {
        $this->routes[] = [
            'method'  => $method,
            'uri'     => $uri,
            'handler' => $handler,
        ];
    }

    public function dispatch(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri    = $_SERVER['REQUEST_URI'];

        // Strip base path
        if ($this->basePath && str_starts_with($uri, $this->basePath)) {
            $uri = substr($uri, strlen($this->basePath));
        }

        // Strip query string
        $uri = strtok($uri, '?') ?: '/';
        $uri = '/' . trim($uri, '/');

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $pattern = $this->uriToPattern($route['uri']);
            if (preg_match($pattern, $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                $this->call($route['handler'], $params);
                return;
            }
        }

        // 404
        http_response_code(404);
        view('errors/404', [], null);
    }

    private function uriToPattern(string $uri): string
    {
        $pattern = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $uri);
        return '#^' . $pattern . '$#';
    }

    private function call(callable|array $handler, array $params): void
    {
        if (is_callable($handler)) {
            call_user_func_array($handler, $params);
            return;
        }

        [$class, $method] = $handler;
        $controller = new $class();
        call_user_func_array([$controller, $method], $params);
    }
}
