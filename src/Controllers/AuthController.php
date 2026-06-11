<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\CSRF;
use App\Core\Session;
use App\Core\Validator;
use App\Models\User;

class AuthController
{
    public function showLogin(): void
    {
        Auth::guest();
        view('auth/login', [], 'layouts/auth');
    }

    public function login(): void
    {
        Auth::guest();
        CSRF::verifyOrFail();

        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            Session::flash('error', 'Please fill in all fields.');
            redirect('/login');
        }

        $user = User::findByUsername($username);

        if (!$user || !password_verify($password, $user['password'])) {
            Session::flash('error', 'Invalid username or password.');
            redirect('/login');
        }

        Auth::login($user);

        redirect(Auth::isAdmin() ? '/admin' : '/dashboard');
    }

    public function showRegister(): void
    {
        Auth::guest();
        view('auth/register', ['departments' => $this->departments()], 'layouts/auth');
    }

    public function register(): void
    {
        Auth::guest();
        CSRF::verifyOrFail();

        $v = Validator::make($_POST, [
            'username'         => 'required|min:3|max:50',
            'full_name'        => 'required|min:2|max:100',
            'password'         => 'required|min:8|password',
            'password_confirm' => 'required|match:password',
            'department'       => 'required|in:' . implode(',', array_keys($this->departments())),
        ]);

        if ($v->fails()) {
            Session::flash('errors', $v->allErrors());
            Session::flash('_old_input', $_POST);
            redirect('/register');
        }

        if (User::usernameExists($_POST['username'])) {
            Session::flash('errors', ['That username is already taken.']);
            Session::flash('_old_input', $_POST);
            redirect('/register');
        }

        User::create([
            'username'   => trim($_POST['username']),
            'full_name'  => trim($_POST['full_name']),
            'password'   => $_POST['password'],
            'department' => $_POST['department'],
        ]);

        Session::flash('success', 'Account created! You can now log in.');
        redirect('/login');
    }

    public function logout(): void
    {
        Auth::logout();
        redirect('/login');
    }

    private function departments(): array
    {
        return [
            'General'   => 'General Operations',
            'News'      => 'News & Editorial',
            'Technical' => 'Technical Support',
            'Finance'   => 'Finance & Accounting',
            'HR'        => 'Human Resources',
        ];
    }
}
