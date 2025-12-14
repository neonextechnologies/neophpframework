<?php

declare(strict_types=1);

namespace App\Entities;

use Cycle\Annotated\Annotation\Column;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Table;
use Cycle\Annotated\Annotation\Table\Index;

#[Entity(repository: \App\Repositories\MediaRepository::class)]
#[Table(name: 'media')]
#[Index(columns: ['type'])]
#[Index(columns: ['created_at'])]
class Media
{
    #[Column(type: 'primary')]
    public ?int $id = null;

    #[Column(type: 'string(255)')]
    public string $name;

    #[Column(type: 'string(255)')]
    public string $filename;

    #[Column(type: 'string(255)')]
    public string $path;

    #[Column(type: 'string(100)')]
    public string $disk;

    #[Column(type: 'string(100)')]
    public string $mime_type;

    #[Column(type: 'string(50)')]
    public string $type; // image, video, audio, document, other

    #[Column(type: 'bigint')]
    public int $size;

    #[Column(type: 'integer', nullable: true)]
    public ?int $width = null;

    #[Column(type: 'integer', nullable: true)]
    public ?int $height = null;

    #[Column(type: 'text', nullable: true)]
    public ?string $description = null;

    #[Column(type: 'string(255)', nullable: true)]
    public ?string $alt_text = null;

    #[Column(type: 'json', nullable: true)]
    public ?array $metadata = null;

    #[Column(type: 'datetime')]
    public \DateTimeInterface $created_at;

    #[Column(type: 'datetime')]
    public \DateTimeInterface $updated_at;

    public function __construct()
    {
        $this->created_at = new \DateTimeImmutable();
        $this->updated_at = new \DateTimeImmutable();
    }

    public function getUrl(): string
    {
        return storage($this->disk)->url($this->path);
    }

    public function getTemporaryUrl(int $expiration = 3600): string
    {
        $storage = storage($this->disk);
        
        if (method_exists($storage, 'temporaryUrl')) {
            return $storage->temporaryUrl($this->path, $expiration);
        }
        
        return $this->getUrl();
    }

    public function delete(): bool
    {
        return storage($this->disk)->delete($this->path);
    }

    public function isImage(): bool
    {
        return $this->type === 'image';
    }

    public function isVideo(): bool
    {
        return $this->type === 'video';
    }

    public function isAudio(): bool
    {
        return $this->type === 'audio';
    }

    public function isDocument(): bool
    {
        return $this->type === 'document';
    }

    public function getFormattedSize(): string
    {
        return format_file_size($this->size);
    }

    public function getExtension(): string
    {
        return pathinfo($this->filename, PATHINFO_EXTENSION);
    }

    public static function determineType(string $mimeType): string
    {
        return match (true) {
            str_starts_with($mimeType, 'image/') => 'image',
            str_starts_with($mimeType, 'video/') => 'video',
            str_starts_with($mimeType, 'audio/') => 'audio',
            in_array($mimeType, [
                'application/pdf',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'text/plain',
            ]) => 'document',
            default => 'other',
        };
    }
}
