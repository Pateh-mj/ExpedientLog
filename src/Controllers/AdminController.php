<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\CSRF;
use App\Core\DB;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Ticket;
use App\Models\User;
use App\Models\Announcement;

class AdminController
{
    public function dashboard(): void
    {
        Auth::requireAdmin();

        $date = $_GET['date'] ?? date('Y-m-d');
        $dept = $_GET['dept'] ?? 'All';

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = date('Y-m-d');
        }

        $departments = array_merge(
            ['All'],
            array_column(
                DB::all("SELECT DISTINCT department FROM users WHERE role='employee' AND department IS NOT NULL ORDER BY department"),
                'department'
            )
        );

        $stats          = Ticket::adminStats($date, $dept);
        $logs           = Ticket::adminLogs($date, $dept);
        $projects       = Ticket::projectBreakdown($date, $dept);
        $kb_leaders     = Ticket::knowledgeLeaders();
        $staff_analytics = Ticket::staffAnalytics($date, $dept);
        $hourly         = Ticket::hourlyDistribution($date, $dept);
        $max_hourly     = max(array_column($hourly, 'count') ?: [1]);
        $kb_today       = array_sum(array_column($logs, 'is_knowledge'));

        view('admin/dashboard', [
            'date'            => $date,
            'dept'            => $dept,
            'departments'     => $departments,
            'stats'           => $stats,
            'logs'            => $logs,
            'projects'        => $projects,
            'kb_leaders'      => $kb_leaders,
            'max_tasks'       => max(array_column($projects, 'tasks') ?: [1]),
            'staff_analytics' => $staff_analytics,
            'hourly'          => $hourly,
            'max_hourly'      => $max_hourly,
            'kb_today'        => $kb_today,
        ], 'layouts/admin');
    }

    public function activityLog(): void
    {
        Auth::requireAdmin();

        $date       = $_GET['date']  ?? date('Y-m-d');
        $dept       = $_GET['dept']  ?? 'All';
        $typeFilter = $_GET['type']  ?? 'all';   // all | task | knowledge
        $search     = trim($_GET['q'] ?? '');

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            $date = date('Y-m-d');
        }

        $departments = array_merge(
            ['All'],
            array_column(
                DB::all("SELECT DISTINCT department FROM users WHERE role='employee' AND department IS NOT NULL ORDER BY department"),
                'department'
            )
        );

        $logs = Ticket::activityLogs($date, $dept, $typeFilter, $search);

        view('admin/activity_log', [
            'date'        => $date,
            'dept'        => $dept,
            'typeFilter'  => $typeFilter,
            'search'      => $search,
            'departments' => $departments,
            'logs'        => $logs,
        ], 'layouts/admin');
    }

    public function users(): void
    {
        Auth::requireAdmin();

        $users = User::all();

        view('admin/users', ['users' => $users], 'layouts/admin');
    }

    public function updateUser(): void
    {
        Auth::requireAdmin();
        CSRF::verifyOrFail();

        $id = (int) ($_POST['id'] ?? 0);
        if (!$id) {
            Session::flash('error', 'Invalid user.');
            redirect('/admin/users');
        }

        $v = Validator::make($_POST, [
            'username'   => 'required|min:3|max:50',
            'full_name'  => 'required|min:2|max:100',
            'email'      => 'email|max:150',
            'phone'      => 'max:30',
            'department' => 'required|in:General,News,Technical,Finance,HR',
            'role'       => 'required|in:employee,supervisor,admin',
        ]);

        if ($v->fails()) {
            Session::flash('errors', $v->allErrors());
            redirect('/admin/users');
        }

        $username = trim($_POST['username']);
        if (User::usernameExists($username, $id)) {
            Session::flash('error', 'That username is already taken by another user.');
            redirect('/admin/users');
        }

        User::adminUpdate($id, [
            'username'   => $username,
            'full_name'  => trim($_POST['full_name']),
            'email'      => trim($_POST['email'] ?? ''),
            'phone'      => trim($_POST['phone'] ?? ''),
            'department' => trim($_POST['department']),
            'role'       => trim($_POST['role']),
        ]);

        if ($id === Auth::id()) {
            Session::set('full_name',  trim($_POST['full_name']));
            Session::set('department', trim($_POST['department']));
        }

        Session::flash('success', 'User updated successfully.');
        redirect('/admin/users');
    }

    public function resetPassword(): void
    {
        Auth::requireAdmin();
        CSRF::verifyOrFail();

        $id = (int) ($_POST['id'] ?? 0);
        if (!$id) {
            Session::flash('error', 'Invalid user.');
            redirect('/admin/users');
        }

        $temp = User::resetPassword($id);

        Session::flash('success', "Password reset. Temporary password: <strong>{$temp}</strong>");
        redirect('/admin/users');
    }

    public function deleteUser(): void
    {
        Auth::requireAdmin();
        CSRF::verifyOrFail();

        $id = (int) ($_POST['id'] ?? 0);

        if ($id === Auth::id()) {
            Session::flash('error', 'You cannot delete your own account.');
            redirect('/admin/users');
        }

        if ($id) {
            User::delete($id);
            Session::flash('success', 'User removed.');
        }

        redirect('/admin/users');
    }
}
