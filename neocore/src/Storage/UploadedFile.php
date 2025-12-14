<?php

declare(strict_types=1);

namespace NeoCore\Storage;

class UploadedFile
{
    protected string $name;
    protected string $tmpName;
    protected string $type;
    protected int $size;
    protected int $error;

    public function __construct(array $file)
    {
        $this->name = $file['name'] ?? '';
        $this->tmpName = $file['tmp_name'] ?? '';
        $this->type = $file['type'] ?? '';
        $this->size = (int) ($file['size'] ?? 0);
        $this->error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    }

    public static function createFromRequest(string $key): ?self
    {
        if (!isset($_FILES[$key])) {
            return null;
        }

        return new self($_FILES[$key]);
    }

    public static function createMultipleFromRequest(string $key): array
    {
        if (!isset($_FILES[$key])) {
            return [];
        }

        $files = $_FILES[$key];
        $result = [];

        // Handle multiple file uploads
        if (is_array($files['name'])) {
            $count = count($files['name']);
            for ($i = 0; $i < $count; $i++) {
                $result[] = new self([
                    'name' => $files['name'][$i],
                    'tmp_name' => $files['tmp_name'][$i],
                    'type' => $files['type'][$i],
                    'size' => $files['size'][$i],
                    'error' => $files['error'][$i],
                ]);
            }
        } else {
            $result[] = new self($files);
        }

        return $result;
    }

    public function isValid(): bool
    {
        return $this->error === UPLOAD_ERR_OK && is_uploaded_file($this->tmpName);
    }

    public function getError(): int
    {
        return $this->error;
    }

    public function getErrorMessage(): string
    {
        return match ($this->error) {
            UPLOAD_ERR_OK => 'No error',
            UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
            UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form',
            UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload',
            default => 'Unknown upload error',
        };
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getClientOriginalName(): string
    {
        return $this->name;
    }

    public function getClientOriginalExtension(): string
    {
        return pathinfo($this->name, PATHINFO_EXTENSION);
    }

    public function getTmpName(): string
    {
        return $this->tmpName;
    }

    public function getMimeType(): string
    {
        return $this->type;
    }

    public function getSize(): int
    {
        return $this->size;
    }

    public function getContents(): string|false
    {
        if (!$this->isValid()) {
            return false;
        }

        return file_get_contents($this->tmpName);
    }

    public function moveTo(string $destination): bool
    {
        if (!$this->isValid()) {
            return false;
        }

        // Create directory if it doesn't exist
        $directory = dirname($destination);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        return move_uploaded_file($this->tmpName, $destination);
    }

    public function store(string $path, string $disk = null): string|false
    {
        if (!$this->isValid()) {
            return false;
        }

        $storage = app('storage');
        
        if ($disk) {
            $storage = $storage->disk($disk);
        }

        $contents = $this->getContents();
        
        if ($contents === false) {
            return false;
        }

        $filename = $this->generateFilename();
        $fullPath = $path . '/' . $filename;

        if ($storage->put($fullPath, $contents)) {
            return $fullPath;
        }

        return false;
    }

    public function storeAs(string $path, string $name, string $disk = null): string|false
    {
        if (!$this->isValid()) {
            return false;
        }

        $storage = app('storage');
        
        if ($disk) {
            $storage = $storage->disk($disk);
        }

        $contents = $this->getContents();
        
        if ($contents === false) {
            return false;
        }

        $fullPath = $path . '/' . $name;

        if ($storage->put($fullPath, $contents)) {
            return $fullPath;
        }

        return false;
    }

    protected function generateFilename(): string
    {
        $extension = $this->getClientOriginalExtension();
        $hash = md5(uniqid('', true));
        
        return $extension ? "{$hash}.{$extension}" : $hash;
    }

    public function isImage(): bool
    {
        $imageTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
        return in_array($this->getMimeType(), $imageTypes);
    }

    public function getImageInfo(): array|false
    {
        if (!$this->isValid() || !$this->isImage()) {
            return false;
        }

        $info = getimagesize($this->tmpName);
        
        if ($info === false) {
            return false;
        }

        return [
            'width' => $info[0],
            'height' => $info[1],
            'type' => $info[2],
            'mime' => $info['mime'],
        ];
    }
}
