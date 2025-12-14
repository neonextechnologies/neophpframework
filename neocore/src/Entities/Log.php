<?php

declare(strict_types=1);

namespace NeoCore\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Table;
use Cycle\Annotated\Annotation\Table\Index;
use DateTimeImmutable;

#[Entity(repository: \NeoCore\Repositories\LogRepository::class)]
#[Table(name: 'logs')]
#[Index(columns: ['level', 'created_at'])]
#[Index(columns: ['channel'])]
class Log
{
    #[Column(type: 'primary')]
    public ?int $id = null;

    #[Column(type: 'string')]
    public string $level;

    #[Column(type: 'string')]
    public string $channel;

    #[Column(type: 'text')]
    public string $message;

    #[Column(type: 'json', nullable: true)]
    public ?array $context = null;

    #[Column(type: 'datetime')]
    public DateTimeImmutable $created_at;

    public function __construct()
    {
        $this->created_at = new DateTimeImmutable();
    }
}
