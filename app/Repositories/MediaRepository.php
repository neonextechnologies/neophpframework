<?php

declare(strict_types=1);

namespace App\Repositories;

use Cycle\ORM\Select\Repository;
use App\Entities\Media;

class MediaRepository extends Repository
{
    public function findByType(string $type): array
    {
        return $this->select()
            ->where('type', $type)
            ->orderBy('created_at', 'DESC')
            ->fetchAll();
    }

    public function findRecent(int $limit = 10): array
    {
        return $this->select()
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->fetchAll();
    }

    public function findImages(int $limit = null): array
    {
        $query = $this->select()
            ->where('type', 'image')
            ->orderBy('created_at', 'DESC');

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->fetchAll();
    }

    public function findVideos(int $limit = null): array
    {
        $query = $this->select()
            ->where('type', 'video')
            ->orderBy('created_at', 'DESC');

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->fetchAll();
    }

    public function findDocuments(int $limit = null): array
    {
        $query = $this->select()
            ->where('type', 'document')
            ->orderBy('created_at', 'DESC');

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->fetchAll();
    }

    public function search(string $query): array
    {
        return $this->select()
            ->where('name', 'LIKE', "%{$query}%")
            ->orWhere('filename', 'LIKE', "%{$query}%")
            ->orWhere('description', 'LIKE', "%{$query}%")
            ->orderBy('created_at', 'DESC')
            ->fetchAll();
    }

    public function getTotalSize(): int
    {
        $result = $this->select()
            ->buildQuery()
            ->columns(['total' => 'SUM(size)'])
            ->run()
            ->fetch();

        return (int) ($result['total'] ?? 0);
    }

    public function getCountByType(): array
    {
        $results = $this->select()
            ->buildQuery()
            ->columns(['type', 'count' => 'COUNT(*)'])
            ->groupBy('type')
            ->run()
            ->fetchAll();

        $counts = [];
        foreach ($results as $result) {
            $counts[$result['type']] = (int) $result['count'];
        }

        return $counts;
    }

    public function paginate(int $page = 1, int $perPage = 20): array
    {
        $offset = ($page - 1) * $perPage;

        $items = $this->select()
            ->orderBy('created_at', 'DESC')
            ->limit($perPage)
            ->offset($offset)
            ->fetchAll();

        $total = $this->select()->count();

        return [
            'items' => $items,
            'total' => $total,
            'per_page' => $perPage,
            'current_page' => $page,
            'last_page' => (int) ceil($total / $perPage),
        ];
    }

    public function deleteById(int $id): bool
    {
        $media = $this->findByPK($id);
        
        if (!$media) {
            return false;
        }

        // Delete file from storage
        $media->delete();

        // Delete from database
        $this->getEntityManager()->delete($media)->run();

        return true;
    }

    public function cleanup(): int
    {
        $deleted = 0;
        $medias = $this->select()->fetchAll();

        foreach ($medias as $media) {
            // Check if file still exists in storage
            if (!storage($media->disk)->exists($media->path)) {
                $this->getEntityManager()->delete($media)->run();
                $deleted++;
            }
        }

        return $deleted;
    }
}
