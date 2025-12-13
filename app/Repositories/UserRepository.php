<?php

/**
 * User Repository
 */

namespace App\Repositories;

class UserRepository extends Repository
{
    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?object
    {
        return $this->findOneBy(['email' => $email]);
    }

    /**
     * Find active users
     */
    public function findActiveUsers(int $limit = 100): array
    {
        return $this->findBy(['status' => 'active'], ['createdAt' => 'DESC'], $limit);
    }

    /**
     * Find users by status
     */
    public function findByStatus(string $status): array
    {
        return $this->findBy(['status' => $status]);
    }

    /**
     * Search users by name or email
     */
    public function search(string $keyword): array
    {
        return $this->select()
            ->where('name', 'LIKE', "%{$keyword}%")
            ->orWhere('email', 'LIKE', "%{$keyword}%")
            ->fetchAll();
    }
}
