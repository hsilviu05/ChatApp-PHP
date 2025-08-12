<?php
session_start();
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config.php';

use ChatApp\User;
use ChatApp\Group;
use ChatApp\Chat;

header('Content-Type: application/json');

$user = new User();
$group = new Group();
$chat = new Chat();

// Check if user is logged in
if (!$user->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$currentUser = $user->getCurrentUser();

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
$groupId = $input['group_id'] ?? null;
$message = trim($input['message'] ?? '');

if (!$groupId) {
    http_response_code(400);
    echo json_encode(['error' => 'Group ID is required']);
    exit;
}

if (empty($message)) {
    http_response_code(400);
    echo json_encode(['error' => 'Message is required']);
    exit;
}

// Check if user is member of the group
if (!$group->isMember($groupId, $currentUser['id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied - not a member of this group']);
    exit;
}

try {
    $result = $chat->sendGroupMessage($currentUser['id'], $groupId, $message);
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message_id' => $result['message_id'],
            'timestamp' => $result['timestamp']
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => $result['message']]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
