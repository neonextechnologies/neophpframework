<?php

declare(strict_types=1);

namespace App\Entities;

use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Table;
use Cycle\Annotated\Annotation\Relation\ManyToMany;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * Role Entity
 * 
 * @Entity
 * @Table(name="roles")
 */
#[Entity]
#[Table(name: 'roles')]
class Role
{
    #[Column(type: 'primary')]
    public ?int $id = null;

    #[Column(type: 'string', unique: true)]
    public string $name;

    #[Column(type: 'string', unique: true)]
    public string $slug;

    #[Column(type: 'text', nullable: true)]
    public ?string $description = null;

    #[ManyToMany(target: Permission::class, through: 'role_permissions')]
    public Collection $permissions;

    #[Column(type: 'datetime')]
    public \DateTimeImmutable $created_at;

    #[Column(type: 'datetime', nullable: true)]
    public ?\DateTimeImmutable $updated_at = null;

    public function __construct()
    {
        $this->permissions = new ArrayCollection();
        $this->created_at = new \DateTimeImmutable();
    }

    /**
     * Check if role has a permission
     */
    public function hasPermission(string $permission): bool
    {
        return $this->permissions->exists(function ($key, Permission $perm) use ($permission) {
            return $perm->slug === $permission;
        });
    }

    /**
     * Grant a permission to the role
     */
    public function grantPermission(Permission $permission): void
    {
        if (!$this->permissions->contains($permission)) {
            $this->permissions->add($permission);
        }
    }

    /**
     * Revoke a permission from the role
     */
    public function revokePermission(Permission $permission): void
    {
        $this->permissions->removeElement($permission);
    }

    /**
     * Sync permissions
     */
    public function syncPermissions(array $permissions): void
    {
        $this->permissions->clear();
        foreach ($permissions as $permission) {
            $this->grantPermission($permission);
        }
    }
}
