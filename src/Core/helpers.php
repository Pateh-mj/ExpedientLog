<?php

declare(strict_types=1);

use App\Core\Session;
use App\Core\Auth;

if (!function_exists('e')) {
    function e(mixed $value): string
    {
        return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        $base = rtrim(defined('BASE_PATH') ? BASE_PATH : '', '/');
        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return url('assets/' . ltrim($path, '/'));
    }
}

if (!function_exists('redirect')) {
    function redirect(string $path, int $code = 302): never
    {
        header('Location: ' . url($path), true, $code);
        exit();
    }
}

if (!function_exists('view')) {
    function view(string $template, array $data = [], ?string $layout = 'layouts/app'): void
    {
        extract($data, EXTR_SKIP);

        ob_start();
        require APP_ROOT . '/src/Views/' . $template . '.php';
        $content = ob_get_clean();

        if ($layout !== null) {
            require APP_ROOT . '/src/Views/' . $layout . '.php';
        } else {
            echo $content;
        }
    }
}

if (!function_exists('json')) {
    function json(mixed $data, int $code = 200): never
    {
        http_response_code($code);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }
}

if (!function_exists('flash')) {
    function flash(string $key, mixed $value): void
    {
        Session::flash($key, $value);
    }
}

if (!function_exists('old')) {
    function old(string $key, mixed $default = ''): string
    {
        $input = Session::getFlash('_old_input', []);
        return e($input[$key] ?? $default);
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return \App\Core\CSRF::field();
    }
}

if (!function_exists('auth')) {
    function auth(): array
    {
        return Auth::user();
    }
}

if (!function_exists('is_admin')) {
    function is_admin(): bool
    {
        return Auth::isAdmin();
    }
}

if (!function_exists('format_date')) {
    function format_date(string $datetime, string $format = 'j M Y'): string
    {
        return date($format, strtotime($datetime));
    }
}

if (!function_exists('time_ago')) {
    function time_ago(string $datetime): string
    {
        $diff = time() - strtotime($datetime);
        if ($diff < 60)    return 'just now';
        if ($diff < 3600)  return floor($diff / 60) . 'm ago';
        if ($diff < 86400) return floor($diff / 3600) . 'h ago';
        return floor($diff / 86400) . 'd ago';
    }
}

if (!function_exists('upload_path')) {
    function upload_path(string $filename = ''): string
    {
        return APP_ROOT . '/storage/uploads/' . $filename;
    }
}

if (!function_exists('upload_url')) {
    function upload_url(string $path): string
    {
        if (empty($path)) return '';
        $filename = basename($path);
        return url('uploads/' . $filename);
    }
}
