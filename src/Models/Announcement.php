<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\DB;

class Announcement
{
    public static function all(): array
    {
        return DB::all(
            "SELECT a.id, a.title, a.body, a.is_pinned, a.created_at, u.username AS author
             FROM announcements a
             JOIN users u ON a.created_by = u.id
             ORDER BY a.is_pinned DESC, a.created_at DESC"
        );
    }

    public static function recent(int $limit = 5): array
    {
        return DB::all(
            "SELECT a.id, a.title, a.body, a.is_pinned, a.created_at, u.username AS author
             FROM announcements a
             JOIN users u ON a.created_by = u.id
             ORDER BY a.is_pinned DESC, a.created_at DESC
             LIMIT ?",
            [$limit]
        );
    }

    public static function create(array $data): string
    {
        return DB::insert(
            'INSERT INTO announcements (title, body, created_by, is_pinned) VALUES (?, ?, ?, ?)',
            [
                $data['title'],
                $data['body'],
                $data['created_by'],
                $data['is_pinned'] ?? 0,
            ]
        );
    }

    public static function delete(int $id): void
    {
        DB::query('DELETE FROM announcements WHERE id = ?', [$id]);
    }

    public static function countUnread(): int
    {
        $row = DB::first('SELECT COUNT(*) AS cnt FROM announcements WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)');
        return (int) ($row['cnt'] ?? 0);
    }
}
