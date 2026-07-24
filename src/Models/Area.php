<?php

namespace Src\Models;

/**
 * Area Model
 */
class Area extends Model
{
    protected string $table = 'areas';

    /**
     * Get all active areas
     */
    public function getActiveAreas(): array
    {
        return $this->findAll(['is_active' => 1], ['name' => 'ASC']);
    }

    /**
     * Get area by ID with moderator count
     */
    public function getWithModeratorCount(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT a.*, COUNT(am.user_id) as moderator_count
             FROM {$this->table} a
             LEFT JOIN area_moderators am ON a.id = am.area_id
             WHERE a.id = :id
             GROUP BY a.id"
        );
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Toggle area active status
     */
    public function toggleStatus(int $id): bool
    {
        $area = $this->findById($id);
        if (!$area) {
            return false;
        }

        $newStatus = $area['is_active'] ? 0 : 1;
        return $this->update($id, ['is_active' => $newStatus]);
    }
}
