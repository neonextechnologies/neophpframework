<?php

declare(strict_types=1);

namespace NeoCore\CMS;

/**
 * Breadcrumb Builder
 * 
 * Builds breadcrumb navigation
 */
class Breadcrumb
{
    protected array $items = [];
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * Add breadcrumb item
     */
    public function add(string $title, ?string $url = null): self
    {
        $this->items[] = [
            'title' => $title,
            'url' => $url,
        ];
        return $this;
    }

    /**
     * Add home item
     */
    public function addHome(?string $title = null, ?string $url = null): self
    {
        $homeTitle = $title ?? ($this->config['breadcrumb']['home_text'] ?? 'Home');
        $homeUrl = $url ?? ($this->config['breadcrumb']['home_url'] ?? '/');
        
        array_unshift($this->items, [
            'title' => $homeTitle,
            'url' => $homeUrl,
        ]);
        
        return $this;
    }

    /**
     * Get all items
     */
    public function getItems(): array
    {
        return $this->items;
    }

    /**
     * Clear all items
     */
    public function clear(): self
    {
        $this->items = [];
        return $this;
    }

    /**
     * Build from path
     */
    public function buildFromPath(string $path, array $labels = []): self
    {
        $this->clear();
        $this->addHome();

        $segments = array_filter(explode('/', trim($path, '/')));
        $currentPath = '';

        foreach ($segments as $index => $segment) {
            $currentPath .= '/' . $segment;
            $title = $labels[$segment] ?? ucwords(str_replace(['-', '_'], ' ', $segment));
            
            // Don't add URL for the last segment (current page)
            $url = ($index === count($segments) - 1) ? null : $currentPath;
            
            $this->add($title, $url);
        }

        return $this;
    }

    /**
     * Build from page tree
     */
    public function buildFromPage(Page $page, PageRepository $repository): self
    {
        $this->clear();
        $this->addHome();

        $pages = [];
        $currentPage = $page;

        // Traverse up the tree
        while ($currentPage) {
            array_unshift($pages, $currentPage);
            
            if ($currentPage->parent_id) {
                $currentPage = $repository->findById($currentPage->parent_id);
            } else {
                break;
            }
        }

        // Add pages to breadcrumb
        foreach ($pages as $index => $p) {
            // Don't add URL for the last page (current page)
            $url = ($index === count($pages) - 1) ? null : $p->getUrl();
            $this->add($p->title, $url);
        }

        return $this;
    }

    /**
     * Render breadcrumb
     */
    public function render(array $options = []): string
    {
        if (empty($this->items)) {
            return '';
        }

        $separator = $options['separator'] ?? ($this->config['breadcrumb']['separator'] ?? ' / ');
        $containerClass = $options['container_class'] ?? 'breadcrumb';
        $itemClass = $options['item_class'] ?? 'breadcrumb-item';
        $activeClass = $options['active_class'] ?? 'active';
        $schema = $options['schema'] ?? true;

        $html = '<nav aria-label="breadcrumb">';
        $html .= '<ol class="' . htmlspecialchars($containerClass, ENT_QUOTES, 'UTF-8') . '"';
        
        if ($schema) {
            $html .= ' itemscope itemtype="https://schema.org/BreadcrumbList"';
        }
        
        $html .= '>';

        $lastIndex = count($this->items) - 1;

        foreach ($this->items as $index => $item) {
            $isLast = $index === $lastIndex;
            
            $html .= '<li class="' . htmlspecialchars($itemClass, ENT_QUOTES, 'UTF-8');
            
            if ($isLast) {
                $html .= ' ' . htmlspecialchars($activeClass, ENT_QUOTES, 'UTF-8');
            }
            
            $html .= '"';
            
            if ($schema) {
                $html .= ' itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem"';
            }
            
            $html .= '>';

            if ($item['url'] && !$isLast) {
                $html .= '<a href="' . htmlspecialchars($item['url'], ENT_QUOTES, 'UTF-8') . '"';
                
                if ($schema) {
                    $html .= ' itemprop="item"';
                }
                
                $html .= '>';
                $html .= '<span';
                
                if ($schema) {
                    $html .= ' itemprop="name"';
                }
                
                $html .= '>' . htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') . '</span>';
                $html .= '</a>';
            } else {
                $html .= '<span';
                
                if ($schema) {
                    $html .= ' itemprop="name"';
                }
                
                $html .= '>' . htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8') . '</span>';
            }

            if ($schema) {
                $html .= '<meta itemprop="position" content="' . ($index + 1) . '">';
            }

            $html .= '</li>';

            if (!$isLast && !empty($separator)) {
                $html .= '<li class="breadcrumb-separator">' . htmlspecialchars($separator, ENT_QUOTES, 'UTF-8') . '</li>';
            }
        }

        $html .= '</ol>';
        $html .= '</nav>';

        return $html;
    }

    /**
     * Render as JSON-LD schema
     */
    public function renderSchema(): string
    {
        if (empty($this->items)) {
            return '';
        }

        $schema = new \NeoCore\SEO\Schema();
        
        $breadcrumbItems = array_map(function($item, $index) {
            return [
                'name' => $item['title'],
                'url' => $item['url'] ?? '',
            ];
        }, $this->items, array_keys($this->items));

        $schema->breadcrumb($breadcrumbItems);

        return $schema->render();
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return $this->items;
    }

    /**
     * Convert to string
     */
    public function __toString(): string
    {
        return $this->render();
    }
}
