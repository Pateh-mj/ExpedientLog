<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Models\Ticket;

class ExportController
{
    public function export(): void
    {
        Auth::requireAdmin();

        $date       = $_GET['date']  ?? date('Y-m-d');
        $dept       = $_GET['dept']  ?? 'All';
        $typeFilter = $_GET['type']  ?? 'all';   // all | task | knowledge
        $search     = trim($_GET['q'] ?? '');

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = date('Y-m-d');
        }

        $logs = Ticket::activityLogs($date, $dept, $typeFilter, $search);

        $parts    = [$date];
        if ($dept !== 'All')         $parts[] = $dept;
        if ($typeFilter !== 'all')   $parts[] = ucfirst($typeFilter);
        if ($search !== '')          $parts[] = 'search-' . preg_replace('/[^a-z0-9]/i', '_', $search);
        $filename = 'ExpedientLog_' . implode('_', $parts) . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['#', 'Date', 'Time', 'Username', 'Department', 'Project', 'Type', 'Task / Issue']);

        foreach ($logs as $i => $row) {
            fputcsv($output, [
                $i + 1,
                substr($row['created_at'], 0, 10),
                substr($row['created_at'], 11, 5),
                $row['username'],
                $row['department'] ?: '',
                $row['project']    ?: '',
                $row['is_knowledge'] ? 'Knowledge Base' : 'Task',
                $row['task'],
            ]);
        }

        fclose($output);
        exit();
    }
}
