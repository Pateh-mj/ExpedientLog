<?php

declare(strict_types=1);

namespace App\Core;

class CSRF
{
    private const TOKEN_KEY = '_csrf_token';

    public static function token(): string
    {
        if (!Session::has(self::TOKEN_KEY)) {
            Session::set(self::TOKEN_KEY, bin2hex(random_bytes(32)));
        }
        return Session::get(self::TOKEN_KEY);
    }

    public static function field(): string
    {
        return '<input type="hidden" name="_csrf" value="' . e(self::token()) . '">';
    }

    public static function verify(): bool
    {
        $token = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        return hash_equals(Session::get(self::TOKEN_KEY, ''), $token);
    }

    public static function verifyOrFail(): void
    {
        if (!self::verify()) {
            http_response_code(419);
            exit('CSRF token mismatch.');
        }
    }
}
