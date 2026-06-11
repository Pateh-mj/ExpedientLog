<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\CSRF;
use App\Core\Session;
use App\Core\Validator;
use App\Models\User;

class ProfileController
{
    public function index(): void
    {
        Auth::require();

        $user   = User::findById(Auth::id());
        $layout = Auth::isAdmin() ? 'layouts/admin' : 'layouts/app';

        view('profile/index', ['user' => $user], $layout);
    }

    public function update(): void
    {
        Auth::require();
        CSRF::verifyOrFail();

        $v = Validator::make($_POST, [
            'full_name'  => 'required|min:2|max:100',
            'email'      => 'email|max:150',
            'phone'      => 'max:30',
        ]);

        if ($v->fails()) {
            Session::flash('errors', $v->allErrors());
            redirect('/profile');
        }

        $user = User::findById(Auth::id());
        $dept = $user ? ($user['department'] ?? 'General') : 'General';

        User::updateProfile(Auth::id(), [
            'full_name'  => trim($_POST['full_name']),
            'email'      => trim($_POST['email'] ?? ''),
            'phone'      => trim($_POST['phone'] ?? ''),
            'department' => $dept,
        ]);

        // Refresh session display name
        Session::set('full_name',  trim($_POST['full_name']));
        Session::set('department', $dept);

        Session::flash('success', 'Profile updated successfully.');
        redirect('/profile');
    }

    public function changePassword(): void
    {
        Auth::require();
        CSRF::verifyOrFail();

        $current  = $_POST['current_password'] ?? '';
        $new      = $_POST['new_password'] ?? '';
        $confirm  = $_POST['new_password_confirm'] ?? '';

        $user = User::findById(Auth::id());

        if (!password_verify($current, $user['password'])) {
            Session::flash('password_error', 'Current password is incorrect.');
            redirect('/profile');
        }

        $v = Validator::make(['password' => $new, 'confirm' => $confirm], [
            'password' => 'required|min:8|password',
            'confirm'  => 'required|match:password',
        ]);

        if ($v->fails()) {
            Session::flash('password_error', $v->firstError());
            redirect('/profile');
        }

        User::updatePassword(Auth::id(), $new);

        Session::flash('success', 'Password changed successfully.');
        redirect('/profile');
    }
}
