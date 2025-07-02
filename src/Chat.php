<?php
namespace ChatApp;

class Chat {
    private $db;
    
    public function __construct() {
        $this->db = Db::getInstance()->getConnection();
    }
    
    public function sendMessage($senderId, $receiverId, $message, $type = 'private') {
        $stmt = $this->db->prepare(
            "INSERT INTO messages (sender_id, receiver_id, message, message_type) 
             VALUES (?, ?, ?, ?)"
        );
        
        if ($stmt->execute([$senderId, $receiverId, $message, $type])) {
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
    
    public function searchMessages($userId, $searchTerm, $limit = 50) {
        $searchTerm = '%' . $searchTerm . '%';
        $stmt = $this->db->prepare(
            "SELECT m.*, u.username as sender_name
             FROM messages m 
             JOIN users u ON m.sender_id = u.id 
             WHERE (m.sender_id = ? OR m.receiver_id = ?) 
                AND m.message LIKE ?
             ORDER BY m.created_at DESC
             LIMIT ?"
        );
        $stmt->execute([$userId, $userId, $searchTerm, $limit]);
        return $stmt->fetchAll();
    }
}