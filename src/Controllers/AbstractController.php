<?php

namespace Src\Controllers;

use Src\Models\AbstractModel;
use Src\Models\Area;
use Src\Models\User;
use Src\Services\AbstractService;
use Src\Services\EmailService;
use Src\Validators\FileUploadValidator;

/**
 * Abstract Controller - Handles abstract submissions
 */
class AbstractController
{
    private AbstractModel $abstractModel;
    private Area $areaModel;
    private User $userModel;
    private AbstractService $abstractService;
    private FileUploadValidator $fileValidator;
    private EmailService $emailService;

    public function __construct()
    {
        $this->abstractModel = new AbstractModel();
        $this->areaModel = new Area();
        $this->userModel = new User();
        $this->abstractService = new AbstractService();
        $this->fileValidator = new FileUploadValidator();
        $this->emailService = new EmailService();
    }

    /**
     * Submit a new abstract
     */
    public function submit(int $userId, array $data, array $uploadedFile): int
    {
        // Validate required fields
        if (empty($data['title']) || empty($data['area_id'])) {
            throw new \InvalidArgumentException('Título e área são obrigatórios');
        }

        // Validate area exists and is active
        $area = $this->areaModel->findById((int) $data['area_id']);
        if (!$area || !$area['is_active']) {
            throw new \Exception('Área inválida ou inativa');
        }

        // Validate and move uploaded file
        $config = require __DIR__ . '/../../config/app.php';
        $safeName = $this->fileValidator->validate($uploadedFile);
        $destinationPath = $config['upload']['abstracts_path'] . $safeName;

        if (!move_uploaded_file($uploadedFile['tmp_name'], $destinationPath)) {
            throw new \Exception('Erro ao salvar arquivo');
        }

        // Submit abstract
        return $this->abstractService->submitAbstract(
            $userId,
            (int) $data['area_id'],
            $data['title'],
            $destinationPath
        );
    }

    /**
     * Replace abstract file
     */
    public function replaceFile(int $abstractId, int $userId, array $uploadedFile): bool
    {
        // Validate and move uploaded file
        $config = require __DIR__ . '/../../config/app.php';
        $safeName = $this->fileValidator->validate($uploadedFile);
        $destinationPath = $config['upload']['abstracts_path'] . $safeName;

        if (!move_uploaded_file($uploadedFile['tmp_name'], $destinationPath)) {
            throw new \Exception('Erro ao salvar arquivo');
        }

        return $this->abstractService->replaceFile($abstractId, $userId, $destinationPath);
    }

    /**
     * Get user's abstracts
     */
    public function getUserAbstracts(int $userId): array
    {
        return $this->abstractModel->getByUser($userId);
    }

    /**
     * Get abstract details with history
     */
    public function getAbstractDetails(int $abstractId, int $requestingUserId): ?array
    {
        $abstract = $this->abstractModel->getWithDetails($abstractId);
        
        if (!$abstract) {
            return null;
        }

        // Check permissions
        $user = $this->userModel->findById($requestingUserId);
        $canView = false;

        // User can view their own abstracts
        if ($abstract['user_id'] === $requestingUserId) {
            $canView = true;
        }

        // Moderators can view abstracts in their areas
        if ($user && in_array($user['role'], ['moderator', 'admin'])) {
            $areaModeratorModel = new \Src\Models\AreaModerator();
            if ($areaModeratorModel->isModeratorOfArea($requestingUserId, $abstract['area_id'])) {
                $canView = true;
            }
        }

        // Admins can view all
        if ($user && $user['role'] === 'admin') {
            $canView = true;
        }

        if (!$canView) {
            throw new \Exception('Você não tem permissão para visualizar este resumo');
        }

        // Add history
        $abstract['history'] = (new \Src\Models\AbstractHistory())->getByAbstract($abstractId);

        return $abstract;
    }

    /**
     * Update abstract status (moderator action)
     */
    public function updateStatus(int $abstractId, string $newStatus, ?string $justification, int $moderatorId): bool
    {
        // Verify moderator can moderate this abstract
        if (!$this->abstractService->canModerate($moderatorId, $abstractId)) {
            throw new \Exception('Você não tem permissão para avaliar este resumo');
        }

        return $this->abstractService->updateStatus($abstractId, $newStatus, $justification, $moderatorId);
    }

    /**
     * Get abstracts for moderator (their areas only)
     */
    public function getModeratorAbstracts(int $moderatorId, ?string $status = null): array
    {
        if ($status) {
            return $this->abstractModel->getByModeratorAreas($moderatorId, $status);
        }

        // Return all abstracts from moderator's areas
        return $this->abstractModel->getByModeratorAreas($moderatorId);
    }

    /**
     * Get all abstracts (admin only)
     */
    public function getAllAbstracts(?string $status = null, ?int $areaId = null): array
    {
        return $this->abstractModel->getAll($status, $areaId);
    }

    /**
     * Delete an abstract (only if pending and owner)
     */
    public function delete(int $abstractId, int $userId): bool
    {
        $abstract = $this->abstractModel->getWithDetails($abstractId);

        if (!$abstract) {
            throw new \Exception('Resumo não encontrado');
        }

        // Only owner can delete
        if ($abstract['user_id'] !== $userId) {
            throw new \Exception('Você não tem permissão para excluir este resumo');
        }

        // Only pending abstracts can be deleted
        if ($abstract['status'] !== 'pending') {
            throw new \Exception('Apenas submissões pendentes podem ser excluídas');
        }

        // Delete file
        if (file_exists($abstract['file_path'])) {
            unlink($abstract['file_path']);
        }

        return $this->abstractModel->delete($abstractId);
    }
}
