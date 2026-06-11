<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;

class FileController
{
    public function serve(string $filename): void
    {
        Auth::require();

        // Sanitize - no directory traversal
        $filename = basename($filename);
        $path     = APP_ROOT . '/storage/uploads/' . $filename;

        if (!file_exists($path)) {
            http_response_code(404);
            exit('File not found.');
        }

        $mime = mime_content_type($path) ?: 'application/octet-stream';

        // Only serve images
        if (!str_starts_with($mime, 'image/')) {
            http_response_code(403);
            exit('Forbidden.');
        }

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: private, max-age=86400');
        readfile($path);
        exit();
    }
}
