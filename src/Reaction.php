<?php
namespace ChatApp;

use PDO;
use PDOException;

class Reaction {
    private $db;
    
    // Available reaction types
    private const REACTIONS = [
        '👍' => 'thumbs_up',
        '👎' => 'thumbs_down', 
        '❤️' => 'heart',
        '😂' => 'joy',
        '😮' => 'open_mouth',
        '😢' => 'cry',
        '😡' => 'angry',
        '🎉' => 'party',
        '👏' => 'clap',
        '🔥' => 'fire'
    ];
    
    public function __construct() {
        $this->db = Db::getInstance()->getConnection();
    }
    
    /**
     * Get all available reaction types
     */
    public function getAvailableReactions() {
        return self::REACTIONS;
    }
    
    /**
     * Add a reaction to a message
     */
    public function addReaction($messageId, $userId, $reactionType) {
        try {
            // Validate reaction type
            if (!in_array($reactionType, array_values(self::REACTIONS))) {
                throw new \Exception("Invalid reaction type");
            }
            
            $stmt = $this->db->prepare("
                INSERT INTO message_reactions (message_id, user_id, reaction_type) 
                VALUES (?, ?, ?)
                ON DUPLICATE KEY UPDATE created_at = CURRENT_TIMESTAMP
            ");
            
            return $stmt->execute([$messageId, $userId, $reactionType]);
            
        } catch (PDOException $e) {
            throw new \Exception("Failed to add reaction: " . $e->getMessage());
        }
    }
    
    /**
     * Remove a reaction from a message
     */
    public function removeReaction($messageId, $userId, $reactionType) {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM message_reactions 
                WHERE message_id = ? AND user_id = ? AND reaction_type = ?
            ");
            
            return $stmt->execute([$messageId, $userId, $reactionType]);
            
        } catch (PDOException $e) {
            throw new \Exception("Failed to remove reaction: " . $e->getMessage());
        }
    }
    
    /**
     * Toggle a reaction (add if not exists, remove if exists)
     */
    public function toggleReaction($messageId, $userId, $reactionType) {
        try {
            // Check if reaction already exists
            $stmt = $this->db->prepare("
                SELECT id FROM message_reactions 
                WHERE message_id = ? AND user_id = ? AND reaction_type = ?
            ");
            $stmt->execute([$messageId, $userId, $reactionType]);
            $existing = $stmt->fetch();
            
            if ($existing) {
                // Remove existing reaction
                return $this->removeReaction($messageId, $userId, $reactionType);
            } else {
                // Add new reaction
                return $this->addReaction($messageId, $userId, $reactionType);
            }
            
        } catch (PDOException $e) {
            throw new \Exception("Failed to toggle reaction: " . $e->getMessage());
        }
    }
    
    /**
     * Get all reactions for a message
     */
    public function getMessageReactions($messageId) {
        try {
            $stmt = $this->db->prepare("
                SELECT mr.*, u.username, u.id as user_id
                FROM message_reactions mr
                JOIN users u ON mr.user_id = u.id
                WHERE mr.message_id = ?
                ORDER BY mr.created_at ASC
            ");
            $stmt->execute([$messageId]);
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            throw new \Exception("Failed to get message reactions: " . $e->getMessage());
        }
    }
    
    /**
     * Get reaction summary for multiple messages (counts by type)
     */
    public function getReactionSummary($messageIds) {
        if (empty($messageIds)) {
            return [];
        }
        
        try {
            $placeholders = str_repeat('?,', count($messageIds) - 1) . '?';
            $stmt = $this->db->prepare("
                SELECT message_id, reaction_type, COUNT(*) as count,
                       GROUP_CONCAT(user_id) as user_ids
                FROM message_reactions 
                WHERE message_id IN ($placeholders)
                GROUP BY message_id, reaction_type
                ORDER BY message_id, count DESC
            ");
            $stmt->execute($messageIds);
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            throw new \Exception("Failed to get reaction summary: " . $e->getMessage());
        }
    }
    
    /**
     * Get user's reactions for multiple messages
     */
    public function getUserReactionsForMessages($userId, $messageIds) {
        if (empty($messageIds)) {
            return [];
        }
        
        try {
            $placeholders = str_repeat('?,', count($messageIds) - 1) . '?';
            $stmt = $this->db->prepare("
                SELECT message_id, reaction_type
                FROM message_reactions 
                WHERE user_id = ? AND message_id IN ($placeholders)
            ");
            
            $params = array_merge([$userId], $messageIds);
            $stmt->execute($params);
            
            $reactions = [];
            while ($row = $stmt->fetch()) {
                if (!isset($reactions[$row['message_id']])) {
                    $reactions[$row['message_id']] = [];
                }
                $reactions[$row['message_id']][] = $row['reaction_type'];
            }
            
            return $reactions;
            
        } catch (PDOException $e) {
            throw new \Exception("Failed to get user reactions: " . $e->getMessage());
        }
    }
    
    /**
     * Get reaction emoji by type
     */
    public function getReactionEmoji($reactionType) {
        $flipped = array_flip(self::REACTIONS);
        return $flipped[$reactionType] ?? '❓';
    }
    
    /**
     * Get reaction type by emoji
     */
    public function getReactionType($emoji) {
        return self::REACTIONS[$emoji] ?? null;
    }
    
    /**
     * Check if user has reacted to a message
     */
    public function hasUserReacted($messageId, $userId, $reactionType = null) {
        try {
            if ($reactionType) {
                $stmt = $this->db->prepare("
                    SELECT COUNT(*) as count FROM message_reactions 
                    WHERE message_id = ? AND user_id = ? AND reaction_type = ?
                ");
                $stmt->execute([$messageId, $userId, $reactionType]);
            } else {
                $stmt = $this->db->prepare("
                    SELECT COUNT(*) as count FROM message_reactions 
                    WHERE message_id = ? AND user_id = ?
                ");
                $stmt->execute([$messageId, $userId]);
            }
            
            $result = $stmt->fetch();
            return $result['count'] > 0;
            
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Get total reaction count for a message
     */
    public function getTotalReactionCount($messageId) {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count FROM message_reactions 
                WHERE message_id = ?
            ");
            $stmt->execute([$messageId]);
            $result = $stmt->fetch();
            return $result['count'];
            
        } catch (PDOException $e) {
            return 0;
        }
    }
}
