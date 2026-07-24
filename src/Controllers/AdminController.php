<?php

namespace Src\Controllers;

use Src\Models\User;
use Src\Models\Area;
use Src\Models\AreaModerator;
use Src\Models\SystemSettings;
use Src\Services\EmailService;

/**
 * Admin Controller - Administrative operations
 */
class AdminController
{
    private User $userModel;
    private Area $areaModel;
    private AreaModerator $areaModeratorModel;
    private SystemSettings $settings;
    private EmailService $emailService;

    public function __construct()
    {
        $this->userModel = new User();
        $this->areaModel = new Area();
        $this->areaModeratorModel = new AreaModerator();
        $this->settings = new SystemSettings();
        $this->emailService = new EmailService();
    }

    /**
     * Get all users
     */
    public function getAllUsers(?string $role = null): array
    {
        if ($role) {
            return $this->userModel->getByRole($role);
        }
        return $this->userModel->findAll();
    }

    /**
     * Get pending payment approvals
     */
    public function getPendingPayments(): array
    {
        return $this->userModel->getPendingPayments();
    }

    /**
     * Approve or reject payment
     */
    public function processPayment(int $userId, string $status): bool
    {
        if (!in_array($status, ['approved', 'rejected'])) {
            throw new \InvalidArgumentException('Status inválido');
        }

        $this->userModel->updatePaymentStatus($userId, $status);

        // Send notification email
        $user = $this->userModel->findById($userId);
        $this->emailService->sendPaymentNotification($user['email'], $user['full_name'], $status);

        return true;
    }

    /**
     * Get all areas
     */
    public function getAllAreas(): array
    {
        return $this->areaModel->findAll([], ['name' => 'ASC']);
    }

    /**
     * Create a new area
     */
    public function createArea(string $name, ?string $description = null): int
    {
        if (empty($name)) {
            throw new \InvalidArgumentException('Nome da área é obrigatório');
        }

        return $this->areaModel->insert([
            'name' => $name,
            'description' => $description,
            'is_active' => 1
        ]);
    }

    /**
     * Update an area
     */
    public function updateArea(int $areaId, array $data): bool
    {
        $allowedFields = ['name', 'description', 'is_active'];
        $updateData = [];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }

        if (empty($updateData)) {
            throw new \InvalidArgumentException('Nenhum campo válido para atualização');
        }

        return $this->areaModel->update($areaId, $updateData);
    }

    /**
     * Delete an area
     */
    public function deleteArea(int $areaId): bool
    {
        // Check if area has abstracts
        $abstractModel = new \Src\Models\AbstractModel();
        $abstracts = $abstractModel->getByArea($areaId);

        if (!empty($abstracts)) {
            throw new \Exception('Não é possível excluir uma área com submissões vinculadas');
        }

        return $this->areaModel->delete($areaId);
    }

    /**
     * Assign moderator to area
     */
    public function assignModerator(int $areaId, int $userId): int
    {
        // Verify user exists and is moderator or can be moderator
        $user = $this->userModel->findById($userId);
        if (!$user) {
            throw new \Exception('Usuário não encontrado');
        }

        // Optionally upgrade user role to moderator
        if ($user['role'] === 'user') {
            $this->userModel->update($userId, ['role' => 'moderator']);
        }

        return $this->areaModeratorModel->assign($areaId, $userId);
    }

    /**
     * Remove moderator from area
     */
    public function removeModerator(int $areaId, int $userId): bool
    {
        return $this->areaModeratorModel->remove($areaId, $userId);
    }

    /**
     * Get all moderator assignments
     */
    public function getModeratorAssignments(): array
    {
        return $this->areaModeratorModel->getAllAssignments();
    }

    /**
     * Get system settings
     */
    public function getSettings(): array
    {
        return $this->settings->getAll();
    }

    /**
     * Update submission deadline
     */
    public function setSubmissionDeadline(string $deadline): void
    {
        // Validate date format
        $date = \DateTime::createFromFormat('Y-m-d H:i:s', $deadline);
        if (!$date) {
            throw new \InvalidArgumentException('Formato de data inválido. Use YYYY-MM-DD HH:MM:SS');
        }

        $this->settings->setSubmissionDeadline($deadline);
    }

    /**
     * Get dashboard statistics
     */
    public function getDashboardStats(): array
    {
        $db = \Src\Helpers\Database::getInstance()->getConnection();

        // Total users by role
        $usersByRole = $db->query(
            "SELECT role, COUNT(*) as count FROM users GROUP BY role"
        )->fetchAll();

        // Abstracts by status
        $abstractsByStatus = $db->query(
            "SELECT status, COUNT(*) as count FROM abstracts GROUP BY status"
        )->fetchAll();

        // Total areas
        $totalAreas = $this->areaModel->count();

        // Pending payments
        $pendingPayments = $this->userModel->count(['payment_status' => 'pending']);

        return [
            'users_by_role' => $usersByRole,
            'abstracts_by_status' => $abstractsByStatus,
            'total_areas' => $totalAreas,
            'pending_payments' => $pendingPayments
        ];
    }

    /**
     * Promote user to moderator
     */
    public function promoteToModerator(int $userId): bool
    {
        $user = $this->userModel->findById($userId);
        if (!$user) {
            throw new \Exception('Usuário não encontrado');
        }

        if ($user['role'] !== 'user') {
            throw new \Exception('Usuário já possui outra função');
        }

        return $this->userModel->update($userId, ['role' => 'moderator']);
    }

    /**
     * Demote moderator to user
     */
    public function demoteToUser(int $userId): bool
    {
        $user = $this->userModel->findById($userId);
        if (!$user) {
            throw new \Exception('Usuário não encontrado');
        }

        if ($user['role'] !== 'moderator') {
            throw new \Exception('Usuário não é moderador');
        }

        // Remove all moderator assignments
        $assignments = $this->areaModeratorModel->getAreasByModerator($userId);
        foreach ($assignments as $area) {
            $this->areaModeratorModel->remove($area['id'], $userId);
        }

        return $this->userModel->update($userId, ['role' => 'user']);
    }
}
