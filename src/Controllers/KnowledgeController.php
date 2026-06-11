<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Models\Ticket;

class KnowledgeController
{
    public function index(): void
    {
        Auth::require();

        $search   = trim($_GET['q'] ?? '');
        $category = trim($_GET['cat'] ?? '');

        $items = Ticket::knowledge($search, $category);

        $layout = Auth::isAdmin() ? 'layouts/admin' : 'layouts/app';

        view('knowledge/index', [
            'items'         => $items,
            'kb_categories' => Ticket::KB_CATEGORIES,
            'search'        => $search,
            'category'      => $category,
        ], $layout);
    }
}
