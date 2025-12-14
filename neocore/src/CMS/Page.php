<?php

declare(strict_types=1);

namespace NeoCore\CMS;

use DateTimeImmutable;

/**
 * Page Entity
 * 
 * Represents a CMS page
 */
class Page
{
    public function __construct(
        public ?int $id = null,
        public string $title = '',
        public string $slug = '',
        public string $content = '',
        public string $status = 'draft',
        public ?string $template = null,
        public ?int $parent_id = null,
        public int $order = 0,
        public array $meta = [],
        public array $blocks = [],
        public ?DateTimeImmutable $published_at = null,
        public ?DateTimeImmutable $created_at = null,
        public ?DateTimeImmutable $updated_at = null,
        public ?int $author_id = null
    ) {
        $this->created_at = $this->created_at ?? new DateTimeImmutable();
        $this->updated_at = $this->updated_at ?? new DateTimeImmutable();
    }

    /**
     * Check if page is published
     */
    public function isPublished(): bool
    {
        return $this->status === 'published' && 
               $this->published_at !== null && 
               $this->published_at <= new DateTimeImmutable();
    }

    /**
     * Check if page is draft
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Publish page
     */
    public function publish(): void
    {
        $this->status = 'published';
        $this->published_at = new DateTimeImmutable();
    }

    /**
     * Unpublish page
     */
    public function unpublish(): void
    {
        $this->status = 'draft';
    }

    /**
     * Get meta value
     */
    public function getMeta(string $key, mixed $default = null): mixed
    {
        return $this->meta[$key] ?? $default;
    }

    /**
     * Set meta value
     */
    public function setMeta(string $key, mixed $value): void
    {
        $this->meta[$key] = $value;
    }

    /**
     * Add content block
     */
    public function addBlock(array $block): void
    {
        $this->blocks[] = $block;
    }

    /**
     * Get blocks by type
     */
    public function getBlocksByType(string $type): array
    {
        return array_filter($this->blocks, fn($block) => ($block['type'] ?? '') === $type);
    }

    /**
     * Generate URL
     */
    public function getUrl(): string
    {
        return '/' . ltrim($this->slug, '/');
    }

    /**
     * Get full path (including parent slugs)
     */
    public function getFullPath(?PageRepository $repository = null): string
    {
        if (!$this->parent_id || !$repository) {
            return $this->slug;
        }

        $parent = $repository->findById($this->parent_id);
        
        if ($parent) {
            return rtrim($parent->getFullPath($repository), '/') . '/' . $this->slug;
        }

        return $this->slug;
    }
}
