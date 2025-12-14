<?php

declare(strict_types=1);

use NeoCore\Storage\StorageManager;
use NeoCore\Storage\UploadedFile;
use NeoCore\Storage\FileUploader;
use NeoCore\Storage\ImageProcessor;

if (!function_exists('storage')) {
    /**
     * Get the storage manager instance
     */
    function storage(?string $disk = null): StorageManager|\NeoCore\Storage\FilesystemInterface
    {
        $storage = app('storage');
        
        if ($disk !== null) {
            return $storage->disk($disk);
        }
        
        return $storage;
    }
}

if (!function_exists('storage_path')) {
    /**
     * Get the path to the storage directory
     */
    function storage_path(string $path = ''): string
    {
        return base_path('storage' . ($path ? DIRECTORY_SEPARATOR . $path : ''));
    }
}

if (!function_exists('public_path')) {
    /**
     * Get the path to the public directory
     */
    function public_path(string $path = ''): string
    {
        return base_path('public' . ($path ? DIRECTORY_SEPARATOR . $path : ''));
    }
}

if (!function_exists('upload')) {
    /**
     * Create a file uploader instance
     */
    function upload(array $config = []): FileUploader
    {
        return new FileUploader($config);
    }
}

if (!function_exists('uploaded_file')) {
    /**
     * Get an uploaded file from the request
     */
    function uploaded_file(string $key): ?UploadedFile
    {
        return UploadedFile::createFromRequest($key);
    }
}

if (!function_exists('uploaded_files')) {
    /**
     * Get multiple uploaded files from the request
     */
    function uploaded_files(string $key): array
    {
        return UploadedFile::createMultipleFromRequest($key);
    }
}

if (!function_exists('image')) {
    /**
     * Create an image processor instance
     */
    function image(): ImageProcessor
    {
        return new ImageProcessor();
    }
}

if (!function_exists('file_exists_in_storage')) {
    /**
     * Check if a file exists in storage
     */
    function file_exists_in_storage(string $path, ?string $disk = null): bool
    {
        return storage($disk)->exists($path);
    }
}

if (!function_exists('get_file_from_storage')) {
    /**
     * Get file contents from storage
     */
    function get_file_from_storage(string $path, ?string $disk = null): ?string
    {
        return storage($disk)->get($path);
    }
}

if (!function_exists('put_file_to_storage')) {
    /**
     * Put file contents to storage
     */
    function put_file_to_storage(string $path, string $contents, ?string $disk = null): bool
    {
        return storage($disk)->put($path, $contents);
    }
}

if (!function_exists('delete_file_from_storage')) {
    /**
     * Delete a file from storage
     */
    function delete_file_from_storage(string|array $paths, ?string $disk = null): bool
    {
        return storage($disk)->delete($paths);
    }
}

if (!function_exists('copy_file_in_storage')) {
    /**
     * Copy a file in storage
     */
    function copy_file_in_storage(string $from, string $to, ?string $disk = null): bool
    {
        return storage($disk)->copy($from, $to);
    }
}

if (!function_exists('move_file_in_storage')) {
    /**
     * Move a file in storage
     */
    function move_file_in_storage(string $from, string $to, ?string $disk = null): bool
    {
        return storage($disk)->move($from, $to);
    }
}

if (!function_exists('get_file_size')) {
    /**
     * Get file size from storage
     */
    function get_file_size(string $path, ?string $disk = null): int
    {
        return storage($disk)->size($path);
    }
}

if (!function_exists('get_file_last_modified')) {
    /**
     * Get file last modified time from storage
     */
    function get_file_last_modified(string $path, ?string $disk = null): int
    {
        return storage($disk)->lastModified($path);
    }
}

if (!function_exists('list_files_in_storage')) {
    /**
     * List files in storage directory
     */
    function list_files_in_storage(string $directory = '', bool $recursive = false, ?string $disk = null): array
    {
        return storage($disk)->files($directory, $recursive);
    }
}

if (!function_exists('list_directories_in_storage')) {
    /**
     * List directories in storage
     */
    function list_directories_in_storage(string $directory = '', ?string $disk = null): array
    {
        return storage($disk)->directories($directory);
    }
}

if (!function_exists('make_directory_in_storage')) {
    /**
     * Create a directory in storage
     */
    function make_directory_in_storage(string $path, ?string $disk = null): bool
    {
        return storage($disk)->makeDirectory($path);
    }
}

if (!function_exists('delete_directory_in_storage')) {
    /**
     * Delete a directory from storage
     */
    function delete_directory_in_storage(string $directory, ?string $disk = null): bool
    {
        return storage($disk)->deleteDirectory($directory);
    }
}

if (!function_exists('get_file_url')) {
    /**
     * Get the URL for a file in storage
     */
    function get_file_url(string $path, ?string $disk = null): string
    {
        return storage($disk)->url($path);
    }
}

if (!function_exists('format_file_size')) {
    /**
     * Format bytes to human readable file size
     */
    function format_file_size(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }
}

if (!function_exists('get_mime_type')) {
    /**
     * Get MIME type of a file
     */
    function get_mime_type(string $path): string|false
    {
        if (!file_exists($path)) {
            return false;
        }
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $path);
        finfo_close($finfo);
        
        return $mimeType;
    }
}
