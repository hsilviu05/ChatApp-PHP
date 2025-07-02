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

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $senderId = $_GET['sender_id'] ?? null;
    $currentUserId = $_SESSION['user_id'];
    
    $chat = new Chat();
    
    if ($senderId) {
        // Get unread count from specific sender
        $count = $chat->getUnreadCountBySender($currentUserId, $senderId);
    } else {
        // Get total unread count
        $count = $chat->getUnreadCount($currentUserId);
    }
    
    echo json_encode([
        'success' => true,
        'count' => $count
    ]);
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
} 