<?php

declare(strict_types=1);

namespace NeoCore\CMS;

/**
 * Menu Item
 * 
 * Represents a menu item
 */
class MenuItem
{
    public function __construct(
        public ?int $id = null,
        public string $title = '',
        public string $url = '',
        public ?int $parent_id = null,
        public int $order = 0,
        public string $target = '_self',
        public array $attributes = [],
        public array $children = []
    ) {}

    /**
     * Add child menu item
     */
    public function addChild(MenuItem $child): void
    {
        $child->parent_id = $this->id;
        $this->children[] = $child;
    }

    /**
     * Remove child
     */
    public function removeChild(int $id): bool
    {
        foreach ($this->children as $index => $child) {
            if ($child->id === $id) {
                unset($this->children[$index]);
                $this->children = array_values($this->children);
                return true;
            }
        }
        return false;
    }

    /**
     * Has children
     */
    public function hasChildren(): bool
    {
        return !empty($this->children);
    }

    /**
     * Sort children by order
     */
    public function sortChildren(): void
    {
        usort($this->children, fn($a, $b) => $a->order <=> $b->order);
    }

    /**
     * Get attribute
     */
    public function getAttribute(string $key, mixed $default = null): mixed
    {
        return $this->attributes[$key] ?? $default;
    }

    /**
     * Set attribute
     */
    public function setAttribute(string $key, mixed $value): void
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Render attributes
     */
    public function renderAttributes(): string
    {
        $html = '';
        
        foreach ($this->attributes as $key => $value) {
            $html .= ' ' . htmlspecialchars($key, ENT_QUOTES, 'UTF-8') . 
                     '="' . htmlspecialchars($value, ENT_QUOTES, 'UTF-8') . '"';
        }
        
        return $html;
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'url' => $this->url,
            'parent_id' => $this->parent_id,
            'order' => $this->order,
            'target' => $this->target,
            'attributes' => $this->attributes,
            'children' => array_map(fn($child) => $child->toArray(), $this->children),
        ];
    }

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        $item = new self(
            $data['id'] ?? null,
            $data['title'] ?? '',
            $data['url'] ?? '',
            $data['parent_id'] ?? null,
            $data['order'] ?? 0,
            $data['target'] ?? '_self',
            $data['attributes'] ?? []
        );

        if (isset($data['children'])) {
            foreach ($data['children'] as $childData) {
                $item->addChild(self::fromArray($childData));
            }
        }

        return $item;
    }
}
