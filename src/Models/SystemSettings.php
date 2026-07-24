<?php

namespace Src\Models;

/**
 * SystemSettings Model - Manages system-wide configuration
 */
class SystemSettings extends Model
{
    protected string $table = 'system_settings';

    /**
     * Get a setting value by key
     */
    public function get(string $key, $default = null)
    {
        $setting = $this->findByKey($key);
        return $setting ? $setting['setting_value'] : $default;
    }

    /**
     * Find setting by key
     */
    public function findByKey(string $key): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE setting_key = :key");
        $stmt->execute(['key' => $key]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Set or update a setting value
     */
    public function set(string $key, string $value, ?string $description = null): int
    {
        $existing = $this->findByKey($key);

        if ($existing) {
            $data = ['setting_value' => $value];
            if ($description !== null) {
                $data['description'] = $description;
            }
            $this->update($existing['id'], $data);
            return $existing['id'];
        }

        return $this->insert([
            'setting_key' => $key,
            'setting_value' => $value,
            'description' => $description
        ]);
    }

    /**
     * Get submission deadline
     */
    public function getSubmissionDeadline(): ?string
    {
        return $this->get('submission_deadline');
    }

    /**
     * Set submission deadline
     */
    public function setSubmissionDeadline(string $deadline): void
    {
        $this->set('submission_deadline', $deadline, 'Deadline for abstract submissions');
    }

    /**
     * Check if submissions are still open
     */
    public function isSubmissionOpen(): bool
    {
        $deadline = $this->getSubmissionDeadline();
        if (!$deadline) {
            return true; // No deadline set means always open
        }

        return new \DateTime() < new \DateTime($deadline);
    }

    /**
     * Get max abstracts per user
     */
    public function getMaxAbstractsPerUser(): int
    {
        return (int) $this->get('max_abstracts_per_user', 2);
    }

    /**
     * Get all settings
     */
    public function getAll(): array
    {
        return $this->findAll([], ['setting_key' => 'ASC']);
    }
}
