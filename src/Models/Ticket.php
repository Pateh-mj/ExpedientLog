<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\DB;

class Ticket
{
    public const PROJECTS = [
        'General / Other',
        'Q4 Financial Audit',
        'Lusaka Branch Operations',
        'IT Systems Upgrade',
        'HR & Recruitment',
        'Field Work',
    ];

    public const KB_CATEGORIES = [
        'General',
        'SOP / Procedure',
        'Client Notes',
        'Templates',
        'Lessons Learned',
        'Contacts',
        'IT / Tech',
    ];

    public static function todayByUser(int $userId): array
    {
        return DB::all(
            'SELECT id, task, project, is_knowledge, category, image_path, created_at, updated_at
             FROM tickets
             WHERE user_id = ? AND DATE(created_at) = CURDATE()
             ORDER BY created_at DESC',
            [$userId]
        );
    }

    public static function create(array $data): string
    {
        return DB::insert(
            'INSERT INTO tickets (user_id, task, project, is_knowledge, category, image_path) VALUES (?, ?, ?, ?, ?, ?)',
            [
                $data['user_id'],
                $data['task'],
                $data['project'],
                $data['is_knowledge'],
                $data['category']   ?? null,
                $data['image_path'] ?? null,
            ]
        );
    }

    public static function findById(int $id): array|false
    {
        return DB::first('SELECT * FROM tickets WHERE id = ?', [$id]);
    }

    public static function update(int $id, array $data): void
    {
        DB::query(
            'UPDATE tickets SET task = ?, project = ?, is_knowledge = ?, category = ?, updated_at = NOW() WHERE id = ?',
            [$data['task'], $data['project'], $data['is_knowledge'], $data['category'] ?? null, $id]
        );
    }

    public static function delete(int $id): void
    {
        DB::query('DELETE FROM tickets WHERE id = ?', [$id]);
    }

    // Admin: fetch logs for a date and optional department
    public static function adminLogs(string $date, string $dept = 'All'): array
    {
        $params = [$date];
        $deptClause = '';
        if ($dept !== 'All') {
            $deptClause = " AND u.department = ?";
            $params[] = $dept;
        }

        return DB::all(
            "SELECT t.id, t.task, t.project, t.created_at, t.is_knowledge, t.image_path,
                    u.username, u.department
             FROM tickets t
             JOIN users u ON t.user_id = u.id
             WHERE DATE(t.created_at) = ? AND u.role = 'employee' {$deptClause}
             ORDER BY t.created_at DESC",
            $params
        );
    }

    // Admin: activity log with optional type & keyword filters
    public static function activityLogs(
        string $date,
        string $dept       = 'All',
        string $typeFilter = 'all',   // all | task | knowledge
        string $search     = ''
    ): array {
        $params = [$date];
        $clauses = ["u.role = 'employee'"];

        if ($dept !== 'All') {
            $clauses[] = "u.department = ?";
            $params[]  = $dept;
        }
        if ($typeFilter === 'knowledge') {
            $clauses[] = "t.is_knowledge = 1";
        } elseif ($typeFilter === 'task') {
            $clauses[] = "t.is_knowledge = 0";
        }
        if ($search !== '') {
            $clauses[] = "t.task LIKE ?";
            $params[]  = "%{$search}%";
        }

        $where = implode(' AND ', $clauses);

        return DB::all(
            "SELECT t.id, t.task, t.project, t.created_at, t.is_knowledge, t.image_path,
                    u.username, u.department
             FROM tickets t
             JOIN users u ON t.user_id = u.id
             WHERE DATE(t.created_at) = ? AND {$where}
             ORDER BY t.created_at DESC",
            $params
        );
    }

    // Admin: KPI stats
    public static function adminStats(string $date, string $dept = 'All'): array
    {
        $params = [$date, $date];
        $deptClause = '';
        if ($dept !== 'All') {
            $deptClause = " AND u.department = ?";
            $params[] = $dept;
        }

        $row = DB::first(
            "SELECT
                COUNT(DISTINCT CASE WHEN DATE(t.created_at) = ? THEN t.user_id END) AS active_today,
                COUNT(CASE WHEN DATE(t.created_at) = ? THEN t.id END) AS total_logs,
                COUNT(DISTINCT u.id) AS total_staff
             FROM users u
             LEFT JOIN tickets t ON u.id = t.user_id
             WHERE u.role = 'employee' {$deptClause}",
            $params
        );

        $row['active_rate'] = $row['total_staff'] > 0
            ? round(($row['active_today'] / $row['total_staff']) * 100, 1)
            : 0;

        return $row;
    }

    // Admin: top performers
    public static function topPerformers(string $date, string $dept = 'All', int $limit = 10): array
    {
        $params = [$date];
        $deptClause = '';
        if ($dept !== 'All') {
            $deptClause = " AND u.department = ?";
            $params[] = $dept;
        }
        $params[] = $limit;

        return DB::all(
            "SELECT u.username, u.department, COUNT(t.id) AS logs
             FROM users u
             LEFT JOIN tickets t ON u.id = t.user_id AND DATE(t.created_at) = ?
             WHERE u.role = 'employee' {$deptClause}
             GROUP BY u.id, u.username, u.department
             ORDER BY logs DESC
             LIMIT ?",
            $params
        );
    }

    // Admin: project breakdown
    public static function projectBreakdown(string $date, string $dept = 'All'): array
    {
        $params = [$date];
        $deptClause = '';
        if ($dept !== 'All') {
            $deptClause = " AND u.department = ?";
            $params[] = $dept;
        }

        return DB::all(
            "SELECT t.project, COUNT(*) AS tasks
             FROM tickets t
             JOIN users u ON t.user_id = u.id
             WHERE DATE(t.created_at) = ? AND u.role = 'employee' {$deptClause}
               AND t.project IS NOT NULL AND t.project != ''
             GROUP BY t.project
             ORDER BY tasks DESC
             LIMIT 6",
            $params
        );
    }

    // Per-employee analytics for a given date
    public static function staffAnalytics(string $date, string $dept = 'All'): array
    {
        $params     = [$date, $date];
        $deptClause = '';
        if ($dept !== 'All') {
            $deptClause = " AND u.department = ?";
            $params[]   = $dept;
        }

        return DB::all(
            "SELECT
               u.id,
               u.username,
               u.department,
               COUNT(t.id)                                        AS log_count,
               SUM(COALESCE(t.is_knowledge, 0))                   AS kb_count,
               TIME_FORMAT(MIN(t.created_at), '%H:%i')            AS first_log,
               TIME_FORMAT(MAX(t.created_at), '%H:%i')            AS last_log,
               TIMESTAMPDIFF(MINUTE, MIN(t.created_at), MAX(t.created_at)) AS span_min,
               (SELECT t2.project FROM tickets t2
                WHERE t2.user_id = u.id AND DATE(t2.created_at) = ?
                  AND t2.project IS NOT NULL AND t2.project != ''
                GROUP BY t2.project ORDER BY COUNT(*) DESC LIMIT 1) AS top_project
             FROM users u
             LEFT JOIN tickets t ON u.id = t.user_id AND DATE(t.created_at) = ?
             WHERE u.role = 'employee' {$deptClause}
             GROUP BY u.id, u.username, u.department
             ORDER BY log_count DESC, u.username ASC",
            $params
        );
    }

    // Hourly log distribution for a given date
    public static function hourlyDistribution(string $date, string $dept = 'All'): array
    {
        $params     = [$date];
        $deptClause = '';
        if ($dept !== 'All') {
            $deptClause = " AND u.department = ?";
            $params[]   = $dept;
        }

        $rows = DB::all(
            "SELECT HOUR(t.created_at) AS hr, COUNT(*) AS count
             FROM tickets t
             JOIN users u ON t.user_id = u.id
             WHERE DATE(t.created_at) = ? AND u.role = 'employee' {$deptClause}
             GROUP BY HOUR(t.created_at)
             ORDER BY hr",
            $params
        );

        $byHour = array_column($rows, 'count', 'hr');

        // Return hours 6–19 with counts
        $result = [];
        for ($h = 6; $h <= 19; $h++) {
            $result[] = ['hour' => $h, 'label' => date('ga', mktime($h, 0, 0)), 'count' => (int)($byHour[$h] ?? 0)];
        }
        return $result;
    }

    // Knowledge base
    public static function knowledge(string $search = '', string $category = ''): array
    {
        $params = [];
        $where  = ["t.is_knowledge = 1"];

        if ($search !== '') {
            $where[]  = "t.task LIKE ?";
            $params[] = "%{$search}%";
        }
        if ($category !== '' && $category !== 'all') {
            $where[]  = "t.category = ?";
            $params[] = $category;
        }

        $whereStr = implode(' AND ', $where);

        return DB::all(
            "SELECT t.id, t.task, t.category, t.image_path, t.created_at, u.username, u.department
             FROM tickets t
             JOIN users u ON t.user_id = u.id
             WHERE {$whereStr}
             ORDER BY t.created_at DESC",
            $params
        );
    }

    // Last 7 days activity strip for an employee
    public static function weeklyStrip(int $userId): array
    {
        $rows = DB::all(
            "SELECT DATE(created_at) AS day, COUNT(*) AS count
             FROM tickets
             WHERE user_id = ? AND created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
             GROUP BY DATE(created_at)",
            [$userId]
        );

        $counts = array_column($rows, 'count', 'day');

        $strip = [];
        for ($i = 6; $i >= 0; $i--) {
            $date    = date('Y-m-d', strtotime("-{$i} days"));
            $strip[] = [
                'date'     => $date,
                'label'    => date('D', strtotime($date)),
                'day_num'  => date('j', strtotime($date)),
                'count'    => (int) ($counts[$date] ?? 0),
                'is_today' => $date === date('Y-m-d'),
            ];
        }

        return $strip;
    }

    // Past 7 days history (excluding today)
    public static function recentHistory(int $userId, int $days = 7): array
    {
        return DB::all(
            "SELECT id, task, project, is_knowledge, category, image_path, created_at
             FROM tickets
             WHERE user_id = ?
               AND DATE(created_at) < CURDATE()
               AND created_at >= DATE_SUB(CURDATE(), INTERVAL ? DAY)
             ORDER BY created_at DESC",
            [$userId, $days]
        );
    }

    // Consecutive days logged — 0 means no streak yet
    public static function streak(int $userId): int
    {
        $rows = DB::all(
            "SELECT DISTINCT DATE(created_at) AS day
             FROM tickets WHERE user_id = ?
             ORDER BY day DESC LIMIT 60",
            [$userId]
        );

        if (empty($rows)) return 0;

        $streak   = 0;
        $expected = date('Y-m-d');

        // If nothing logged today, check if yesterday starts a streak
        if ($rows[0]['day'] !== $expected) {
            $expected = date('Y-m-d', strtotime('-1 day'));
            if ($rows[0]['day'] !== $expected) return 0;
        }

        foreach ($rows as $row) {
            if ($row['day'] === $expected) {
                $streak++;
                $expected = date('Y-m-d', strtotime($expected . ' -1 day'));
            } else {
                break;
            }
        }

        return $streak;
    }

    // Knowledge leaders
    public static function knowledgeLeaders(int $limit = 8): array
    {
        return DB::all(
            "SELECT u.username, u.department, COUNT(*) AS contrib
             FROM tickets t
             JOIN users u ON t.user_id = u.id
             WHERE t.is_knowledge = 1 AND u.role = 'employee'
             GROUP BY u.id, u.username, u.department
             ORDER BY contrib DESC
             LIMIT ?",
            [$limit]
        );
    }
}
