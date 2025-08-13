<?php
session_start();
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../config.php';

use ChatApp\User;
use ChatApp\Chat;

$user = new User();
$chat = new Chat();

// Redirect if not logged in
if (!$user->isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$currentUser = $user->getCurrentUser();
$allUsers = $user->getAllUsers();
$recentMessages = $chat->getRecentMessages($currentUser['id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat App</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .chat-container {
            width: 90%;
            max-width: 1000px;
            height: 80vh;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }
        
        .chat-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .chat-header h1 {
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
        
        .logout-btn {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            padding: 8px 16px;
            border-radius: 20px;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .logout-btn:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .groups-btn-header {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            padding: 8px 12px;
            border-radius: 50%;
            cursor: pointer;
            transition: background 0.3s;
            margin-right: 10px;
        }
        
        .groups-btn-header:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .search-btn-header {
            background: rgba(255,255,255,0.2);
            border: none;
            color: white;
            padding: 8px 12px;
            border-radius: 50%;
            cursor: pointer;
            transition: background 0.3s;
            margin-right: 10px;
        }
        
        .search-btn-header:hover {
            background: rgba(255,255,255,0.3);
        }
        
        .chat-main {
            display: flex;
            flex: 1;
            overflow: hidden;
        }
        
        .users-sidebar {
            width: 250px;
            background: #f8f9fa;
            border-right: 1px solid #e9ecef;
            overflow-y: auto;
        }
        
        .users-header {
            padding: 15px;
            border-bottom: 1px solid #e9ecef;
            font-weight: 600;
            color: #495057;
        }
        
        .user-item {
            padding: 15px;
            cursor: pointer;
            transition: background 0.3s;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .user-item:hover {
            background: #e9ecef;
        }
        
        .user-item.active {
            background: #667eea;
            color: white;
        }
        
        .user-avatar-small {
            width: 35px;
            height: 35px;
            background: #667eea;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
        }
        
        .user-item.active .user-avatar-small {
            background: white;
            color: #667eea;
        }
        
        .unread-badge {
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
            margin-left: auto;
        }
        
        .chat-area {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .chat-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            background: #f8f9fa;
        }
        
        .message {
            margin-bottom: 15px;
            display: flex;
            align-items: flex-end;
            gap: 10px;
        }
        
        .message.sent {
            flex-direction: row-reverse;
        }
        
        .message-content {
            max-width: 70%;
            padding: 12px 16px;
            border-radius: 18px;
            position: relative;
            word-wrap: break-word;
        }
        
        .message.sent .message-content {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-bottom-right-radius: 4px;
        }
        
        .message.received .message-content {
            background: white;
            color: #333;
            border: 1px solid #e9ecef;
            border-bottom-left-radius: 4px;
        }
        
        .message-time {
            font-size: 11px;
            opacity: 0.7;
            margin-top: 5px;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .message.sent .message-time {
            justify-content: flex-end;
        }
        
        .seen-indicator {
            color: #4CAF50;
            font-size: 14px;
        }
        
        .file-attachment {
            margin-top: 10px;
            padding: 10px;
            background: rgba(255,255,255,0.1);
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .message.received .file-attachment {
            background: #f8f9fa;
        }
        
        .file-attachment:hover {
            background: rgba(255,255,255,0.2);
        }
        
        .message.received .file-attachment:hover {
            background: #e9ecef;
        }
        
        .file-icon {
            font-size: 20px;
            color: #667eea;
        }
        
        .file-info {
            flex: 1;
        }
        
        .file-name {
            font-weight: 600;
            margin-bottom: 2px;
        }
        
        .file-size {
            font-size: 12px;
            opacity: 0.7;
        }
        
        .chat-input {
            padding: 20px;
            background: white;
            border-top: 1px solid #e9ecef;
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }
        
        .input-group {
            flex: 1;
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }
        
        .message-input {
            flex: 1;
            padding: 12px 16px;
            border: 2px solid #e9ecef;
            border-radius: 25px;
            outline: none;
            font-size: 14px;
            resize: none;
            min-height: 45px;
            max-height: 120px;
            transition: border-color 0.3s;
        }
        
        .message-input:focus {
            border-color: #667eea;
        }
        
        .file-input {
            display: none;
        }
        
        .file-btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 50%;
            cursor: pointer;
            transition: background 0.3s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .file-btn:hover {
            background: #5a6fd8;
        }
        
        .send-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 25px;
            cursor: pointer;
            transition: transform 0.3s;
            font-weight: 600;
        }
        
        .send-btn:hover {
            transform: translateY(-2px);
        }
        
        .send-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        .typing-indicator {
            padding: 10px 20px;
            color: #6c757d;
            font-style: italic;
            font-size: 14px;
        }
        
        .no-chat-selected {
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
            color: #6c757d;
            font-size: 18px;
            text-align: center;
        }
        
        .no-chat-selected i {
            font-size: 48px;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        @media (max-width: 768px) {
            .chat-container {
                width: 100%;
                height: 100vh;
                border-radius: 0;
            }
            
            .users-sidebar {
                width: 200px;
            }
        }
    </style>
</head>
<body>
    <div class="chat-container">
        <div class="chat-header">
            <h1><i class="fas fa-comments"></i> Chat App</h1>
            <div class="user-info">
                <button class="groups-btn-header" onclick="openGroups()" title="Groups & Channels">
                    <i class="fas fa-users"></i>
                </button>
                <button class="search-btn-header" onclick="openSearch()" title="Search Messages (Ctrl+F)">
                    <i class="fas fa-search"></i>
                </button>
                <div class="user-avatar">
                    <?php echo strtoupper(substr($_SESSION['username'], 0, 1)); ?>
                </div>
                <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
        
        <div class="chat-main">
            <div class="users-sidebar">
                <div class="users-header">
                    <i class="fas fa-users"></i> Users
                </div>
                <div id="users-list">
                    <!-- Users will be loaded here -->
                </div>
            </div>
            
            <div class="chat-area">
                <div id="chat-messages" class="chat-messages">
                    <div class="no-chat-selected">
                        <div>
                            <i class="fas fa-comment-dots"></i>
                            <p>Select a user to start chatting</p>
                        </div>
                    </div>
                </div>
                
                <div class="chat-input">
                    <div class="input-group">
                        <input type="file" id="file-input" class="file-input" accept="image/*,.pdf,.doc,.docx,.txt,.xls,.xlsx">
                        <button type="button" class="file-btn" onclick="document.getElementById('file-input').click()">
                            <i class="fas fa-paperclip"></i>
                        </button>
                        <textarea 
                            id="message-input" 
                            class="message-input" 
                            placeholder="Type a message..."
                            rows="1"
                        ></textarea>
                        <button type="button" id="send-btn" class="send-btn" disabled>
                            <i class="fas fa-paper-plane"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'search-interface.php'; ?>
    <?php include 'groups-interface.php'; ?>
    <?php include 'reactions-interface.php'; ?>

    <script src="js/app.js"></script>
</body>
</html>