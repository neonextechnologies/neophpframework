<?php

declare(strict_types=1);

namespace App\Controllers;

use NeoCore\Http\Request;
use NeoCore\Http\JsonResponse;
use NeoCore\Storage\MediaLibrary;
use NeoCore\Storage\UploadedFile;
use App\Repositories\MediaRepository;

class MediaController
{
    protected MediaLibrary $mediaLibrary;
    protected MediaRepository $repository;

    public function __construct(MediaLibrary $mediaLibrary, MediaRepository $repository)
    {
        $this->mediaLibrary = $mediaLibrary;
        $this->repository = $repository;
    }

    /**
     * List all media files
     */
    public function index(Request $request): JsonResponse
    {
        $page = (int) ($request->query('page') ?? 1);
        $perPage = (int) ($request->query('per_page') ?? 20);
        $type = $request->query('type');
        $search = $request->query('search');

        if ($search) {
            $items = $this->mediaLibrary->search($search);
            return new JsonResponse(['data' => $items]);
        }

        if ($type) {
            $items = $this->mediaLibrary->findByType($type);
            return new JsonResponse(['data' => $items]);
        }

        $result = $this->repository->paginate($page, $perPage);
        return new JsonResponse($result);
    }

    /**
     * Get a single media file
     */
    public function show(Request $request): JsonResponse
    {
        $id = (int) $request->route('id');
        $media = $this->mediaLibrary->find($id);

        if (!$media) {
            return new JsonResponse(['error' => 'Media not found'], 404);
        }

        return new JsonResponse(['data' => $media]);
    }

    /**
     * Upload a new media file
     */
    public function store(Request $request): JsonResponse
    {
        $file = UploadedFile::createFromRequest('file');

        if (!$file) {
            return new JsonResponse(['error' => 'No file uploaded'], 400);
        }

        try {
            $options = [
                'name' => $request->input('name'),
                'description' => $request->input('description'),
                'alt_text' => $request->input('alt_text'),
                'disk' => $request->input('disk') ?? 'public',
            ];

            $media = $this->mediaLibrary->upload($file, $options);

            return new JsonResponse([
                'message' => 'File uploaded successfully',
                'data' => $media,
            ], 201);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Upload multiple media files
     */
    public function storeMultiple(Request $request): JsonResponse
    {
        $files = UploadedFile::createMultipleFromRequest('files');

        if (empty($files)) {
            return new JsonResponse(['error' => 'No files uploaded'], 400);
        }

        try {
            $options = [
                'disk' => $request->input('disk') ?? 'public',
            ];

            $medias = $this->mediaLibrary->uploadMultiple($files, $options);

            return new JsonResponse([
                'message' => 'Files uploaded successfully',
                'data' => $medias,
                'count' => count($medias),
            ], 201);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Update media information
     */
    public function update(Request $request): JsonResponse
    {
        $id = (int) $request->route('id');

        $data = [
            'name' => $request->input('name'),
            'description' => $request->input('description'),
            'alt_text' => $request->input('alt_text'),
        ];

        $media = $this->mediaLibrary->update($id, $data);

        if (!$media) {
            return new JsonResponse(['error' => 'Media not found'], 404);
        }

        return new JsonResponse([
            'message' => 'Media updated successfully',
            'data' => $media,
        ]);
    }

    /**
     * Replace media file
     */
    public function replace(Request $request): JsonResponse
    {
        $id = (int) $request->route('id');
        $file = UploadedFile::createFromRequest('file');

        if (!$file) {
            return new JsonResponse(['error' => 'No file uploaded'], 400);
        }

        try {
            $media = $this->mediaLibrary->replace($id, $file);

            if (!$media) {
                return new JsonResponse(['error' => 'Media not found'], 404);
            }

            return new JsonResponse([
                'message' => 'File replaced successfully',
                'data' => $media,
            ]);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Delete a media file
     */
    public function destroy(Request $request): JsonResponse
    {
        $id = (int) $request->route('id');

        if ($this->mediaLibrary->delete($id)) {
            return new JsonResponse(['message' => 'Media deleted successfully']);
        }

        return new JsonResponse(['error' => 'Media not found'], 404);
    }

    /**
     * Delete multiple media files
     */
    public function destroyMultiple(Request $request): JsonResponse
    {
        $ids = $request->input('ids');

        if (!is_array($ids) || empty($ids)) {
            return new JsonResponse(['error' => 'No IDs provided'], 400);
        }

        $deleted = $this->mediaLibrary->deleteMultiple($ids);

        return new JsonResponse([
            'message' => "Successfully deleted {$deleted} files",
            'deleted' => $deleted,
        ]);
    }

    /**
     * Create thumbnail for an image
     */
    public function createThumbnail(Request $request): JsonResponse
    {
        $id = (int) $request->route('id');
        $width = (int) ($request->input('width') ?? 150);
        $height = (int) ($request->input('height') ?? 150);

        $thumbnailPath = $this->mediaLibrary->createThumbnail($id, $width, $height);

        if (!$thumbnailPath) {
            return new JsonResponse(['error' => 'Failed to create thumbnail'], 400);
        }

        return new JsonResponse([
            'message' => 'Thumbnail created successfully',
            'path' => $thumbnailPath,
            'url' => get_file_url($thumbnailPath, 'public'),
        ]);
    }

    /**
     * Get media statistics
     */
    public function statistics(): JsonResponse
    {
        $stats = $this->mediaLibrary->getStatistics();

        return new JsonResponse([
            'data' => [
                'total_count' => $stats['total_count'],
                'total_size' => $stats['total_size'],
                'total_size_formatted' => format_file_size($stats['total_size']),
                'count_by_type' => $stats['count_by_type'],
            ],
        ]);
    }

    /**
     * Cleanup orphaned media records
     */
    public function cleanup(): JsonResponse
    {
        $deleted = $this->mediaLibrary->cleanup();

        return new JsonResponse([
            'message' => "Cleaned up {$deleted} orphaned records",
            'deleted' => $deleted,
        ]);
    }
}
