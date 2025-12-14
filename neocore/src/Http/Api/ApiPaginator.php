<?php

declare(strict_types=1);

namespace NeoCore\Http\Api;

/**
 * API Paginator
 * 
 * Handle pagination for API responses
 */
class ApiPaginator
{
    protected array $items;
    protected int $total;
    protected int $perPage;
    protected int $currentPage;
    protected ?string $path = null;

    public function __construct(array $items, int $total, int $perPage, int $currentPage)
    {
        $this->items = $items;
        $this->total = $total;
        $this->perPage = $perPage;
        $this->currentPage = $currentPage;
    }

    /**
     * Create paginator from array
     */
    public static function make(array $items, int $perPage = 15, int $page = 1): self
    {
        $total = count($items);
        $offset = ($page - 1) * $perPage;
        $pageItems = array_slice($items, $offset, $perPage);

        return new self($pageItems, $total, $perPage, $page);
    }

    /**
     * Set base path for links
     */
    public function setPath(string $path): self
    {
        $this->path = $path;
        return $this;
    }

    /**
     * Get items
     */
    public function items(): array
    {
        return $this->items;
    }

    /**
     * Get total items
     */
    public function total(): int
    {
        return $this->total;
    }

    /**
     * Get per page
     */
    public function perPage(): int
    {
        return $this->perPage;
    }

    /**
     * Get current page
     */
    public function currentPage(): int
    {
        return $this->currentPage;
    }

    /**
     * Get last page
     */
    public function lastPage(): int
    {
        return (int) ceil($this->total / $this->perPage);
    }

    /**
     * Check if there are more pages
     */
    public function hasMorePages(): bool
    {
        return $this->currentPage < $this->lastPage();
    }

    /**
     * Get from index
     */
    public function from(): int
    {
        if ($this->total === 0) {
            return 0;
        }

        return ($this->currentPage - 1) * $this->perPage + 1;
    }

    /**
     * Get to index
     */
    public function to(): int
    {
        return min($this->currentPage * $this->perPage, $this->total);
    }

    /**
     * Get next page URL
     */
    public function nextPageUrl(): ?string
    {
        if (!$this->hasMorePages() || !$this->path) {
            return null;
        }

        return $this->url($this->currentPage + 1);
    }

    /**
     * Get previous page URL
     */
    public function previousPageUrl(): ?string
    {
        if ($this->currentPage <= 1 || !$this->path) {
            return null;
        }

        return $this->url($this->currentPage - 1);
    }

    /**
     * Get URL for page
     */
    public function url(int $page): string
    {
        if (!$this->path) {
            return '';
        }

        $separator = str_contains($this->path, '?') ? '&' : '?';
        return $this->path . $separator . "page={$page}";
    }

    /**
     * Get pagination metadata
     */
    public function meta(): array
    {
        return [
            'total' => $this->total,
            'per_page' => $this->perPage,
            'current_page' => $this->currentPage,
            'last_page' => $this->lastPage(),
            'from' => $this->from(),
            'to' => $this->to(),
        ];
    }

    /**
     * Get pagination links
     */
    public function links(): array
    {
        return [
            'first' => $this->url(1),
            'last' => $this->url($this->lastPage()),
            'prev' => $this->previousPageUrl(),
            'next' => $this->nextPageUrl(),
        ];
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'data' => $this->items,
            'meta' => $this->meta(),
            'links' => $this->links(),
        ];
    }

    /**
     * Convert to JSON response
     */
    public function toResponse(): \NeoCore\Http\JsonResponse
    {
        return new \NeoCore\Http\JsonResponse([
            'success' => true,
            'data' => $this->items,
            'meta' => $this->meta(),
            'links' => $this->links(),
        ]);
    }

    /**
     * Get page numbers for pagination display
     */
    public function getPageNumbers(int $onEachSide = 3): array
    {
        $lastPage = $this->lastPage();
        $currentPage = $this->currentPage;

        if ($lastPage <= ($onEachSide * 2) + 1) {
            return range(1, $lastPage);
        }

        $start = max(1, $currentPage - $onEachSide);
        $end = min($lastPage, $currentPage + $onEachSide);

        if ($currentPage <= $onEachSide) {
            $end = ($onEachSide * 2) + 1;
        }

        if ($currentPage >= $lastPage - $onEachSide) {
            $start = $lastPage - ($onEachSide * 2);
        }

        return range($start, $end);
    }
}
