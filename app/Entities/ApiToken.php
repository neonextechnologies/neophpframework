<?php

declare(strict_types=1);

namespace App\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Table;
use Cycle\Annotated\Annotation\Table\Index;

#[Entity]
#[Table(name: 'api_tokens')]
#[Index(columns: ['token'], unique: true)]
#[Index(columns: ['user_id'])]
class ApiToken
{
    #[Column(type: 'primary')]
    public ?int $id = null;

    #[Column(type: 'integer')]
    public int $user_id;

    #[Column(type: 'string(64)')]
    public string $token;

    #[Column(type: 'string(255)')]
    public string $name;

    #[Column(type: 'json', nullable: true)]
    public ?array $abilities = null;

    #[Column(type: 'datetime', nullable: true)]
    public ?\DateTimeInterface $last_used_at = null;

    #[Column(type: 'datetime', nullable: true)]
    public ?\DateTimeInterface $expires_at = null;

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
     * Generate a new API token
     */
    public static function generate(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Check if token has expired
     */
    public function isExpired(): bool
    {
        if ($this->expires_at === null) {
            return false;
        }

        return $this->expires_at < new \DateTimeImmutable();
    }

    /**
     * Check if token has ability
     */
    public function can(string $ability): bool
    {
        if ($this->abilities === null) {
            return true; // All abilities
        }

        return in_array('*', $this->abilities) || in_array($ability, $this->abilities);
    }

    /**
     * Update last used timestamp
     */
    public function touch(): void
    {
        $this->last_used_at = new \DateTimeImmutable();
    }
}
