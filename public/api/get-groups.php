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

try {
    $userGroups = $group->getUserGroups($currentUser['id']);
    
    echo json_encode([
        'success' => true,
        'groups' => $userGroups
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
