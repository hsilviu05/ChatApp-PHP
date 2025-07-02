<?php
session_start();
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config.php';

use ChatApp\User;

header('Content-Type: application/json');

$user = new User();

if (!$user->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$users = $user->getAllUsers();

echo json_encode([
    'success' => true,
    'users' => $users
]); 