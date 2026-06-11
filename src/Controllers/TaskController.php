<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\CSRF;
use App\Core\FileUpload;
use App\Core\Session;
use App\Models\Ticket;

class TaskController
{
    public function create(): void
    {
        Auth::require();
        CSRF::verifyOrFail();

        $task     = trim($_POST['task'] ?? '');
        $project  = $_POST['project'] ?? 'General / Other';
        $isKb     = isset($_POST['save_as_knowledge']) ? 1 : 0;
        $category = $isKb ? ($_POST['category'] ?? 'General') : null;

        if (empty($task) || strlen($task) > 1500) {
            Session::flash('error', 'Task must be between 1 and 1500 characters.');
            redirect('/dashboard');
        }

        if (!in_array($project, Ticket::PROJECTS, true)) {
            $project = 'General / Other';
        }

        if ($isKb && !in_array($category, Ticket::KB_CATEGORIES, true)) {
            $category = 'General';
        }

        $upload = FileUpload::handleImage('task_image');
        if ($upload['error']) {
            Session::flash('error', $upload['error']);
            redirect('/dashboard');
        }

        Ticket::create([
            'user_id'      => Auth::id(),
            'task'         => $task,
            'project'      => $project,
            'is_knowledge' => $isKb,
            'category'     => $category,
            'image_path'   => $upload['path'],
        ]);

        Session::flash('success', 'Activity logged successfully.' . ($upload['path'] ? ' Image attached.' : ''));
        redirect('/dashboard');
    }

    public function update(): void
    {
        Auth::require();

        if (!CSRF::verify()) {
            json(['error' => 'Invalid request.'], 419);
        }

        $id       = (int) ($_POST['id'] ?? 0);
        $task     = trim($_POST['task'] ?? '');
        $project  = $_POST['project'] ?? 'General / Other';
        $isKb     = isset($_POST['save_as_knowledge']) ? 1 : 0;
        $category = $isKb ? ($_POST['category'] ?? 'General') : null;

        if (!$id || empty($task) || strlen($task) > 1500) {
            json(['error' => 'Invalid input.'], 400);
        }

        $ticket = Ticket::findById($id);

        if (!$ticket) {
            json(['error' => 'Task not found.'], 404);
        }
        if ((int) $ticket['user_id'] !== Auth::id()) {
            json(['error' => 'Permission denied.'], 403);
        }
        if (date('Y-m-d', strtotime($ticket['created_at'])) !== date('Y-m-d')) {
            json(['error' => 'Cannot edit logs older than today.'], 403);
        }

        if (!in_array($project, Ticket::PROJECTS, true)) {
            $project = 'General / Other';
        }

        Ticket::update($id, [
            'task'         => $task,
            'project'      => $project,
            'is_knowledge' => $isKb,
            'category'     => $category,
        ]);

        json([
            'success' => true,
            'data'    => [
                'id'           => $id,
                'task'         => $task,
                'project'      => $project,
                'is_knowledge' => $isKb,
                'category'     => $category,
                'time'         => date('H:i'),
            ],
        ]);
    }

    public function delete(): void
    {
        Auth::require();

        if (!CSRF::verify()) {
            json(['error' => 'Invalid request.'], 419);
        }

        $id = (int) ($_POST['id'] ?? 0);

        if (!$id) {
            json(['error' => 'Invalid ID.'], 400);
        }

        $ticket = Ticket::findById($id);

        if (!$ticket) {
            json(['error' => 'Task not found.'], 404);
        }
        if ((int) $ticket['user_id'] !== Auth::id()) {
            json(['error' => 'Permission denied.'], 403);
        }
        if (date('Y-m-d', strtotime($ticket['created_at'])) !== date('Y-m-d')) {
            json(['error' => 'Cannot delete logs older than today.'], 403);
        }

        if ($ticket['image_path']) {
            FileUpload::delete($ticket['image_path']);
        }

        Ticket::delete($id);

        json(['success' => true]);
    }
}
