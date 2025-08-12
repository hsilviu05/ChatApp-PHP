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

// Get group ID from query parameters
$groupId = $_GET['group_id'] ?? null;

if (!$groupId) {
    http_response_code(400);
    echo json_encode(['error' => 'Group ID is required']);
    exit;
}

// Check if user is member of the group
if (!$group->isMember($groupId, $currentUser['id'])) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied - not a member of this group']);
    exit;
}

try {
    $limit = min((int)($_GET['limit'] ?? 50), 100); // Max 100 messages
    $messages = $chat->getGroupMessages($groupId, $limit);
    $groupInfo = $group->getGroup($groupId);
    
    echo json_encode([
        'success' => true,
        'messages' => $messages,
        'group' => $groupInfo
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
