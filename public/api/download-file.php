<?php
session_start();
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config.php';

use ChatApp\Chat;
use ChatApp\User;

$user = new User();

// Check if user is logged in
if (!$user->isLoggedIn()) {
    http_response_code(401);
    echo 'Unauthorized';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $filename = $_GET['filename'] ?? null;
    
    if (!$filename) {
        http_response_code(400);
        echo 'Filename is required';
        exit;
    }
    
    // Validate filename (prevent directory traversal)
    if (strpos($filename, '..') !== false || strpos($filename, '/') !== false) {
        http_response_code(400);
        echo 'Invalid filename';
        exit;
    }
    
    $filePath = __DIR__ . '/../uploads/' . $filename;
    
    if (!file_exists($filePath)) {
        http_response_code(404);
        echo 'File not found';
        exit;
    }
    
    // Get file info
    $chat = new Chat();
    $attachments = $chat->getMessageAttachmentsByFilename($filename);
    
    if (empty($attachments)) {
        http_response_code(404);
        echo 'File not found';
        exit;
    }
    
    $attachment = $attachments[0];
    
    // Set headers for download
    header('Content-Type: ' . $attachment['mime_type']);
    header('Content-Disposition: attachment; filename="' . $attachment['original_filename'] . '"');
    header('Content-Length: ' . $attachment['file_size']);
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
    
    // Output file
    readfile($filePath);
} else {
    http_response_code(405);
    echo 'Method not allowed';
} 