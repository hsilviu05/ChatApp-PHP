<?php
session_start();
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config.php';

use ChatApp\Chat;
use ChatApp\User;

header('Content-Type: application/json');

$user = new User();

// Check if user is logged in
if (!$user->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_FILES['file'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No file uploaded']);
        exit;
    }
    
    $file = $_FILES['file'];
    $receiverId = $_POST['receiver_id'] ?? null;
    $message = trim($_POST['message'] ?? '');
    
    // Validate receiver_id
    if (!$receiverId || !is_numeric($receiverId)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid receiver ID']);
        exit;
    }
    
    // Validate file
    if ($file['error'] !== UPLOAD_ERR_OK) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'File upload error: ' . $file['error']]);
        exit;
    }
    
    // Check file size (10MB limit)
    if ($file['size'] > 10 * 1024 * 1024) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'File too large. Maximum size is 10MB']);
        exit;
    }
    
    // Check file type
    $allowedTypes = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp',
        'application/pdf',
        'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'text/plain'
    ];
    
    if (!in_array($file['type'], $allowedTypes)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'File type not allowed']);
        exit;
    }
    
    // Create uploads directory if it doesn't exist
    $uploadDir = __DIR__ . '/../uploads/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $filePath = $uploadDir . $filename;
    
    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $filePath)) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to save file']);
        exit;
    }
    
    $currentUserId = $_SESSION['user_id'];
    $chat = new Chat();
    
    // Send message with file attachment
    $result = $chat->sendMessage($currentUserId, $receiverId, $message ?: 'Sent a file');
    
    if ($result['success']) {
        $messageId = $result['message_id'];
        
        // Add file attachment to the message
        $attachmentResult = $chat->addFileAttachment(
            $messageId,
            $filename,
            $file['name'],
            'uploads/' . $filename,
            $file['size'],
            $file['type']
        );
        
        if ($attachmentResult) {
            echo json_encode([
                'success' => true,
                'message' => 'File sent successfully',
                'filename' => $filename,
                'original_filename' => $file['name'],
                'file_size' => $file['size'],
                'mime_type' => $file['type'],
                'message_id' => $messageId
            ]);
        } else {
            // Clean up if attachment failed
            unlink($filePath);
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to save file attachment']);
        }
    } else {
        // Clean up if message failed
        unlink($filePath);
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to send message']);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
} 