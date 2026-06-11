<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Models\Ticket;
use App\Models\Announcement;

class DashboardController
{
    public function index(): void
    {
        Auth::require();

        $userId = Auth::id();

        view('dashboard/index', [
            'tasks'         => Ticket::todayByUser($userId),
            'history'       => Ticket::recentHistory($userId),
            'weekly_strip'  => Ticket::weeklyStrip($userId),
            'streak'        => Ticket::streak($userId),
            'projects'      => Ticket::PROJECTS,
            'kb_categories' => Ticket::KB_CATEGORIES,
            'announcements' => Announcement::recent(3),
            'today'         => date('l, j F Y'),
        ]);
    }
}
