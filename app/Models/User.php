<?php

/**
 * Example User Model
 * 
 * This file demonstrates how to create a model in NeoCore.
 * Models extend the base Model class and use explicit PDO queries.
 */

namespace App\Models;

use NeoCore\System\Core\Model;
use PDO;

class User extends Model
{
    protected string $table = 'users';
    protected string $primaryKey = 'id';

    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?array
    {
        return $this->findWhere(['email' => $email]);
    }

    /**
     * Get active users
     */
    public function getActiveUsers(): array
    {
        $sql = "SELECT * FROM {$this->table} WHERE status = :status ORDER BY created_at DESC";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['status' => 'active']);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Create new user
     */
    public function createUser(array $data): ?string
    {
        // Validate data before insert
        $requiredFields = ['name', 'email', 'password'];
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                return null;
            }
        }

        // Hash password
        $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        $data['created_at'] = date('Y-m-d H:i:s');

        return $this->insert($data);
    }

    /**
     * Update user password
     */
    public function updatePassword(int $userId, string $newPassword): bool
    {
        $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT);
        return $this->update($userId, ['password' => $hashedPassword]);
    }

    /**
     * Delete inactive users
     */
    public function deleteInactiveUsers(int $daysInactive = 30): int
    {
        $date = date('Y-m-d', strtotime("-{$daysInactive} days"));
        $sql = "DELETE FROM {$this->table} WHERE last_login < :date AND status = 'inactive'";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute(['date' => $date]);
        return $stmt->rowCount();
    }
}
