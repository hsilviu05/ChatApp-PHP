<?php
session_start();
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config.php';

use ChatApp\User;
use ChatApp\Reaction;

header('Content-Type: application/json');

$user = new User();
$reaction = new Reaction();

// Check if user is logged in
if (!$user->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$currentUser = $user->getCurrentUser();

// Get message ID from query parameters
$messageId = $_GET['message_id'] ?? null;

if (!$messageId) {
    http_response_code(400);
    echo json_encode(['error' => 'Message ID is required']);
    exit;
}

try {
    $reactionSummary = $reaction->getReactionSummary([$messageId]);
    $userReactions = $reaction->getUserReactionsForMessages($currentUser['id'], [$messageId]);
    
    echo json_encode([
        'success' => true,
        'reactions' => $reactionSummary,
        'user_reactions' => $userReactions[$messageId] ?? []
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
