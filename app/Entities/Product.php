<?php

/**
 * Product Entity Example
 */

namespace App\Entities;

use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Table;

#[Entity]
#[Table(name: 'products')]
class Product
{
    #[Column(type: 'primary')]
    public ?int $id = null;

    #[Column(type: 'string', nullable: false)]
    public string $name;

    #[Column(type: 'string', nullable: false)]
    public string $slug;

    #[Column(type: 'text', nullable: true)]
    public ?string $description = null;

    #[Column(type: 'string', nullable: true)]
    public ?string $category = null;

    #[Column(type: 'decimal(10,2)', default: 0.00)]
    public float $price = 0.00;

    #[Column(type: 'integer', default: 0)]
    public int $stock = 0;

    #[Column(type: 'string', default: 'active')]
    public string $status = 'active';

    #[Column(type: 'datetime')]
    public \DateTimeImmutable $createdAt;

    #[Column(type: 'datetime', nullable: true)]
    public ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    /**
     * Check if product is in stock
     */
    public function isInStock(): bool
    {
        return $this->stock > 0;
    }

    /**
     * Check if product is active
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Decrease stock
     */
    public function decreaseStock(int $quantity): void
    {
        if ($this->stock < $quantity) {
            throw new \RuntimeException('Insufficient stock');
        }
        $this->stock -= $quantity;
    }

    /**
     * Increase stock
     */
    public function increaseStock(int $quantity): void
    {
        $this->stock += $quantity;
    }
}
