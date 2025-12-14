<?php

declare(strict_types=1);

namespace NeoCore\Storage;

/**
 * Filesystem Interface
 * 
 * Defines methods for file storage operations
 */
interface FilesystemInterface
{
    /**
     * Check if a file exists
     */
    public function exists(string $path): bool;

    /**
     * Get the contents of a file
     */
    public function get(string $path): ?string;

    /**
     * Write the contents to a file
     */
    public function put(string $path, string $contents, array $options = []): bool;

    /**
     * Prepend to a file
     */
    public function prepend(string $path, string $data): bool;

    /**
     * Append to a file
     */
    public function append(string $path, string $data): bool;

    /**
     * Delete a file
     */
    public function delete(string $path): bool;

    /**
     * Delete multiple files
     */
    public function deleteDirectory(string $directory): bool;

    /**
     * Copy a file to a new location
     */
    public function copy(string $from, string $to): bool;

    /**
     * Move a file to a new location
     */
    public function move(string $from, string $to): bool;

    /**
     * Get the file size
     */
    public function size(string $path): int;

    /**
     * Get the file's last modification time
     */
    public function lastModified(string $path): int;

    /**
     * Get an array of all files in a directory
     */
    public function files(string $directory = ''): array;

    /**
     * Get all directories within a directory
     */
    public function directories(string $directory = ''): array;

    /**
     * Create a directory
     */
    public function makeDirectory(string $path): bool;

    /**
     * Get the URL for the file
     */
    public function url(string $path): string;
}
