<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\CSRF;
use App\Core\Session;
use App\Core\Validator;
use App\Models\Announcement;

class AnnouncementController
{
    public function index(): void
    {
        Auth::require();

        $announcements = Announcement::all();
        $layout = Auth::isAdmin() ? 'layouts/admin' : 'layouts/app';

        view('announcements/index', ['announcements' => $announcements], $layout);
    }

    public function adminIndex(): void
    {
        Auth::requireAdmin();

        $announcements = Announcement::all();
        view('announcements/manage', ['announcements' => $announcements], 'layouts/admin');
    }

    public function create(): void
    {
        Auth::requireAdmin();
        CSRF::verifyOrFail();

        $v = Validator::make($_POST, [
            'title' => 'required|min:3|max:200',
            'body'  => 'required|min:5|max:5000',
        ]);

        if ($v->fails()) {
            Session::flash('errors', $v->allErrors());
            redirect('/admin/announcements');
        }

        Announcement::create([
            'title'      => trim($_POST['title']),
            'body'       => trim($_POST['body']),
            'created_by' => Auth::id(),
            'is_pinned'  => isset($_POST['is_pinned']) ? 1 : 0,
        ]);

        Session::flash('success', 'Announcement posted.');
        redirect('/admin/announcements');
    }

    public function delete(): void
    {
        Auth::requireAdmin();
        CSRF::verifyOrFail();

        $id = (int) ($_POST['id'] ?? 0);
        if ($id) {
            Announcement::delete($id);
        }

        Session::flash('success', 'Announcement removed.');
        redirect('/admin/announcements');
    }
}
