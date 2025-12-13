<?php

/**
 * Base Entity Class
 * 
 * Example entity using Cycle ORM annotations
 */

namespace App\Entities;

use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Table;

#[Entity]
#[Table(name: 'users')]
class User
{
    #[Column(type: 'primary')]
    public ?int $id = null;

    #[Column(type: 'string', nullable: false)]
    public string $name;

    #[Column(type: 'string', nullable: false)]
    public string $email;

    #[Column(type: 'string', nullable: false)]
    public string $password;

    #[Column(type: 'string', default: 'active')]
    public string $status = 'active';

    #[Column(type: 'datetime', nullable: true)]
    public ?\DateTimeImmutable $lastLogin = null;

    #[Column(type: 'datetime')]
    public \DateTimeImmutable $createdAt;

    #[Column(type: 'datetime', nullable: true)]
    public ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    /**
     * Set password (hashed)
     */
    public function setPassword(string $password): void
    {
        $this->password = password_hash($password, PASSWORD_BCRYPT);
    }

    /**
     * Verify password
     */
    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->password);
    }

    /**
     * Check if user is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Update last login timestamp
     */
    public function updateLastLogin(): void
    {
        $this->lastLogin = new \DateTimeImmutable();
    }
}
