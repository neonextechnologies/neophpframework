<?php

declare(strict_types=1);

namespace NeoCore\Storage;

class FileUploader
{
    protected array $config;
    protected array $errors = [];

    public function __construct(array $config = [])
    {
        $this->config = array_merge([
            'max_size' => 10485760, // 10MB
            'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt'],
            'allowed_mimes' => [
                'image/jpeg',
                'image/jpg',
                'image/png',
                'image/gif',
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'text/plain',
            ],
            'disk' => 'local',
            'path' => 'uploads',
        ], $config);
    }

    public function upload(UploadedFile $file): string|false
    {
        $this->errors = [];

        if (!$this->validate($file)) {
            return false;
        }

        return $file->store($this->config['path'], $this->config['disk']);
    }

    public function uploadAs(UploadedFile $file, string $name): string|false
    {
        $this->errors = [];

        if (!$this->validate($file)) {
            return false;
        }

        return $file->storeAs($this->config['path'], $name, $this->config['disk']);
    }

    public function uploadMultiple(array $files): array
    {
        $results = [];

        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $path = $this->upload($file);
                if ($path !== false) {
                    $results[] = $path;
                }
            }
        }

        return $results;
    }

    public function validate(UploadedFile $file): bool
    {
        // Check if file is valid
        if (!$file->isValid()) {
            $this->errors[] = $file->getErrorMessage();
            return false;
        }

        // Check file size
        if ($file->getSize() > $this->config['max_size']) {
            $maxSizeMB = $this->config['max_size'] / 1048576;
            $this->errors[] = "File size exceeds the maximum allowed size of {$maxSizeMB}MB";
            return false;
        }

        // Check extension
        $extension = strtolower($file->getClientOriginalExtension());
        if (!in_array($extension, $this->config['allowed_extensions'])) {
            $this->errors[] = "File extension '{$extension}' is not allowed";
            return false;
        }

        // Check MIME type
        $mimeType = $file->getMimeType();
        if (!in_array($mimeType, $this->config['allowed_mimes'])) {
            $this->errors[] = "File type '{$mimeType}' is not allowed";
            return false;
        }

        return true;
    }

    public function validateImage(UploadedFile $file, array $requirements = []): bool
    {
        if (!$this->validate($file)) {
            return false;
        }

        if (!$file->isImage()) {
            $this->errors[] = "File is not a valid image";
            return false;
        }

        $imageInfo = $file->getImageInfo();
        
        if ($imageInfo === false) {
            $this->errors[] = "Unable to read image information";
            return false;
        }

        // Check minimum width
        if (isset($requirements['min_width']) && $imageInfo['width'] < $requirements['min_width']) {
            $this->errors[] = "Image width must be at least {$requirements['min_width']}px";
            return false;
        }

        // Check maximum width
        if (isset($requirements['max_width']) && $imageInfo['width'] > $requirements['max_width']) {
            $this->errors[] = "Image width must not exceed {$requirements['max_width']}px";
            return false;
        }

        // Check minimum height
        if (isset($requirements['min_height']) && $imageInfo['height'] < $requirements['min_height']) {
            $this->errors[] = "Image height must be at least {$requirements['min_height']}px";
            return false;
        }

        // Check maximum height
        if (isset($requirements['max_height']) && $imageInfo['height'] > $requirements['max_height']) {
            $this->errors[] = "Image height must not exceed {$requirements['max_height']}px";
            return false;
        }

        // Check aspect ratio
        if (isset($requirements['aspect_ratio'])) {
            $ratio = $imageInfo['width'] / $imageInfo['height'];
            $expectedRatio = $requirements['aspect_ratio'];
            $tolerance = $requirements['aspect_ratio_tolerance'] ?? 0.1;

            if (abs($ratio - $expectedRatio) > $tolerance) {
                $this->errors[] = "Image aspect ratio must be approximately {$expectedRatio}";
                return false;
            }
        }

        return true;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getLastError(): ?string
    {
        return end($this->errors) ?: null;
    }

    public function setConfig(array $config): self
    {
        $this->config = array_merge($this->config, $config);
        return $this;
    }

    public function setMaxSize(int $bytes): self
    {
        $this->config['max_size'] = $bytes;
        return $this;
    }

    public function setAllowedExtensions(array $extensions): self
    {
        $this->config['allowed_extensions'] = $extensions;
        return $this;
    }

    public function setAllowedMimes(array $mimes): self
    {
        $this->config['allowed_mimes'] = $mimes;
        return $this;
    }

    public function setDisk(string $disk): self
    {
        $this->config['disk'] = $disk;
        return $this;
    }

    public function setPath(string $path): self
    {
        $this->config['path'] = $path;
        return $this;
    }
}
