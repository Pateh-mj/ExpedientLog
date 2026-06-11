<?php

declare(strict_types=1);

define('APP_ROOT', dirname(__DIR__));

// Bootstrap
require APP_ROOT . '/config/app.php';
require APP_ROOT . '/src/Core/helpers.php';

// Autoloader (simple PSR-4 style)
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $base   = APP_ROOT . '/src/';
    if (!str_starts_with($class, $prefix)) return;
    $relative = str_replace('\\', '/', substr($class, strlen($prefix)));
    $file     = $base . $relative . '.php';
    if (file_exists($file)) {
        require $file;
    }
});

use App\Core\Session;
use App\Core\Router;
use App\Controllers\AuthController;
use App\Controllers\DashboardController;
use App\Controllers\TaskController;
use App\Controllers\KnowledgeController;
use App\Controllers\AnnouncementController;
use App\Controllers\ProfileController;
use App\Controllers\AdminController;
use App\Controllers\ExportController;
use App\Controllers\FileController;

Session::start();

$router = new Router(BASE_PATH);

// Auth routes
$router->get('/',          [AuthController::class, 'showLogin']);
$router->get('/login',     [AuthController::class, 'showLogin']);
$router->post('/login',    [AuthController::class, 'login']);
$router->get('/register',  [AuthController::class, 'showRegister']);
$router->post('/register', [AuthController::class, 'register']);
$router->get('/logout',    [AuthController::class, 'logout']);

// Employee routes
$router->get('/dashboard',         [DashboardController::class, 'index']);
$router->post('/tasks',            [TaskController::class, 'create']);
$router->post('/tasks/update',     [TaskController::class, 'update']);
$router->post('/tasks/delete',     [TaskController::class, 'delete']);
$router->get('/knowledge',         [KnowledgeController::class, 'index']);
$router->get('/announcements',     [AnnouncementController::class, 'index']);
$router->get('/profile',           [ProfileController::class, 'index']);
$router->post('/profile/update',   [ProfileController::class, 'update']);
$router->post('/profile/password', [ProfileController::class, 'changePassword']);

// File serving (uploads outside web root)
$router->get('/uploads/{filename}', [FileController::class, 'serve']);

// Admin routes
$router->get('/admin',                    [AdminController::class, 'dashboard']);
$router->get('/admin/activity-log',       [AdminController::class, 'activityLog']);
$router->get('/admin/users',              [AdminController::class, 'users']);
$router->post('/admin/users/reset',       [AdminController::class, 'resetPassword']);
$router->post('/admin/users/update',      [AdminController::class, 'updateUser']);
$router->post('/admin/users/delete',      [AdminController::class, 'deleteUser']);
$router->get('/admin/announcements',      [AnnouncementController::class, 'adminIndex']);
$router->post('/admin/announcements',     [AnnouncementController::class, 'create']);
$router->post('/admin/announcements/delete', [AnnouncementController::class, 'delete']);
$router->get('/admin/export',             [ExportController::class, 'export']);

$router->dispatch();
