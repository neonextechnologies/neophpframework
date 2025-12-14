<?php

declare(strict_types=1);

namespace NeoCore\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Table;
use Cycle\Annotated\Annotation\Table\Index;
use DateTimeImmutable;

#[Entity(repository: \NeoCore\Repositories\AuditLogRepository::class)]
#[Table(name: 'audit_logs')]
#[Index(columns: ['user_id', 'created_at'])]
#[Index(columns: ['event'])]
#[Index(columns: ['auditable_type', 'auditable_id'])]
class AuditLog
{
    #[Column(type: 'primary')]
    public ?int $id = null;

    #[Column(type: 'integer', nullable: true)]
    public ?int $user_id = null;

    #[Column(type: 'string')]
    public string $event;

    #[Column(type: 'string', nullable: true)]
    public ?string $auditable_type = null;

    #[Column(type: 'integer', nullable: true)]
    public ?int $auditable_id = null;

    #[Column(type: 'json', nullable: true)]
    public ?array $old_values = null;

    #[Column(type: 'json', nullable: true)]
    public ?array $new_values = null;

    #[Column(type: 'string', nullable: true)]
    public ?string $ip_address = null;

    #[Column(type: 'string', nullable: true)]
    public ?string $user_agent = null;

    #[Column(type: 'json', nullable: true)]
    public ?array $metadata = null;

    #[Column(type: 'datetime')]
    public DateTimeImmutable $created_at;

    public function __construct()
    {
        $this->created_at = new DateTimeImmutable();
    }
}
