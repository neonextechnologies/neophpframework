<?php

declare(strict_types=1);

namespace NeoCore\Storage;

use App\Entities\Media;
use App\Repositories\MediaRepository;
use Cycle\ORM\EntityManagerInterface;

class MediaLibrary
{
    protected MediaRepository $repository;
    protected EntityManagerInterface $entityManager;
    protected array $config;

    public function __construct(MediaRepository $repository, EntityManagerInterface $entityManager, array $config = [])
    {
        $this->repository = $repository;
        $this->entityManager = $entityManager;
        $this->config = array_merge([
            'disk' => 'public',
            'path' => 'media',
            'max_size' => 10485760, // 10MB
            'allowed_types' => ['image', 'video', 'audio', 'document'],
        ], $config);
    }

    public function upload(UploadedFile $file, array $options = []): ?Media
    {
        if (!$file->isValid()) {
            throw new \RuntimeException($file->getErrorMessage());
        }

        // Validate file size
        if ($file->getSize() > $this->config['max_size']) {
            throw new \RuntimeException('File size exceeds maximum allowed size');
        }

        // Determine file type
        $mimeType = $file->getMimeType();
        $type = Media::determineType($mimeType);

        // Check if type is allowed
        if (!in_array($type, $this->config['allowed_types'])) {
            throw new \RuntimeException("File type '{$type}' is not allowed");
        }

        // Generate unique filename
        $filename = $this->generateUniqueFilename($file);
        $disk = $options['disk'] ?? $this->config['disk'];
        $path = $this->config['path'] . '/' . $filename;

        // Store file
        $contents = $file->getContents();
        if ($contents === false || !storage($disk)->put($path, $contents)) {
            throw new \RuntimeException('Failed to store file');
        }

        // Create media entity
        $media = new Media();
        $media->name = $options['name'] ?? $file->getClientOriginalName();
        $media->filename = $filename;
        $media->path = $path;
        $media->disk = $disk;
        $media->mime_type = $mimeType;
        $media->type = $type;
        $media->size = $file->getSize();
        $media->description = $options['description'] ?? null;
        $media->alt_text = $options['alt_text'] ?? null;
        $media->metadata = $options['metadata'] ?? null;

        // Get image dimensions if it's an image
        if ($type === 'image') {
            $imageInfo = $file->getImageInfo();
            if ($imageInfo !== false) {
                $media->width = $imageInfo['width'];
                $media->height = $imageInfo['height'];
            }
        }

        // Save to database
        $this->entityManager->persist($media)->run();

        return $media;
    }

    public function uploadMultiple(array $files, array $options = []): array
    {
        $results = [];

        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                try {
                    $results[] = $this->upload($file, $options);
                } catch (\Exception $e) {
                    // Continue with other files
                }
            }
        }

        return $results;
    }

    public function find(int $id): ?Media
    {
        return $this->repository->findByPK($id);
    }

    public function findByType(string $type): array
    {
        return $this->repository->findByType($type);
    }

    public function findRecent(int $limit = 10): array
    {
        return $this->repository->findRecent($limit);
    }

    public function search(string $query): array
    {
        return $this->repository->search($query);
    }

    public function delete(int $id): bool
    {
        return $this->repository->deleteById($id);
    }

    public function deleteMultiple(array $ids): int
    {
        $deleted = 0;

        foreach ($ids as $id) {
            if ($this->delete($id)) {
                $deleted++;
            }
        }

        return $deleted;
    }

    public function update(int $id, array $data): ?Media
    {
        $media = $this->find($id);

        if (!$media) {
            return null;
        }

        if (isset($data['name'])) {
            $media->name = $data['name'];
        }

        if (isset($data['description'])) {
            $media->description = $data['description'];
        }

        if (isset($data['alt_text'])) {
            $media->alt_text = $data['alt_text'];
        }

        if (isset($data['metadata'])) {
            $media->metadata = $data['metadata'];
        }

        $media->updated_at = new \DateTimeImmutable();

        $this->entityManager->persist($media)->run();

        return $media;
    }

    public function replace(int $id, UploadedFile $file): ?Media
    {
        $media = $this->find($id);

        if (!$media) {
            return null;
        }

        // Delete old file
        $media->delete();

        // Upload new file
        $filename = $this->generateUniqueFilename($file);
        $path = $this->config['path'] . '/' . $filename;

        $contents = $file->getContents();
        if ($contents === false || !storage($media->disk)->put($path, $contents)) {
            throw new \RuntimeException('Failed to store file');
        }

        // Update media entity
        $media->filename = $filename;
        $media->path = $path;
        $media->mime_type = $file->getMimeType();
        $media->type = Media::determineType($file->getMimeType());
        $media->size = $file->getSize();
        $media->updated_at = new \DateTimeImmutable();

        // Get image dimensions if it's an image
        if ($media->type === 'image') {
            $imageInfo = $file->getImageInfo();
            if ($imageInfo !== false) {
                $media->width = $imageInfo['width'];
                $media->height = $imageInfo['height'];
            }
        }

        $this->entityManager->persist($media)->run();

        return $media;
    }

    public function createThumbnail(int $id, int $width, int $height): ?string
    {
        $media = $this->find($id);

        if (!$media || !$media->isImage()) {
            return null;
        }

        // Get original image
        $contents = storage($media->disk)->get($media->path);
        if ($contents === null) {
            return null;
        }

        // Process image
        $processor = new ImageProcessor();
        $processor->loadFromString($contents);
        $processor->fit($width, $height);

        // Generate thumbnail path
        $extension = pathinfo($media->filename, PATHINFO_EXTENSION);
        $basename = pathinfo($media->filename, PATHINFO_FILENAME);
        $thumbnailFilename = "{$basename}_{$width}x{$height}.{$extension}";
        $thumbnailPath = $this->config['path'] . '/thumbnails/' . $thumbnailFilename;

        // Save thumbnail
        $thumbnailContents = $processor->getContents($extension);
        if (!storage($media->disk)->put($thumbnailPath, $thumbnailContents)) {
            return null;
        }

        return $thumbnailPath;
    }

    public function getStatistics(): array
    {
        return [
            'total_count' => $this->repository->select()->count(),
            'total_size' => $this->repository->getTotalSize(),
            'count_by_type' => $this->repository->getCountByType(),
        ];
    }

    public function cleanup(): int
    {
        return $this->repository->cleanup();
    }

    protected function generateUniqueFilename(UploadedFile $file): string
    {
        $extension = $file->getClientOriginalExtension();
        $hash = md5(uniqid('', true));
        
        return $extension ? "{$hash}.{$extension}" : $hash;
    }
}
