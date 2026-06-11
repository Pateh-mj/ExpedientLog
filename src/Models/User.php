<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\DB;

class User
{
    public static function findById(int $id): array|false
    {
        return DB::first('SELECT * FROM users WHERE id = ?', [$id]);
    }

    public static function findByUsername(string $username): array|false
    {
        return DB::first('SELECT * FROM users WHERE username = ?', [$username]);
    }

    public static function all(): array
    {
        return DB::all('SELECT id, username, full_name, email, role, department, phone, created_at FROM users ORDER BY username ASC');
    }

    public static function employees(): array
    {
        return DB::all(
            "SELECT id, username, full_name, email, department, phone, created_at
             FROM users WHERE role = 'employee' ORDER BY username ASC"
        );
    }

    public static function create(array $data): string
    {
        return DB::insert(
            'INSERT INTO users (username, password, role, department, full_name, email, phone) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [
                $data['username'],
                password_hash($data['password'], PASSWORD_DEFAULT),
                $data['role']       ?? 'employee',
                $data['department'] ?? 'General',
                $data['full_name']  ?? '',
                $data['email']      ?? '',
                $data['phone']      ?? '',
            ]
        );
    }

    public static function updateProfile(int $id, array $data): void
    {
        DB::query(
            'UPDATE users SET full_name = ?, email = ?, phone = ?, department = ? WHERE id = ?',
            [$data['full_name'], $data['email'], $data['phone'], $data['department'], $id]
        );
    }

    public static function adminUpdate(int $id, array $data): void
    {
        DB::query(
            'UPDATE users SET username = ?, full_name = ?, email = ?, phone = ?, department = ?, role = ? WHERE id = ?',
            [
                $data['username'],
                $data['full_name'],
                $data['email'],
                $data['phone'],
                $data['department'],
                $data['role'],
                $id
            ]
        );
    }

    public static function updatePassword(int $id, string $password): void
    {
        DB::query(
            'UPDATE users SET password = ? WHERE id = ?',
            [password_hash($password, PASSWORD_DEFAULT), $id]
        );
    }

    public static function resetPassword(int $id): string
    {
        $temp = bin2hex(random_bytes(6));
        self::updatePassword($id, $temp);
        return $temp;
    }

    public static function delete(int $id): void
    {
        DB::query('DELETE FROM users WHERE id = ?', [$id]);
    }

    public static function usernameExists(string $username, int $excludeId = 0): bool
    {
        $row = DB::first(
            'SELECT id FROM users WHERE username = ? AND id != ?',
            [$username, $excludeId]
        );
        return $row !== false;
    }
}
