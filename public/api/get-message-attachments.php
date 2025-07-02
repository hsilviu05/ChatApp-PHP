<?php
session_start();
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config.php';

use ChatApp\Chat;
use ChatApp\User;

header('Content-Type: application/json');

$user = new User();

if (!$user->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $messageId = $_GET['message_id'] ?? null;
    
    if (!$messageId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Message ID is required']);
        exit;
    }
    
    $chat = new Chat();
    $attachments = $chat->getMessageAttachments($messageId);
    
    // Format attachments for frontend
    $formattedAttachments = [];
    foreach ($attachments as $attachment) {
        $formattedAttachments[] = [
            'id' => $attachment['id'],
            'filename' => $attachment['filename'],
            'original_filename' => $attachment['original_filename'],
            'file_size' => $attachment['file_size'],
            'mime_type' => $attachment['mime_type']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'attachments' => $formattedAttachments
    ]);
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
} 