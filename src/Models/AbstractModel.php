<?php

namespace Src\Models;

/**
 * Abstract Model - Manages scientific abstracts/submissions
 */
class AbstractModel extends Model
{
    protected string $table = 'abstracts';

    /**
     * Create a new abstract submission
     */
    public function create(int $userId, int $areaId, string $title, string $filePath): int
    {
        return $this->insert([
            'user_id' => $userId,
            'area_id' => $areaId,
            'title' => $title,
            'file_path' => $filePath,
            'status' => 'pending'
        ]);
    }

    /**
     * Get abstract by ID with user and area details
     */
    public function getWithDetails(int $id): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT a.*, u.full_name as author_name, u.email as author_email, ar.name as area_name
             FROM {$this->table} a
             JOIN users u ON a.user_id = u.id
             JOIN areas ar ON a.area_id = ar.id
             WHERE a.id = :id"
        );
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Get all abstracts for a user
     */
    public function getByUser(int $userId): array
    {
        $stmt = $this->db->prepare(
            "SELECT a.*, ar.name as area_name
             FROM {$this->table} a
             JOIN areas ar ON a.area_id = ar.id
             WHERE a.user_id = :user_id
             ORDER BY a.submitted_at DESC"
        );
        $stmt->execute(['user_id' => $userId]);
        return $stmt->fetchAll();
    }

    /**
     * Count active abstracts for a user (excludes rejected)
     */
    public function countActiveByUser(int $userId): int
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) as total 
             FROM {$this->table} 
             WHERE user_id = :user_id 
             AND status != 'rejected'"
        );
        $stmt->execute(['user_id' => $userId]);
        $result = $stmt->fetch();
        return (int) ($result['total'] ?? 0);
    }

    /**
     * Get abstracts for a moderator (their assigned areas only)
     */
    public function getByModeratorAreas(int $userId, ?string $status = null): array
    {
        $sql = "
            SELECT DISTINCT a.*, u.full_name as author_name, u.email as author_email, ar.name as area_name
            FROM {$this->table} a
            JOIN users u ON a.user_id = u.id
            JOIN areas ar ON a.area_id = ar.id
            JOIN area_moderators am ON ar.id = am.area_id
            WHERE am.user_id = :user_id
        ";

        $params = ['user_id' => $userId];

        if ($status !== null) {
            $sql .= " AND a.status = :status";
            $params['status'] = $status;
        }

        $sql .= " ORDER BY a.submitted_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Get all abstracts (admin view)
     */
    public function getAll(?string $status = null, ?int $areaId = null): array
    {
        $sql = "
            SELECT a.*, u.full_name as author_name, u.email as author_email, ar.name as area_name
            FROM {$this->table} a
            JOIN users u ON a.user_id = u.id
            JOIN areas ar ON a.area_id = ar.id
            WHERE 1=1
        ";

        $params = [];

        if ($status !== null) {
            $sql .= " AND a.status = :status";
            $params['status'] = $status;
        }

        if ($areaId !== null) {
            $sql .= " AND a.area_id = :area_id";
            $params['area_id'] = $areaId;
        }

        $sql .= " ORDER BY a.submitted_at DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Update abstract status
     */
    public function updateStatus(int $id, string $status, ?string $reason = null): bool
    {
        $data = ['status' => $status];

        if ($status === 'rejected') {
            $data['rejection_reason'] = $reason;
        } elseif ($status === 'accepted_with_corrections') {
            $data['correction_notes'] = $reason;
        }

        return $this->update($id, $data);
    }

    /**
     * Replace abstract file (only allowed for pending or accepted_with_corrections status)
     */
    public function replaceFile(int $id, string $newFilePath): bool
    {
        $abstract = $this->findById($id);
        if (!$abstract) {
            return false;
        }

        // Only allow replacement if status is pending or accepted_with_corrections or pending_revision
        if (!in_array($abstract['status'], ['pending', 'accepted_with_corrections', 'pending_revision'])) {
            throw new \Exception('File replacement not allowed for this status');
        }

        // Delete old file if exists
        if (file_exists($abstract['file_path'])) {
            unlink($abstract['file_path']);
        }

        return $this->update($id, ['file_path' => $newFilePath]);
    }

    /**
     * Check if user can submit more abstracts
     */
    public function canUserSubmitMore(int $userId, int $maxAllowed = 2): bool
    {
        $activeCount = $this->countActiveByUser($userId);
        return $activeCount < $maxAllowed;
    }

    /**
     * Get abstracts by area
     */
    public function getByArea(int $areaId): array
    {
        $stmt = $this->db->prepare(
            "SELECT a.*, u.full_name as author_name, u.email as author_email
             FROM {$this->table} a
             JOIN users u ON a.user_id = u.id
             WHERE a.area_id = :area_id
             ORDER BY a.submitted_at DESC"
        );
        $stmt->execute(['area_id' => $areaId]);
        return $stmt->fetchAll();
    }

    /**
     * Get abstracts pending moderation for a specific moderator's areas
     */
    public function getPendingForModerator(int $userId): array
    {
        return $this->getByModeratorAreas($userId, 'pending');
    }

    /**
     * Get abstracts pending revision for a specific moderator's areas
     */
    public function getRevisionForModerator(int $userId): array
    {
        return $this->getByModeratorAreas($userId, 'pending_revision');
    }
}
