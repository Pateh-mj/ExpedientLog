<?php

declare(strict_types=1);

namespace App\Core;

class FileUpload
{
    private const ALLOWED_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    private const MAX_SIZE      = 5_242_880; // 5MB

    public static function handleImage(string $fieldName): array
    {
        if (!isset($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
            return ['path' => null, 'error' => null];
        }

        $file = $_FILES[$fieldName];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['path' => null, 'error' => 'Upload failed (error code ' . $file['error'] . ').'];
        }

        if ($file['size'] > self::MAX_SIZE) {
            return ['path' => null, 'error' => 'File exceeds maximum size of 5MB.'];
        }

        $info = @getimagesize($file['tmp_name']);
        if ($info === false || !in_array($info['mime'], self::ALLOWED_TYPES, true)) {
            return ['path' => null, 'error' => 'Only JPG, PNG, GIF, and WebP images are allowed.'];
        }

        $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = time() . '_' . bin2hex(random_bytes(8)) . '.' . strtolower($ext);
        $destDir  = APP_ROOT . '/storage/uploads/';
        $destPath = $destDir . $filename;

        if (!is_dir($destDir)) {
            mkdir($destDir, 0755, true);
        }

        if (!move_uploaded_file($file['tmp_name'], $destPath)) {
            return ['path' => null, 'error' => 'Could not save the uploaded file.'];
        }

        return ['path' => $filename, 'error' => null];
    }

    public static function delete(?string $filename): void
    {
        if ($filename) {
            $path = APP_ROOT . '/storage/uploads/' . basename($filename);
            if (file_exists($path)) {
                unlink($path);
            }
        }
    }
}
