<?php

namespace Src\Models;

/**
 * User Model
 */
class User extends Model
{
    protected string $table = 'users';

    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Find user by Google ID
     */
    public function findByGoogleId(string $googleId): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE google_id = :google_id");
        $stmt->execute(['google_id' => $googleId]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Create a new user
     */
    public function create(array $data): int
    {
        $requiredFields = ['email', 'full_name', 'date_of_birth', 'document_number', 'country', 'institution', 'category'];
        
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                throw new \InvalidArgumentException("Field {$field} is required");
            }
        }

        // Set default values
        $data['role'] = $data['role'] ?? 'user';
        $data['payment_status'] = $data['payment_status'] ?? 'pending';

        return $this->insert($data);
    }

    /**
     * Update payment status
     */
    public function updatePaymentStatus(int $userId, string $status): bool
    {
        if (!in_array($status, ['pending', 'approved', 'rejected'])) {
            throw new \InvalidArgumentException('Invalid payment status');
        }

        return $this->update($userId, ['payment_status' => $status]);
    }

    /**
     * Link Google account to existing user
     */
    public function linkGoogleAccount(int $userId, string $googleId): bool
    {
        return $this->update($userId, ['google_id' => $googleId]);
    }

    /**
     * Check if user is a moderator
     */
    public function isModerator(int $userId): bool
    {
        $user = $this->findById($userId);
        return $user && ($user['role'] === 'moderator' || $user['role'] === 'admin');
    }

    /**
     * Check if user is an admin
     */
    public function isAdmin(int $userId): bool
    {
        $user = $this->findById($userId);
        return $user && $user['role'] === 'admin';
    }

    /**
     * Get users by role
     */
    public function getByRole(string $role): array
    {
        return $this->findAll(['role' => $role]);
    }

    /**
     * Get pending payment approvals
     */
    public function getPendingPayments(): array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM {$this->table} 
             WHERE payment_status = 'pending' 
             AND payment_proof_path IS NOT NULL
             ORDER BY created_at DESC"
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
