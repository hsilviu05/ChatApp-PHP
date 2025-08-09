<?php
session_start();
require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config.php';

use ChatApp\User;
use ChatApp\Chat;

header('Content-Type: application/json');

$user = new User();
$chat = new Chat();

// Check if user is logged in
if (!$user->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized - User not logged in']);
    exit;
}

$currentUser = $user->getCurrentUser();

// Get search parameters
$searchTerm = $_GET['q'] ?? '';
$conversationId = $_GET['conversation_id'] ?? null;
$dateFrom = $_GET['date_from'] ?? null;
$dateTo = $_GET['date_to'] ?? null;
$senderId = $_GET['sender_id'] ?? null;
$limit = min((int)($_GET['limit'] ?? 50), 100); // Max 100 results
$offset = (int)($_GET['offset'] ?? 0);

if (empty($searchTerm) && empty($dateFrom) && empty($dateTo) && empty($senderId)) {
    echo json_encode(['error' => 'Search term or filters required']);
    exit;
}

try {
    error_log("Search request - User: {$currentUser['id']}, Term: '$searchTerm', Limit: $limit");
    
    $results = $chat->searchMessages(
        $currentUser['id'],
        $searchTerm,
        $conversationId,
        $dateFrom,
        $dateTo,
        $senderId,
        $limit,
        $offset
    );
    
    error_log("Search results - Found: " . count($results['messages']) . " messages, Total: {$results['total']}");
    
    echo json_encode([
        'success' => true,
        'results' => $results['messages'],
        'total' => $results['total'],
        'has_more' => $results['has_more']
    ]);
    
} catch (Exception $e) {
    error_log("Search error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Search failed: ' . $e->getMessage()]);
} 