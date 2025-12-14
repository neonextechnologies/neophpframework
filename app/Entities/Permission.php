<?php

declare(strict_types=1);

namespace App\Entities;

use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Table;

/**
 * Permission Entity
 * 
 * @Entity
 * @Table(name="permissions")
 */
#[Entity]
#[Table(name: 'permissions')]
class Permission
{
    #[Column(type: 'primary')]
    public ?int $id = null;

    #[Column(type: 'string', unique: true)]
    public string $name;

    #[Column(type: 'string', unique: true)]
    public string $slug;

    #[Column(type: 'text', nullable: true)]
    public ?string $description = null;

    #[Column(type: 'string', nullable: true)]
    public ?string $group = null;

    #[Column(type: 'datetime')]
    public \DateTimeImmutable $created_at;

    #[Column(type: 'datetime', nullable: true)]
    public ?\DateTimeImmutable $updated_at = null;

    public function __construct()
    {
        $this->created_at = new \DateTimeImmutable();
    }
}
