<?php

declare(strict_types=1);

namespace App\Core;

class Auth
{
    private const TIMEOUT = 1800; // 30 minutes

    public static function check(): bool
    {
        return Session::has('user_id');
    }

    public static function id(): ?int
    {
        $id = Session::get('user_id');
        return $id !== null ? (int) $id : null;
    }

    public static function user(): array
    {
        return [
            'id'         => Session::get('user_id'),
            'username'   => Session::get('username'),
            'role'       => Session::get('role'),
            'department' => Session::get('department'),
            'full_name'  => Session::get('full_name'),
        ];
    }

    public static function role(): string
    {
        return Session::get('role', 'employee');
    }

    public static function isAdmin(): bool
    {
        return in_array(Session::get('role'), ['supervisor', 'admin'], true);
    }

    public static function login(array $user): void
    {
        Session::regenerate();
        Session::set('user_id',    $user['id']);
        Session::set('username',   $user['username']);
        Session::set('role',       $user['role']);
        Session::set('department', $user['department']);
        Session::set('full_name',  $user['full_name'] ?? $user['username']);
        Session::set('last_activity', time());
    }

    public static function logout(): void
    {
        Session::destroy();
    }

    public static function checkTimeout(): bool
    {
        $last = Session::get('last_activity');
        if ($last && (time() - $last > self::TIMEOUT)) {
            self::logout();
            return false;
        }
        Session::set('last_activity', time());
        return true;
    }

    public static function require(): void
    {
        if (!self::check() || !self::checkTimeout()) {
            redirect('/login');
        }
    }

    public static function requireAdmin(): void
    {
        self::require();
        if (!self::isAdmin()) {
            http_response_code(403);
            view('errors/403');
            exit();
        }
    }

    public static function guest(): void
    {
        if (self::check()) {
            redirect(self::isAdmin() ? '/admin' : '/dashboard');
        }
    }
}
