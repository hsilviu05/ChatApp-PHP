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

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get POST data
$input = json_decode(file_get_contents('php://input'), true);
$messageId = $input['message_id'] ?? null;
$reactionType = $input['reaction_type'] ?? null;

if (!$messageId || !$reactionType) {
    http_response_code(400);
    echo json_encode(['error' => 'Message ID and reaction type are required']);
    exit;
}

try {
    $result = $reaction->toggleReaction($messageId, $currentUser['id'], $reactionType);
    
    if ($result) {
        // Get updated reaction data
        $reactionSummary = $reaction->getReactionSummary([$messageId]);
        $userReactions = $reaction->getUserReactionsForMessages($currentUser['id'], [$messageId]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Reaction toggled successfully',
            'reactions' => $reactionSummary,
            'user_reactions' => $userReactions[$messageId] ?? []
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to toggle reaction']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
