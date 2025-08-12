<?php
namespace ChatApp;

class Chat {
    private $db;
    
    public function __construct() {
        $this->db = Db::getInstance()->getConnection();
    }
    
    public function sendMessage($senderId, $receiverId, $message, $type = 'private', $groupId = null) {
        $stmt = $this->db->prepare(
            "INSERT INTO messages (sender_id, receiver_id, group_id, message, message_type) 
             VALUES (?, ?, ?, ?, ?)"
        );
        
        if ($stmt->execute([$senderId, $receiverId, $groupId, $message, $type])) {
            return [
                'success' => true,
                'message_id' => $this->db->lastInsertId(),
                'timestamp' => date('Y-m-d H:i:s')
            ];
        }
        
        return ['success' => false, 'message' => 'Failed to send message'];
    }
    
    public function getMessages($userId1, $userId2, $limit = 50) {
        $stmt = $this->db->prepare(
            "SELECT m.*, u.username as sender_name 
             FROM messages m 
             JOIN users u ON m.sender_id = u.id 
             WHERE (m.sender_id = ? AND m.receiver_id = ?) 
                OR (m.sender_id = ? AND m.receiver_id = ?)
             ORDER BY m.created_at DESC
             LIMIT ?"
        );
        $stmt->execute([$userId1, $userId2, $userId2, $userId1, $limit]);
        $messages = $stmt->fetchAll();
        
        // Reverse to get chronological order
        return array_reverse($messages);
    }
    
    public function getRecentMessages($userId, $limit = 20) {
        $stmt = $this->db->prepare(
            "SELECT m.*, u.username as sender_name, 
                    CASE 
                        WHEN m.sender_id = ? THEN m.receiver_id 
                        ELSE m.sender_id 
                    END as other_user_id,
                    other.username as other_username
             FROM messages m 
             JOIN users u ON m.sender_id = u.id
             JOIN users other ON (
                 CASE 
                     WHEN m.sender_id = ? THEN m.receiver_id 
                     ELSE m.sender_id 
                 END = other.id
             )
             WHERE m.sender_id = ? OR m.receiver_id = ?
             AND m.id IN (
                 SELECT MAX(id) 
                 FROM messages 
                 WHERE (sender_id = ? AND receiver_id = m.receiver_id) 
                    OR (sender_id = m.receiver_id AND receiver_id = ?)
                    OR (sender_id = ? AND receiver_id = m.sender_id)
                    OR (sender_id = m.sender_id AND receiver_id = ?)
                 GROUP BY 
                     CASE 
                         WHEN sender_id = ? THEN receiver_id 
                         ELSE sender_id 
                     END
             )
             ORDER BY m.created_at DESC
             LIMIT ?"
        );
        
        $stmt->execute([$userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId, $userId, $limit]);
        return $stmt->fetchAll();
    }
    
    public function markAsRead($messageId) {
        $stmt = $this->db->prepare("UPDATE messages SET is_read = TRUE WHERE id = ?");
        return $stmt->execute([$messageId]);
    }
    
    public function markConversationAsRead($userId1, $userId2) {
        $stmt = $this->db->prepare(
            "UPDATE messages 
             SET is_read = TRUE 
             WHERE receiver_id = ? AND sender_id = ? AND is_read = FALSE"
        );
        return $stmt->execute([$userId1, $userId2]);
    }
    
    public function getUnreadCount($userId) {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) as count FROM messages WHERE receiver_id = ? AND is_read = FALSE"
        );
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        return $result['count'];
    }
    
    public function getUnreadCountBySender($receiverId, $senderId) {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) as count 
             FROM messages 
             WHERE receiver_id = ? AND sender_id = ? AND is_read = FALSE"
        );
        $stmt->execute([$receiverId, $senderId]);
        $result = $stmt->fetch();
        return $result['count'];
    }
    
    public function deleteMessage($messageId, $userId) {
        // Only allow deletion of own messages
        $stmt = $this->db->prepare(
            "DELETE FROM messages WHERE id = ? AND sender_id = ?"
        );
        return $stmt->execute([$messageId, $userId]);
    }
    
    public function addFileAttachment($messageId, $filename, $originalFilename, $filePath, $fileSize, $mimeType) {
        $stmt = $this->db->prepare(
            "INSERT INTO file_attachments (message_id, filename, original_filename, file_path, file_size, mime_type) 
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        return $stmt->execute([$messageId, $filename, $originalFilename, $filePath, $fileSize, $mimeType]);
    }
    
    public function getMessageAttachments($messageId) {
        $stmt = $this->db->prepare(
            "SELECT * FROM file_attachments WHERE message_id = ?"
        );
        $stmt->execute([$messageId]);
        return $stmt->fetchAll();
    }
    
    public function getMessageAttachmentsByFilename($filename) {
        $stmt = $this->db->prepare(
            "SELECT * FROM file_attachments WHERE filename = ?"
        );
        $stmt->execute([$filename]);
        return $stmt->fetchAll();
    }
    
    public function getMessagesWithAttachments($userId1, $userId2, $limit = 50) {
        $stmt = $this->db->prepare(
            "SELECT m.*, u.username as sender_name,
                    GROUP_CONCAT(
                        JSON_OBJECT(
                            'id', fa.id,
                            'filename', fa.filename,
                            'original_filename', fa.original_filename,
                            'file_size', fa.file_size,
                            'mime_type', fa.mime_type
                        )
                    ) as attachments
             FROM messages m 
             JOIN users u ON m.sender_id = u.id 
             LEFT JOIN file_attachments fa ON m.id = fa.message_id
             WHERE (m.sender_id = ? AND m.receiver_id = ?) 
                OR (m.sender_id = ? AND m.receiver_id = ?)
             GROUP BY m.id
             ORDER BY m.created_at DESC
             LIMIT ?"
        );
        $stmt->execute([$userId1, $userId2, $userId2, $userId1, $limit]);
        $messages = $stmt->fetchAll();
        
        // Process attachments and robustly format created_at
        foreach ($messages as &$message) {
            if ($message['attachments']) {
                $message['attachments'] = json_decode('[' . $message['attachments'] . ']', true);
            } else {
                $message['attachments'] = [];
            }
            // Robustly format created_at as ISO 8601
            if (isset($message['created_at']) && $message['created_at']) {
                $timestamp = strtotime($message['created_at']);
                if ($timestamp && $timestamp > 0) {
                    $message['created_at'] = date('c', $timestamp);
                } else {
                    $message['created_at'] = date('c'); // fallback to now
                }
            } else {
                $message['created_at'] = date('c'); // fallback to now
            }
        }
        
        // Reverse to get chronological order
        return array_reverse($messages);
    }
    
    public function getSeenStatus($messageId) {
        $stmt = $this->db->prepare(
            "SELECT is_read, created_at FROM messages WHERE id = ?"
        );
        $stmt->execute([$messageId]);
        return $stmt->fetch();
    }
    
    public function getMessageHistory($userId, $limit = 100) {
        $stmt = $this->db->prepare(
            "SELECT m.*, u.username as sender_name, u.username as receiver_name,
                    CASE 
                        WHEN m.sender_id = ? THEN m.receiver_id 
                        ELSE m.sender_id 
                    END as other_user_id
             FROM messages m 
             JOIN users u ON m.sender_id = u.id 
             WHERE m.sender_id = ? OR m.receiver_id = ?
             ORDER BY m.created_at DESC
             LIMIT ?"
        );
        $stmt->execute([$userId, $userId, $userId, $limit]);
        return $stmt->fetchAll();
    }
    
    public function getConversationHistory($userId1, $userId2, $limit = 100) {
        return $this->getMessagesWithAttachments($userId1, $userId2, $limit);
    }
    
    public function searchMessages($userId, $searchTerm = '', $conversationId = null, $dateFrom = null, $dateTo = null, $senderId = null, $limit = 50, $offset = 0) {
        $conditions = ["(m.sender_id = ? OR m.receiver_id = ?)"];
        $params = [$userId, $userId];
        
        // Add search term condition
        if (!empty($searchTerm)) {
            $conditions[] = "m.message LIKE ?";
            $params[] = '%' . $searchTerm . '%';
        }
        
        // Add conversation filter
        if ($conversationId) {
            $conditions[] = "((m.sender_id = ? AND m.receiver_id = ?) OR (m.sender_id = ? AND m.receiver_id = ?))";
            $params[] = $userId;
            $params[] = $conversationId;
            $params[] = $conversationId;
            $params[] = $userId;
        }
        
        // Add date range filters
        if ($dateFrom) {
            $conditions[] = "m.created_at >= ?";
            $params[] = $dateFrom . ' 00:00:00';
        }
        
        if ($dateTo) {
            $conditions[] = "m.created_at <= ?";
            $params[] = $dateTo . ' 23:59:59';
        }
        
        // Add sender filter
        if ($senderId) {
            $conditions[] = "m.sender_id = ?";
            $params[] = $senderId;
        }
        
        $whereClause = implode(' AND ', $conditions);
        
        // Get total count for pagination
        $countStmt = $this->db->prepare(
            "SELECT COUNT(*) as total FROM messages m WHERE $whereClause"
        );
        $countStmt->execute($params);
        $total = $countStmt->fetch()['total'];
        
        // Very simple query to get it working
        $sql = "SELECT m.*, 
                       u.username as sender_name,
                       m.receiver_id as conversation_user_id,
                       'Unknown' as conversation_username
                FROM messages m 
                JOIN users u ON m.sender_id = u.id 
                WHERE $whereClause
                ORDER BY m.created_at DESC
                LIMIT ? OFFSET ?";
        
        // Create separate params array for the main query
        $mainParams = $params;
        $mainParams[] = $limit;
        $mainParams[] = $offset;
        

        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($mainParams);
        $messages = $stmt->fetchAll();
        
        // Debug logging
        error_log("Search SQL: " . $sql);
        error_log("Search params: " . json_encode($mainParams));
        error_log("Search results count: " . count($messages));
        error_log("Where clause: " . $whereClause);
        

        
        // Process messages to add context and format dates
        foreach ($messages as &$message) {
            // Add search context (highlight search term)
            if (!empty($searchTerm)) {
                $message['message_highlighted'] = $this->highlightSearchTerm($message['message'], $searchTerm);
            }
            
            // Format date
            if (isset($message['created_at'])) {
                $timestamp = strtotime($message['created_at']);
                $message['formatted_date'] = date('M j, Y g:i A', $timestamp);
                $message['created_at'] = date('c', $timestamp);
            }
            
            // Add conversation context
            $message['is_from_me'] = $message['sender_id'] == $userId;
            
            // Get conversation username - fix the logic
            $convUserId = ($message['sender_id'] == $userId) ? $message['receiver_id'] : $message['sender_id'];
            $convStmt = $this->db->prepare("SELECT username FROM users WHERE id = ?");
            $convStmt->execute([$convUserId]);
            $convUser = $convStmt->fetch();
            $message['conversation_username'] = $convUser ? $convUser['username'] : 'Unknown';
            $message['conversation_user_id'] = $convUserId;
        }
        
        return [
            'messages' => $messages,
            'total' => $total,
            'has_more' => ($offset + $limit) < $total
        ];
    }
    
    private function highlightSearchTerm($text, $searchTerm) {
        $searchTerm = trim($searchTerm);
        if (empty($searchTerm)) {
            return $text;
        }
        
        // Case-insensitive replacement with highlighting
        $pattern = '/(' . preg_quote($searchTerm, '/') . ')/i';
        return preg_replace($pattern, '<mark class="search-highlight">$1</mark>', $text);
    }
    
    // ===== GROUP MESSAGE METHODS =====
    
    /**
     * Send a message to a group
     */
    public function sendGroupMessage($senderId, $groupId, $message) {
        return $this->sendMessage($senderId, null, $message, 'group', $groupId);
    }
    
    /**
     * Get group messages
     */
    public function getGroupMessages($groupId, $limit = 50) {
        $stmt = $this->db->prepare(
            "SELECT m.*, u.username as sender_name,
                    GROUP_CONCAT(
                        JSON_OBJECT(
                            'id', fa.id,
                            'filename', fa.filename,
                            'original_filename', fa.original_filename,
                            'file_size', fa.file_size,
                            'mime_type', fa.mime_type
                        )
                    ) as attachments
             FROM messages m 
             JOIN users u ON m.sender_id = u.id 
             LEFT JOIN file_attachments fa ON m.id = fa.message_id
             WHERE m.group_id = ? AND m.message_type = 'group'
             GROUP BY m.id
             ORDER BY m.created_at DESC
             LIMIT ?"
        );
        $stmt->execute([$groupId, $limit]);
        $messages = $stmt->fetchAll();
        
        // Process attachments and format dates
        foreach ($messages as &$message) {
            if ($message['attachments']) {
                $message['attachments'] = json_decode('[' . $message['attachments'] . ']', true);
            } else {
                $message['attachments'] = [];
            }
            
            // Format created_at
            if (isset($message['created_at']) && $message['created_at']) {
                $timestamp = strtotime($message['created_at']);
                if ($timestamp && $timestamp > 0) {
                    $message['created_at'] = date('c', $timestamp);
                } else {
                    $message['created_at'] = date('c');
                }
            } else {
                $message['created_at'] = date('c');
            }
        }
        
        // Reverse to get chronological order
        return array_reverse($messages);
    }
    
    /**
     * Get recent group conversations for a user
     */
    public function getRecentGroupConversations($userId, $limit = 20) {
        $stmt = $this->db->prepare(
            "SELECT g.*, 
                    (SELECT COUNT(*) FROM group_members WHERE group_id = g.id) as member_count,
                    (SELECT username FROM users WHERE id = g.created_by) as creator_name,
                    (SELECT message FROM messages WHERE group_id = g.id ORDER BY created_at DESC LIMIT 1) as last_message,
                    (SELECT created_at FROM messages WHERE group_id = g.id ORDER BY created_at DESC LIMIT 1) as last_message_time
             FROM `groups` g
             JOIN group_members gm ON g.id = gm.group_id
             WHERE gm.user_id = ?
             ORDER BY last_message_time DESC NULLS LAST, g.created_at DESC
             LIMIT ?"
        );
        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll();
    }
    
    /**
     * Mark group messages as read for a user
     */
    public function markGroupAsRead($groupId, $userId) {
        $stmt = $this->db->prepare(
            "UPDATE messages 
             SET is_read = TRUE 
             WHERE group_id = ? AND receiver_id = ? AND is_read = FALSE"
        );
        return $stmt->execute([$groupId, $userId]);
    }
    
    /**
     * Get unread group message count for a user
     */
    public function getUnreadGroupCount($userId) {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) as count 
             FROM messages m
             JOIN group_members gm ON m.group_id = gm.group_id
             WHERE m.receiver_id = ? AND m.is_read = FALSE AND m.message_type = 'group'"
        );
        $stmt->execute([$userId]);
        $result = $stmt->fetch();
        return $result['count'];
    }
}