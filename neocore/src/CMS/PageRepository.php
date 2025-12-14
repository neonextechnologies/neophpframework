<?php

declare(strict_types=1);

namespace NeoCore\CMS;

/**
 * Page Repository
 * 
 * Manages page data persistence
 */
class PageRepository
{
    protected array $pages = [];
    protected int $nextId = 1;

    /**
     * Find page by ID
     */
    public function findById(int $id): ?Page
    {
        return $this->pages[$id] ?? null;
    }

    /**
     * Find page by slug
     */
    public function findBySlug(string $slug): ?Page
    {
        foreach ($this->pages as $page) {
            if ($page->slug === $slug) {
                return $page;
            }
        }
        return null;
    }

    /**
     * Find published page by slug
     */
    public function findPublishedBySlug(string $slug): ?Page
    {
        $page = $this->findBySlug($slug);
        return ($page && $page->isPublished()) ? $page : null;
    }

    /**
     * Find all pages
     */
    public function findAll(): array
    {
        return array_values($this->pages);
    }

    /**
     * Find published pages
     */
    public function findPublished(): array
    {
        return array_filter($this->pages, fn($page) => $page->isPublished());
    }

    /**
     * Find child pages
     */
    public function findChildren(int $parentId): array
    {
        return array_filter($this->pages, fn($page) => $page->parent_id === $parentId);
    }

    /**
     * Find root pages (no parent)
     */
    public function findRootPages(): array
    {
        return array_filter($this->pages, fn($page) => $page->parent_id === null);
    }

    /**
     * Save page
     */
    public function save(Page $page): Page
    {
        if ($page->id === null) {
            $page->id = $this->nextId++;
        }

        $page->updated_at = new \DateTimeImmutable();
        $this->pages[$page->id] = $page;

        return $page;
    }

    /**
     * Delete page
     */
    public function delete(int $id): bool
    {
        if (isset($this->pages[$id])) {
            unset($this->pages[$id]);
            return true;
        }
        return false;
    }

    /**
     * Get page tree
     */
    public function getTree(?int $parentId = null, int $depth = 0, int $maxDepth = 5): array
    {
        if ($depth >= $maxDepth) {
            return [];
        }

        $pages = $parentId === null 
            ? $this->findRootPages() 
            : $this->findChildren($parentId);

        $tree = [];

        foreach ($pages as $page) {
            $item = [
                'page' => $page,
                'children' => $this->getTree($page->id, $depth + 1, $maxDepth),
            ];
            $tree[] = $item;
        }

        usort($tree, fn($a, $b) => $a['page']->order <=> $b['page']->order);

        return $tree;
    }

    /**
     * Search pages
     */
    public function search(string $query): array
    {
        $query = strtolower($query);
        
        return array_filter($this->pages, function($page) use ($query) {
            return str_contains(strtolower($page->title), $query) ||
                   str_contains(strtolower($page->content), $query) ||
                   str_contains(strtolower($page->slug), $query);
        });
    }

    /**
     * Get pages by template
     */
    public function findByTemplate(string $template): array
    {
        return array_filter($this->pages, fn($page) => $page->template === $template);
    }

    /**
     * Get pages by author
     */
    public function findByAuthor(int $authorId): array
    {
        return array_filter($this->pages, fn($page) => $page->author_id === $authorId);
    }

    /**
     * Count pages
     */
    public function count(): int
    {
        return count($this->pages);
    }

    /**
     * Count published pages
     */
    public function countPublished(): int
    {
        return count($this->findPublished());
    }
}
