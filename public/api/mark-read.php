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
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON']);
        exit;
    }
    
    $messageId = $input['message_id'] ?? null;
    $senderId = $input['sender_id'] ?? null;
    
    $chat = new Chat();
    
    if ($messageId) {
        // Mark specific message as read
        $result = $chat->markAsRead($messageId);
    } elseif ($senderId) {
        // Mark all messages from sender as read
        $result = $chat->markConversationAsRead($_SESSION['user_id'], $senderId);
    } else {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Message ID or sender ID required']);
        exit;
    }
    
    echo json_encode(['success' => $result]);
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
} 