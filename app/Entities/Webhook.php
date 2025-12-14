<?php

declare(strict_types=1);

namespace App\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Table;
use Cycle\Annotated\Annotation\Table\Index;

#[Entity]
#[Table(name: 'webhooks')]
#[Index(columns: ['url'])]
#[Index(columns: ['event'])]
#[Index(columns: ['active'])]
class Webhook
{
    #[Column(type: 'primary')]
    public ?int $id = null;

    #[Column(type: 'string(255)')]
    public string $name;

    #[Column(type: 'string(500)')]
    public string $url;

    #[Column(type: 'string(100)')]
    public string $event;

    #[Column(type: 'string(20)', default: 'POST')]
    public string $method = 'POST';

    #[Column(type: 'json', nullable: true)]
    public ?array $headers = null;

    #[Column(type: 'string(64)', nullable: true)]
    public ?string $secret = null;

    #[Column(type: 'boolean', default: true)]
    public bool $active = true;

    #[Column(type: 'integer', default: 0)]
    public int $attempts = 0;

    #[Column(type: 'integer', default: 0)]
    public int $failures = 0;

    #[Column(type: 'datetime', nullable: true)]
    public ?\DateTimeInterface $last_triggered_at = null;

    #[Column(type: 'datetime', nullable: true)]
    public ?\DateTimeInterface $last_success_at = null;

    #[Column(type: 'datetime', nullable: true)]
    public ?\DateTimeInterface $last_failure_at = null;

    #[Column(type: 'datetime')]
    public \DateTimeInterface $created_at;

    #[Column(type: 'datetime')]
    public \DateTimeInterface $updated_at;

    public function __construct()
    {
        $this->created_at = new \DateTimeImmutable();
        $this->updated_at = new \DateTimeImmutable();
    }

    /**
     * Generate webhook secret
     */
    public static function generateSecret(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Mark as triggered
     */
    public function markTriggered(): void
    {
        $this->attempts++;
        $this->last_triggered_at = new \DateTimeImmutable();
    }

    /**
     * Mark as successful
     */
    public function markSuccess(): void
    {
        $this->last_success_at = new \DateTimeImmutable();
    }

    /**
     * Mark as failed
     */
    public function markFailed(): void
    {
        $this->failures++;
        $this->last_failure_at = new \DateTimeImmutable();

        // Auto-disable after too many failures
        if ($this->failures >= 10) {
            $this->active = false;
        }
    }

    /**
     * Reset failure count
     */
    public function resetFailures(): void
    {
        $this->failures = 0;
    }
}
