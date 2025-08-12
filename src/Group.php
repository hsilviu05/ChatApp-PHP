<?php
namespace ChatApp;

use PDO;
use PDOException;

class Group {
    private $db;
    
    public function __construct() {
        $this->db = Db::getInstance()->getConnection();
    }
    
    /**
     * Create a new group
     */
    public function createGroup($name, $createdBy, $memberIds = []) {
        try {
            $this->db->beginTransaction();
            
            // Create the group
            $stmt = $this->db->prepare("
                INSERT INTO `groups` (name, created_by) 
                VALUES (?, ?)
            ");
            $stmt->execute([$name, $createdBy]);
            $groupId = $this->db->lastInsertId();
            
            // Add creator as member
            $this->addMember($groupId, $createdBy);
            
            // Add other members
            foreach ($memberIds as $memberId) {
                if ($memberId != $createdBy) {
                    $this->addMember($groupId, $memberId);
                }
            }
            
            $this->db->commit();
            return $groupId;
            
        } catch (PDOException $e) {
            $this->db->rollBack();
            throw new \Exception("Failed to create group: " . $e->getMessage());
        }
    }
    
    /**
     * Add a member to a group
     */
    public function addMember($groupId, $userId) {
        try {
            $stmt = $this->db->prepare("
                INSERT IGNORE INTO group_members (group_id, user_id) 
                VALUES (?, ?)
            ");
            return $stmt->execute([$groupId, $userId]);
        } catch (PDOException $e) {
            throw new \Exception("Failed to add member: " . $e->getMessage());
        }
    }
    
    /**
     * Remove a member from a group
     */
    public function removeMember($groupId, $userId) {
        try {
            $stmt = $this->db->prepare("
                DELETE FROM group_members 
                WHERE group_id = ? AND user_id = ?
            ");
            return $stmt->execute([$groupId, $userId]);
        } catch (PDOException $e) {
            throw new \Exception("Failed to remove member: " . $e->getMessage());
        }
    }
    
    /**
     * Get all groups for a user
     */
    public function getUserGroups($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT g.*, gm.joined_at, 
                       (SELECT COUNT(*) FROM group_members WHERE group_id = g.id) as member_count,
                       (SELECT username FROM users WHERE id = g.created_by) as creator_name
                FROM `groups` g
                JOIN group_members gm ON g.id = gm.group_id
                WHERE gm.user_id = ?
                ORDER BY g.created_at DESC
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new \Exception("Failed to get user groups: " . $e->getMessage());
        }
    }
    
    /**
     * Get group details
     */
    public function getGroup($groupId) {
        try {
            $stmt = $this->db->prepare("
                SELECT g.*, 
                       (SELECT COUNT(*) FROM group_members WHERE group_id = g.id) as member_count,
                       (SELECT username FROM users WHERE id = g.created_by) as creator_name
                FROM `groups` g
                WHERE g.id = ?
            ");
            $stmt->execute([$groupId]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            throw new \Exception("Failed to get group: " . $e->getMessage());
        }
    }
    
    /**
     * Get group members
     */
    public function getGroupMembers($groupId) {
        try {
            $stmt = $this->db->prepare("
                SELECT u.id, u.username, u.created_at, gm.joined_at
                FROM users u
                JOIN group_members gm ON u.id = gm.user_id
                WHERE gm.group_id = ?
                ORDER BY gm.joined_at ASC
            ");
            $stmt->execute([$groupId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new \Exception("Failed to get group members: " . $e->getMessage());
        }
    }
    
    /**
     * Check if user is member of group
     */
    public function isMember($groupId, $userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count
                FROM group_members 
                WHERE group_id = ? AND user_id = ?
            ");
            $stmt->execute([$groupId, $userId]);
            $result = $stmt->fetch();
            return $result['count'] > 0;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Update group name
     */
    public function updateGroupName($groupId, $newName, $userId) {
        try {
            // Check if user is creator
            $group = $this->getGroup($groupId);
            if (!$group || $group['created_by'] != $userId) {
                throw new \Exception("Only group creator can update group name");
            }
            
            $stmt = $this->db->prepare("
                UPDATE `groups` 
                SET name = ? 
                WHERE id = ?
            ");
            return $stmt->execute([$newName, $groupId]);
        } catch (PDOException $e) {
            throw new \Exception("Failed to update group: " . $e->getMessage());
        }
    }
    
    /**
     * Delete group (only creator can do this)
     */
    public function deleteGroup($groupId, $userId) {
        try {
            // Check if user is creator
            $group = $this->getGroup($groupId);
            if (!$group || $group['created_by'] != $userId) {
                throw new \Exception("Only group creator can delete group");
            }
            
            $this->db->beginTransaction();
            
            // Delete group members
            $stmt = $this->db->prepare("DELETE FROM group_members WHERE group_id = ?");
            $stmt->execute([$groupId]);
            
            // Delete group
            $stmt = $this->db->prepare("DELETE FROM `groups` WHERE id = ?");
            $stmt->execute([$groupId]);
            
            $this->db->commit();
            return true;
            
        } catch (PDOException $e) {
            $this->db->rollBack();
            throw new \Exception("Failed to delete group: " . $e->getMessage());
        }
    }
    
    /**
     * Get all available groups for a user to join
     */
    public function getAvailableGroups($userId) {
        try {
            $stmt = $this->db->prepare("
                SELECT g.*, 
                       (SELECT COUNT(*) FROM group_members WHERE group_id = g.id) as member_count,
                       (SELECT username FROM users WHERE id = g.created_by) as creator_name
                FROM `groups` g
                WHERE g.id NOT IN (
                    SELECT group_id FROM group_members WHERE user_id = ?
                )
                ORDER BY g.created_at DESC
            ");
            $stmt->execute([$userId]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            throw new \Exception("Failed to get available groups: " . $e->getMessage());
        }
    }
}
