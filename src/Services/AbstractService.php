<?php

namespace Src\Services;

use Src\Models\AbstractModel;
use Src\Models\AbstractHistory;
use Src\Models\SystemSettings;
use Src\Services\EmailService;

/**
 * Abstract Service - Business logic for abstract management
 */
class AbstractService
{
    private AbstractModel $abstractModel;
    private AbstractHistory $historyModel;
    private SystemSettings $settings;
    private EmailService $emailService;

    public function __construct()
    {
        $this->abstractModel = new AbstractModel();
        $this->historyModel = new AbstractHistory();
        $this->settings = new SystemSettings();
        $this->emailService = new EmailService();
    }

    /**
     * Submit a new abstract
     */
    public function submitAbstract(
        int $userId,
        int $areaId,
        string $title,
        string $filePath
    ): int {
        // Check if submissions are open
        if (!$this->settings->isSubmissionOpen()) {
            throw new \Exception('O prazo para submissões já encerrou');
        }

        // Check if user can submit more abstracts
        $maxAllowed = $this->settings->getMaxAbstractsPerUser();
        if (!$this->abstractModel->canUserSubmitMore($userId, $maxAllowed)) {
            throw new \Exception("Você já atingiu o limite máximo de {$maxAllowed} submissões");
        }

        // Create the abstract
        $abstractId = $this->abstractModel->create($userId, $areaId, $title, $filePath);

        // Record history
        $this->historyModel->record(
            $abstractId,
            null,
            'pending',
            'Submissão inicial',
            $userId
        );

        return $abstractId;
    }

    /**
     * Replace abstract file (for pending or accepted_with_corrections status)
     */
    public function replaceFile(int $abstractId, int $userId, string $newFilePath): bool
    {
        $abstract = $this->abstractModel->getWithDetails($abstractId);
        
        if (!$abstract) {
            throw new \Exception('Resumo não encontrado');
        }

        // Only owner can replace
        if ($abstract['user_id'] !== $userId) {
            throw new \Exception('Você não tem permissão para modificar este resumo');
        }

        // Check status allows replacement
        if (!in_array($abstract['status'], ['pending', 'accepted_with_corrections', 'pending_revision'])) {
            throw new \Exception('Não é possível substituir o arquivo com o status atual');
        }

        // If replacing a "accepted_with_corrections" abstract, change status to "pending_revision"
        $previousStatus = $abstract['status'];
        $newStatus = $previousStatus === 'accepted_with_corrections' ? 'pending_revision' : $previousStatus;

        // Replace file
        $this->abstractModel->replaceFile($abstractId, $newFilePath);

        // Update status if needed
        if ($newStatus !== $previousStatus) {
            $this->abstractModel->updateStatus($abstractId, $newStatus);
        }

        // Record history
        $this->historyModel->record(
            $abstractId,
            $previousStatus,
            $newStatus === $previousStatus ? $previousStatus : $newStatus,
            $newStatus === 'pending_revision' ? 'Arquivo reenviado para revisão' : 'Arquivo substituído pelo autor',
            $userId
        );

        return true;
    }

    /**
     * Update abstract status (moderator action)
     */
    public function updateStatus(
        int $abstractId,
        string $newStatus,
        ?string $justification,
        int $moderatorId
    ): bool {
        $abstract = $this->abstractModel->getWithDetails($abstractId);
        
        if (!$abstract) {
            throw new \Exception('Resumo não encontrado');
        }

        // Validate status transitions
        $this->validateStatusTransition($abstract['status'], $newStatus, $justification);

        // Store previous status
        $previousStatus = $abstract['status'];

        // Update abstract status
        $this->abstractModel->updateStatus($abstractId, $newStatus, $justification);

        // Record history
        $this->historyModel->record(
            $abstractId,
            $previousStatus,
            $newStatus,
            $justification,
            $moderatorId
        );

        // Send email notification
        $this->emailService->sendAbstractStatusNotification(
            $abstract['author_email'],
            $abstract['author_name'],
            $abstract['title'],
            $newStatus,
            $justification
        );

        return true;
    }

    /**
     * Validate status transition and requirements
     */
    private function validateStatusTransition(string $currentStatus, string $newStatus, ?string $justification): void
    {
        $validStatuses = ['pending', 'accepted', 'rejected', 'accepted_with_corrections', 'pending_revision'];
        
        if (!in_array($newStatus, $validStatuses)) {
            throw new \Exception('Status inválido');
        }

        // Rejected and accepted_with_corrections require justification
        if (in_array($newStatus, ['rejected', 'accepted_with_corrections']) && empty($justification)) {
            throw new \Exception('Justificativa é obrigatória para recusar ou solicitar correções');
        }

        // Can only accept/reject from pending or pending_revision
        if (in_array($newStatus, ['accepted', 'rejected']) && !in_array($currentStatus, ['pending', 'pending_revision'])) {
            throw new \Exception('Não é possível alterar para este status a partir do status atual');
        }
    }

    /**
     * Get abstract with full history
     */
    public function getAbstractWithHistory(int $abstractId): ?array
    {
        $abstract = $this->abstractModel->getWithDetails($abstractId);
        if (!$abstract) {
            return null;
        }

        $abstract['history'] = $this->historyModel->getByAbstract($abstractId);
        return $abstract;
    }

    /**
     * Check if user can moderate an abstract
     */
    public function canModerate(int $moderatorId, int $abstractId): bool
    {
        $abstract = $this->abstractModel->getWithDetails($abstractId);
        if (!$abstract) {
            return false;
        }

        // Moderators cannot moderate their own abstracts
        if ($abstract['user_id'] === $moderatorId) {
            return false;
        }

        // Check if moderator is assigned to this area
        $areaModeratorModel = new \Src\Models\AreaModerator();
        return $areaModeratorModel->isModeratorOfArea($moderatorId, $abstract['area_id']);
    }
}
