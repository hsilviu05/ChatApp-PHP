<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;
use ChatApp\Chat;

class ChatWebSocket implements MessageComponentInterface {
    protected $clients;
    protected $users = [];
    protected $chat;
    
    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->chat = new Chat();
    }
    
    public function onOpen(ConnectionInterface $conn) {
        $this->clients->attach($conn);
        echo "New connection! ({$conn->resourceId})\n";
    }
    
    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        
        if (!$data) {
            echo "Invalid JSON received\n";
            return;
        }
        
        echo "Received message: " . json_encode($data) . "\n";
        
        switch ($data['type']) {
            case 'auth':
                $this->handleAuth($from, $data);
                break;
            case 'message':
                $this->handleMessage($from, $data);
                break;
            case 'group_message':
                $this->handleGroupMessage($from, $data);
                break;
            case 'typing':
                $this->handleTyping($from, $data);
                break;
            case 'read':
                $this->handleRead($from, $data);
                break;
            default:
                echo "Unknown message type: " . $data['type'] . "\n";
        }
    }
    
    private function handleAuth($from, $data) {
        $userId = $data['user_id'] ?? null;
        $username = $data['username'] ?? null;
        
        if ($userId && $username) {
            $this->users[$from->resourceId] = [
                'user_id' => $userId,
                'username' => $username,
                'connection' => $from
            ];
            
            echo "User authenticated: {$username} (ID: {$userId})\n";
            
            // Send confirmation back to the user
            $from->send(json_encode([
                'type' => 'auth_success',
                'user_id' => $userId,
                'username' => $username
            ]));
        }
    }
    
    private function handleMessage($from, $data) {
        $senderId = $data['sender_id'] ?? null;
        $receiverId = $data['receiver_id'] ?? null;
        $message = $data['message'] ?? '';
        $timestamp = $data['timestamp'] ?? date('Y-m-d H:i:s');
        $hasAttachment = $data['has_attachment'] ?? false;
        
        if (!$senderId || !$receiverId || !$message) {
            echo "Invalid message data\n";
            return;
        }
        
        // Save message to database if not already saved
        if (!isset($data['saved'])) {
            $result = $this->chat->sendMessage($senderId, $receiverId, $message);
            if ($result['success']) {
                $data['message_id'] = $result['message_id'];
            }
        }
        
        echo "Message sent from {$senderId} to {$receiverId}: {$message}\n";
        
        // Send to receiver
        foreach ($this->users as $userId => $user) {
            if ($user['user_id'] == $receiverId) {
                $user['connection']->send(json_encode([
                    'type' => 'message',
                    'sender_id' => $senderId,
                    'receiver_id' => $receiverId,
                    'message' => $message,
                    'timestamp' => $timestamp,
                    'message_id' => $data['message_id'] ?? null,
                    'has_attachment' => $hasAttachment
                ]));
                break;
            }
        }
        
        // Send read receipt to sender
        $from->send(json_encode([
            'type' => 'read',
            'message_id' => $data['message_id'] ?? null,
            'receiver_id' => $receiverId
        ]));
    }
    
    private function handleTyping($from, $data) {
        $senderId = $data['sender_id'] ?? null;
        $receiverId = $data['receiver_id'] ?? null;
        $isTyping = $data['is_typing'] ?? false;
        
        if (!$senderId || !$receiverId) {
            return;
        }
        
        // Send typing indicator to receiver
        foreach ($this->users as $userId => $user) {
            if ($user['user_id'] == $receiverId) {
                $user['connection']->send(json_encode([
                    'type' => 'typing',
                    'sender_id' => $senderId,
                    'receiver_id' => $receiverId,
                    'is_typing' => $isTyping
                ]));
                break;
            }
        }
    }
    
    private function handleGroupMessage($from, $data) {
        $senderId = $data['sender_id'] ?? null;
        $groupId = $data['group_id'] ?? null;
        $message = $data['message'] ?? '';
        $timestamp = $data['timestamp'] ?? date('Y-m-d H:i:s');
        
        if (!$senderId || !$groupId || !$message) {
            echo "Invalid group message data\n";
            return;
        }
        
        // Save message to database if not already saved
        if (!isset($data['saved'])) {
            $result = $this->chat->sendGroupMessage($senderId, $groupId, $message);
            if ($result['success']) {
                $data['message_id'] = $result['message_id'];
            }
        }
        
        echo "Group message sent from {$senderId} to group {$groupId}: {$message}\n";
        
        // Send to all group members (except sender)
        foreach ($this->users as $userId => $user) {
            if ($user['user_id'] != $senderId) {
                // Check if user is member of the group (this would need to be implemented)
                // For now, send to all users
                $user['connection']->send(json_encode([
                    'type' => 'group_message',
                    'sender_id' => $senderId,
                    'group_id' => $groupId,
                    'message' => $message,
                    'timestamp' => $timestamp,
                    'message_id' => $data['message_id'] ?? null
                ]));
            }
        }
    }
    
    private function handleRead($from, $data) {
        $senderId = $data['sender_id'] ?? null;
        $receiverId = $data['receiver_id'] ?? null;
        
        if (!$senderId || !$receiverId) {
            return;
        }
        
        // Send read receipt to all clients
        foreach ($this->clients as $client) {
            if ($from !== $client) {
                $client->send(json_encode($data));
            }
        }
    }
    
    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        
        if (isset($this->users[$conn->resourceId])) {
            $username = $this->users[$conn->resourceId]['username'];
            unset($this->users[$conn->resourceId]);
            echo "User {$username} disconnected\n";
        } else {
            echo "Connection {$conn->resourceId} has disconnected\n";
        }
    }
    
    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }
}

// Start the WebSocket server
$server = IoServer::factory(
    new HttpServer(
        new WsServer(
            new ChatWebSocket()
        )
    ),
    8080
);

echo "Chat server started on port 8080\n";
echo "Press Ctrl+C to stop the server\n";

$server->run();