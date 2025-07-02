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
    
    $senderId = $input['sender_id'] ?? null;
    $receiverId = $input['receiver_id'] ?? null;
    $message = trim($input['message'] ?? '');
    
    // Validate input
    if (!$senderId || !$receiverId || empty($message)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Missing required fields']);
        exit;
    }
    
    // Verify sender is the logged-in user
    if ($senderId != $_SESSION['user_id']) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Forbidden']);
        exit;
    }
    
    $chat = new Chat();
    $result = $chat->sendMessage($senderId, $receiverId, $message);
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message_id' => $result['message_id'],
            'timestamp' => $result['timestamp']
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => $result['message']]);
    }
} else {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
} 