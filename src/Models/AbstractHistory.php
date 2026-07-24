<?php

namespace Src\Models;

/**
 * AbstractHistory Model - Tracks all status changes for abstracts
 */
class AbstractHistory extends Model
{
    protected string $table = 'abstract_history';

    /**
     * Record a status change
     */
    public function record(int $abstractId, ?string $previousStatus, string $newStatus, 
                          ?string $justification, int $changedByUserId): int
    {
        return $this->insert([
            'abstract_id' => $abstractId,
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
            'justification' => $justification,
            'changed_by_user_id' => $changedByUserId
        ]);
    }

    /**
     * Get full history for an abstract with user details
     */
    public function getByAbstract(int $abstractId): array
    {
        $stmt = $this->db->prepare(
            "SELECT ah.*, u.full_name as changed_by_name, u.email as changed_by_email
             FROM {$this->table} ah
             JOIN users u ON ah.changed_by_user_id = u.id
             WHERE ah.abstract_id = :abstract_id
             ORDER BY ah.changed_at ASC"
        );
        $stmt->execute(['abstract_id' => $abstractId]);
        return $stmt->fetchAll();
    }

    /**
     * Get recent history across all abstracts (for admin dashboard)
     */
    public function getRecent(int $limit = 50): array
    {
        $stmt = $this->db->prepare(
            "SELECT ah.*, a.title as abstract_title, u.full_name as changed_by_name, 
                    author.full_name as author_name
             FROM {$this->table} ah
             JOIN abstracts a ON ah.abstract_id = a.id
             JOIN users u ON ah.changed_by_user_id = u.id
             JOIN users author ON a.user_id = author.id
             ORDER BY ah.changed_at DESC
             LIMIT :limit"
        );
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Get history for abstracts by a specific user (as author or changer)
     */
    public function getByUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT DISTINCT ah.*, a.title as abstract_title
             FROM {$this->table} ah
             JOIN abstracts a ON ah.abstract_id = a.id
             WHERE ah.changed_by_user_id = :user_id OR a.user_id = :user_id
             ORDER BY ah.changed_at DESC"
        );
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }
}
