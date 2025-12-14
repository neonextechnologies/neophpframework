<?php

declare(strict_types=1);

namespace NeoCore\CMS;

/**
 * Menu Builder
 * 
 * Builds and renders navigation menus
 */
class Menu
{
    protected array $items = [];
    protected string $name;
    protected array $config;

    public function __construct(string $name = 'main', array $config = [])
    {
        $this->name = $name;
        $this->config = $config;
    }

    /**
     * Add menu item
     */
    public function add(MenuItem $item): self
    {
        $this->items[] = $item;
        return $this;
    }

    /**
     * Add item by data
     */
    public function addItem(
        string $title,
        string $url,
        ?int $parentId = null,
        int $order = 0,
        array $attributes = []
    ): MenuItem {
        $item = new MenuItem(
            id: count($this->items) + 1,
            title: $title,
            url: $url,
            parent_id: $parentId,
            order: $order,
            attributes: $attributes
        );

        $this->add($item);
        return $item;
    }

    /**
     * Get all items
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * Get root items (no parent)
     */
    public function getRootItems(): array
    {
        return array_filter($this->items, fn($item) => $item->parent_id === null);
    }

    /**
     * Get children of item
     */
    public function getChildren(int $parentId): array
    {
        return array_filter($this->items, fn($item) => $item->parent_id === $parentId);
    }

    /**
     * Build hierarchical tree
     */
    public function buildTree(?int $parentId = null, int $depth = 0, int $maxDepth = 5): array
    {
        if ($depth >= ($this->config['menu']['max_depth'] ?? $maxDepth)) {
            return [];
        }

        $items = $parentId === null 
            ? $this->getRootItems() 
            : $this->getChildren($parentId);

        $tree = [];

        foreach ($items as $item) {
            $itemCopy = clone $item;
            $itemCopy->children = $this->buildTree($item->id, $depth + 1, $maxDepth);
            $tree[] = $itemCopy;
        }

        usort($tree, fn($a, $b) => $a->order <=> $b->order);

        return $tree;
    }

    /**
     * Render menu as HTML list
     */
    public function render(array $options = []): string
    {
        $tree = $this->buildTree();
        
        $ulClass = $options['ul_class'] ?? 'menu';
        $liClass = $options['li_class'] ?? 'menu-item';
        $linkClass = $options['link_class'] ?? 'menu-link';
        $activeClass = $options['active_class'] ?? 'active';
        $currentUrl = $options['current_url'] ?? $_SERVER['REQUEST_URI'] ?? '/';

        return $this->renderItems($tree, $ulClass, $liClass, $linkClass, $activeClass, $currentUrl);
    }

    /**
     * Render items recursively
     */
    protected function renderItems(
        array $items,
        string $ulClass,
        string $liClass,
        string $linkClass,
        string $activeClass,
        string $currentUrl,
        int $depth = 0
    ): string {
        if (empty($items)) {
            return '';
        }

        $html = '<ul class="' . htmlspecialchars($ulClass, ENT_QUOTES, 'UTF-8');
        
        if ($depth > 0) {
            $html .= ' submenu';
        }
        
        $html .= '">';

        foreach ($items as $item) {
            $isActive = $this->isActive($item, $currentUrl);
            $hasChildren = !empty($item->children);
            
            $html .= '<li class="' . htmlspecialchars($liClass, ENT_QUOTES, 'UTF-8');
            
            if ($isActive) {
                $html .= ' ' . htmlspecialchars($activeClass, ENT_QUOTES, 'UTF-8');
            }
            
            if ($hasChildren) {
                $html .= ' has-children';
            }
            
            $html .= '">';
            
            $html .= '<a href="' . htmlspecialchars($item->url, ENT_QUOTES, 'UTF-8') . '" ';
            $html .= 'class="' . htmlspecialchars($linkClass, ENT_QUOTES, 'UTF-8') . '" ';
            $html .= 'target="' . htmlspecialchars($item->target, ENT_QUOTES, 'UTF-8') . '"';
            $html .= $item->renderAttributes();
            $html .= '>';
            $html .= htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8');
            $html .= '</a>';

            if ($hasChildren) {
                $html .= $this->renderItems(
                    $item->children,
                    $ulClass,
                    $liClass,
                    $linkClass,
                    $activeClass,
                    $currentUrl,
                    $depth + 1
                );
            }

            $html .= '</li>';
        }

        $html .= '</ul>';

        return $html;
    }

    /**
     * Check if item is active
     */
    protected function isActive(MenuItem $item, string $currentUrl): bool
    {
        // Remove query string and hash
        $itemPath = parse_url($item->url, PHP_URL_PATH) ?? $item->url;
        $currentPath = parse_url($currentUrl, PHP_URL_PATH) ?? $currentUrl;

        return $itemPath === $currentPath;
    }

    /**
     * Render as dropdown (Bootstrap style)
     */
    public function renderDropdown(array $options = []): string
    {
        return $this->render(array_merge([
            'ul_class' => 'navbar-nav',
            'li_class' => 'nav-item',
            'link_class' => 'nav-link',
            'active_class' => 'active',
        ], $options));
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'items' => array_map(fn($item) => $item->toArray(), $this->items),
        ];
    }

    /**
     * Load from array
     */
    public function loadFromArray(array $data): self
    {
        $this->name = $data['name'] ?? 'main';
        $this->items = [];

        if (isset($data['items'])) {
            foreach ($data['items'] as $itemData) {
                $this->add(MenuItem::fromArray($itemData));
            }
        }

        return $this;
    }
}
