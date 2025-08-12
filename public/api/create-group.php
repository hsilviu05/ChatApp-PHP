<?php
session_start();
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config.php';

use ChatApp\User;
use ChatApp\Group;

header('Content-Type: application/json');

$user = new User();
$group = new Group();

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
$groupName = trim($input['name'] ?? '');
$memberIds = $input['member_ids'] ?? [];

if (empty($groupName)) {
    http_response_code(400);
    echo json_encode(['error' => 'Group name is required']);
    exit;
}

if (strlen($groupName) > 100) {
    http_response_code(400);
    echo json_encode(['error' => 'Group name too long (max 100 characters)']);
    exit;
}

try {
    $groupId = $group->createGroup($groupName, $currentUser['id'], $memberIds);
    
    echo json_encode([
        'success' => true,
        'group_id' => $groupId,
        'message' => 'Group created successfully'
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
