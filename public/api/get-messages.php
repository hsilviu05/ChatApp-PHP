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
    $otherUserId = $_GET['user_id'] ?? null;
    $limit = $_GET['limit'] ?? 50;
    
    if (!$otherUserId) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'User ID is required']);
        exit;
    }
    
    $currentUserId = $_SESSION['user_id'];
    
    $chat = new Chat();
    $messages = $chat->getMessagesWithAttachments($currentUserId, $otherUserId, $limit);
    
    // Debug output
    error_log("[get-messages] currentUserId={$currentUserId}, otherUserId={$otherUserId}, messagesReturned=" . count($messages));
    
    echo json_encode([
        'success' => true,
        'messages' => $messages
    ]);
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
} 