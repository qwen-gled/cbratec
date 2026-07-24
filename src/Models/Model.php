<?php

namespace Src\Models;

use Src\Helpers\Database;
use PDO;

/**
 * Base Model - Provides common database operations
 */
abstract class Model
{
    protected PDO $db;
    protected string $table;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Find a record by ID
     */
    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = :id");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Get all records with optional conditions
     */
    public function findAll(array $conditions = [], array $orderBy = []): array
    {
        $sql = "SELECT * FROM {$this->table}";
        
        if (!empty($conditions)) {
            $where = [];
            foreach ($conditions as $key => $value) {
                $where[] = "$key = :$key";
            }
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        if (!empty($orderBy)) {
            $order = [];
            foreach ($orderBy as $column => $direction) {
                $order[] = "$column $direction";
            }
            $sql .= " ORDER BY " . implode(', ', $order);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($conditions);
        return $stmt->fetchAll();
    }

    /**
     * Insert a new record
     */
    public function insert(array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO {$this->table} ($columns) VALUES ($placeholders)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
        
        return (int) $this->db->lastInsertId();
    }

    /**
     * Update a record by ID
     */
    public function update(int $id, array $data): bool
    {
        $set = [];
        foreach ($data as $key => $value) {
            $set[] = "$key = :$key";
        }
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $set) . " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $data['id'] = $id;
        
        return $stmt->execute($data);
    }

    /**
     * Delete a record by ID
     */
    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Count records with optional conditions
     */
    public function count(array $conditions = []): int
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        
        if (!empty($conditions)) {
            $where = [];
            foreach ($conditions as $key => $value) {
                $where[] = "$key = :$key";
            }
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($conditions);
        $result = $stmt->fetch();
        
        return (int) ($result['total'] ?? 0);
    }
}
