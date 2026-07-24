<?php

namespace Src\Models;

/**
 * AreaModerator Model - Manages moderator assignments to areas
 */
class AreaModerator extends Model
{
    protected string $table = 'area_moderators';

    /**
     * Assign a moderator to an area
     */
    public function assign(int $areaId, int $userId): int
    {
        // Check if already assigned
        $existing = $this->findByAreaAndUser($areaId, $userId);
        if ($existing) {
            throw new \Exception('Moderator already assigned to this area');
        }

        return $this->insert(['area_id' => $areaId, 'user_id' => $userId]);
    }

    /**
     * Remove a moderator from an area
     */
    public function remove(int $areaId, int $userId): bool
    {
        $assignment = $this->findByAreaAndUser($areaId, $userId);
        if (!$assignment) {
            return false;
        }

        return $this->delete($assignment['id']);
    }

    /**
     * Find assignment by area and user
     */
    public function findByAreaAndUser(int $areaId, int $userId): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} 
             WHERE area_id = :area_id AND user_id = :user_id"
        );
        $stmt->execute(['area_id' => $areaId, 'user_id' => $userId]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Get all moderators for an area
     */
    public function getModeratorsByArea(int $areaId): array
    {
        $stmt = $this->db->prepare(
            "SELECT u.id, u.email, u.full_name, u.created_at as assigned_at
             FROM {$this->table} am
             JOIN users u ON am.user_id = u.id
             WHERE am.area_id = :area_id"
        );
        $stmt->execute(['area_id' => $areaId]);
        return $stmt->fetchAll();
    }

    /**
     * Get all areas for a moderator
     */
    public function getAreasByModerator(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT a.*
             FROM {$this->table} am
             JOIN areas a ON am.area_id = a.id
             WHERE am.user_id = :user_id AND a.is_active = 1"
        );
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Check if user is moderator of an area
     */
    public function isModeratorOfArea(int $userId, int $areaId): bool
    {
        $assignment = $this->findByAreaAndUser($areaId, $userId);
        return $assignment !== null;
    }

    /**
     * Get all area assignments with details
     */
    public function getAllAssignments(): array
    {
        $stmt = $this->db->query(
            "SELECT am.id, a.name as area_name, u.full_name as moderator_name, u.email as moderator_email, am.created_at
             FROM {$this->table} am
             JOIN areas a ON am.area_id = a.id
             JOIN users u ON am.user_id = u.id
             ORDER BY a.name, u.full_name"
        );
        return $stmt->fetchAll();
    }
}
