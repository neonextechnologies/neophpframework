<?php

namespace NeoCore\System\Core;

use PDO;
use PDOException;

/**
 * Model - Base model class
 * 
 * No ORM magic. No relationships. No auto-casting.
 * Simple database operations only.
 */
abstract class Model
{
    protected PDO $db;
    protected string $table;
    protected string $primaryKey = 'id';

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Find record by primary key
     */
    public function find($id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Find all records
     */
    public function findAll(int $limit = 100, int $offset = 0): array
    {
        $sql = "SELECT * FROM {$this->table} LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find records by criteria
     */
    public function findWhere(array $criteria, int $limit = 100, int $offset = 0): array
    {
        $conditions = [];
        $params = [];
        
        foreach ($criteria as $field => $value) {
            $conditions[] = "$field = :$field";
            $params[$field] = $value;
        }
        
        $where = implode(' AND ', $conditions);
        $sql = "SELECT * FROM {$this->table} WHERE $where LIMIT :limit OFFSET :offset";
        
        $stmt = $this->db->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue(":$key", $value);
        }
        
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Find first record by criteria
     */
    public function findFirstWhere(array $criteria): ?array
    {
        $conditions = [];
        $params = [];
        
        foreach ($criteria as $field => $value) {
            $conditions[] = "$field = :$field";
            $params[$field] = $value;
        }
        
        $where = implode(' AND ', $conditions);
        $sql = "SELECT * FROM {$this->table} WHERE $where LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ?: null;
    }

    /**
     * Insert new record
     */
    public function insert(array $data): ?int
    {
        $fields = array_keys($data);
        $placeholders = array_map(fn($f) => ":$f", $fields);
        
        $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";
        
        $stmt = $this->db->prepare($sql);
        
        if ($stmt->execute($data)) {
            return (int)$this->db->lastInsertId();
        }
        
        return null;
    }

    /**
     * Update record by primary key
     */
    public function update($id, array $data): bool
    {
        $sets = [];
        foreach (array_keys($data) as $field) {
            $sets[] = "$field = :$field";
        }
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $sets) . 
               " WHERE {$this->primaryKey} = :id";
        
        $data['id'] = $id;
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute($data);
    }

    /**
     * Update records by criteria
     */
    public function updateWhere(array $criteria, array $data): bool
    {
        $sets = [];
        foreach (array_keys($data) as $field) {
            $sets[] = "$field = :set_$field";
        }
        
        $conditions = [];
        foreach (array_keys($criteria) as $field) {
            $conditions[] = "$field = :where_$field";
        }
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $sets) . 
               " WHERE " . implode(' AND ', $conditions);
        
        $params = [];
        foreach ($data as $key => $value) {
            $params["set_$key"] = $value;
        }
        foreach ($criteria as $key => $value) {
            $params["where_$key"] = $value;
        }
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Delete record by primary key
     */
    public function delete($id): bool
    {
        $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = :id";
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute(['id' => $id]);
    }

    /**
     * Delete records by criteria
     */
    public function deleteWhere(array $criteria): bool
    {
        $conditions = [];
        foreach (array_keys($criteria) as $field) {
            $conditions[] = "$field = :$field";
        }
        
        $sql = "DELETE FROM {$this->table} WHERE " . implode(' AND ', $conditions);
        $stmt = $this->db->prepare($sql);
        
        return $stmt->execute($criteria);
    }

    /**
     * Count records
     */
    public function count(array $criteria = []): int
    {
        if (empty($criteria)) {
            $sql = "SELECT COUNT(*) as count FROM {$this->table}";
            $stmt = $this->db->query($sql);
        } else {
            $conditions = [];
            foreach (array_keys($criteria) as $field) {
                $conditions[] = "$field = :$field";
            }
            
            $sql = "SELECT COUNT(*) as count FROM {$this->table} WHERE " . implode(' AND ', $conditions);
            $stmt = $this->db->prepare($sql);
            $stmt->execute($criteria);
        }
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)$result['count'];
    }

    /**
     * Execute raw query
     */
    public function query(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Execute raw statement (INSERT, UPDATE, DELETE)
     */
    public function execute(string $sql, array $params = []): bool
    {
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Begin transaction
     */
    public function beginTransaction(): bool
    {
        return $this->db->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit(): bool
    {
        return $this->db->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback(): bool
    {
        return $this->db->rollBack();
    }
}
