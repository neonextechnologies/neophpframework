<?php

/**
 * Base Entity Class
 * 
 * Example entity using Cycle ORM annotations
 */

namespace App\Entities;

use NeoCore\Auth\Authenticatable;
use NeoCore\Auth\MustVerifyEmail;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Table;
use Cycle\Annotated\Annotation\Relation\ManyToMany;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[Entity]
#[Table(name: 'users')]
class User implements Authenticatable, MustVerifyEmail
{
    #[Column(type: 'primary')]
    public ?int $id = null;

    #[Column(type: 'string', nullable: false)]
    public string $name;

    #[Column(type: 'string', nullable: false)]
    public string $email;

    #[Column(type: 'string', nullable: false)]
    public string $password;

    #[Column(type: 'string', nullable: true)]
    public ?string $remember_token = null;

    #[Column(type: 'datetime', nullable: true)]
    public ?\DateTimeImmutable $email_verified_at = null;

    #[Column(type: 'text', nullable: true)]
    public ?string $two_factor_secret = null;

    #[Column(type: 'text', nullable: true)]
    public ?string $two_factor_recovery_codes = null;

    #[Column(type: 'string', default: 'active')]
    public string $status = 'active';

    #[Column(type: 'datetime', nullable: true)]
    public ?\DateTimeImmutable $lastLogin = null;

    #[Column(type: 'datetime')]
    public \DateTimeImmutable $createdAt;

    #[Column(type: 'datetime', nullable: true)]
    public ?\DateTimeImmutable $updatedAt = null;

    #[ManyToMany(target: Role::class, through: 'user_roles')]
    public Collection $roles;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->roles = new ArrayCollection();
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

    // Authenticatable Interface Implementation

    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    public function getAuthIdentifier(): mixed
    {
        return $this->id;
    }

    public function getAuthPassword(): ?string
    {
        return $this->password;
    }

    public function getRememberToken(): ?string
    {
        return $this->remember_token;
    }

    public function setRememberToken(string $value): void
    {
        $this->remember_token = $value;
    }

    public function getRememberTokenName(): string
    {
        return 'remember_token';
    }

    // MustVerifyEmail Interface Implementation

    public function hasVerifiedEmail(): bool
    {
        return !is_null($this->email_verified_at);
    }

    public function markEmailAsVerified(): bool
    {
        $this->email_verified_at = new \DateTimeImmutable();
        return true;
    }

    public function sendEmailVerificationNotification(): void
    {
        // TODO: Send email verification notification
        // This will be implemented when email system is ready
    }

    public function getEmailForVerification(): string
    {
        return $this->email;
    }

    // Role & Permission Methods

    /**
     * Check if user has a specific role
     */
    public function hasRole(string $role): bool
    {
        return $this->roles->exists(function ($key, Role $r) use ($role) {
            return $r->slug === $role || $r->name === $role;
        });
    }

    /**
     * Check if user has any of the given roles
     */
    public function hasAnyRole(array $roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if user has all of the given roles
     */
    public function hasAllRoles(array $roles): bool
    {
        foreach ($roles as $role) {
            if (!$this->hasRole($role)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Assign a role to the user
     */
    public function assignRole(Role $role): void
    {
        if (!$this->roles->contains($role)) {
            $this->roles->add($role);
        }
    }

    /**
     * Remove a role from the user
     */
    public function removeRole(Role $role): void
    {
        $this->roles->removeElement($role);
    }

    /**
     * Sync roles
     */
    public function syncRoles(array $roles): void
    {
        $this->roles->clear();
        foreach ($roles as $role) {
            $this->assignRole($role);
        }
    }

    /**
     * Check if user has a specific permission
     */
    public function hasPermission(string $permission): bool
    {
        foreach ($this->roles as $role) {
            if ($role->hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if user has any of the given permissions
     */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }
        return false;
    }

    /**
     * Check if user has all of the given permissions
     */
    public function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }
        return true;
    }
}
