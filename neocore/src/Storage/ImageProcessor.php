<?php

declare(strict_types=1);

namespace NeoCore\Storage;

use GdImage;

class ImageProcessor
{
    protected ?GdImage $image = null;
    protected int $width = 0;
    protected int $height = 0;
    protected int $type = 0;
    protected string $mime = '';

    public function __construct()
    {
    }

    public function load(string $path): self
    {
        if (!file_exists($path)) {
            throw new \RuntimeException("Image file not found: {$path}");
        }

        $info = getimagesize($path);
        
        if ($info === false) {
            throw new \RuntimeException("Unable to read image information");
        }

        $this->width = $info[0];
        $this->height = $info[1];
        $this->type = $info[2];
        $this->mime = $info['mime'];

        $this->image = match ($this->type) {
            IMAGETYPE_JPEG => imagecreatefromjpeg($path),
            IMAGETYPE_PNG => imagecreatefrompng($path),
            IMAGETYPE_GIF => imagecreatefromgif($path),
            IMAGETYPE_WEBP => imagecreatefromwebp($path),
            default => throw new \RuntimeException("Unsupported image type"),
        };

        if ($this->image === false) {
            throw new \RuntimeException("Failed to load image");
        }

        return $this;
    }

    public function loadFromString(string $data): self
    {
        $this->image = imagecreatefromstring($data);
        
        if ($this->image === false) {
            throw new \RuntimeException("Failed to load image from string");
        }

        $this->width = imagesx($this->image);
        $this->height = imagesy($this->image);
        
        return $this;
    }

    public function resize(int $width, int $height, bool $maintainAspectRatio = true): self
    {
        if ($this->image === null) {
            throw new \RuntimeException("No image loaded");
        }

        if ($maintainAspectRatio) {
            $aspectRatio = $this->width / $this->height;
            $newAspectRatio = $width / $height;

            if ($newAspectRatio > $aspectRatio) {
                $width = (int) ($height * $aspectRatio);
            } else {
                $height = (int) ($width / $aspectRatio);
            }
        }

        $newImage = imagecreatetruecolor($width, $height);
        
        // Preserve transparency
        $this->preserveTransparency($newImage);

        imagecopyresampled(
            $newImage,
            $this->image,
            0,
            0,
            0,
            0,
            $width,
            $height,
            $this->width,
            $this->height
        );

        imagedestroy($this->image);
        $this->image = $newImage;
        $this->width = $width;
        $this->height = $height;

        return $this;
    }

    public function resizeToWidth(int $width): self
    {
        $ratio = $width / $this->width;
        $height = (int) ($this->height * $ratio);
        
        return $this->resize($width, $height, false);
    }

    public function resizeToHeight(int $height): self
    {
        $ratio = $height / $this->height;
        $width = (int) ($this->width * $ratio);
        
        return $this->resize($width, $height, false);
    }

    public function crop(int $x, int $y, int $width, int $height): self
    {
        if ($this->image === null) {
            throw new \RuntimeException("No image loaded");
        }

        $newImage = imagecreatetruecolor($width, $height);
        
        // Preserve transparency
        $this->preserveTransparency($newImage);

        imagecopy($newImage, $this->image, 0, 0, $x, $y, $width, $height);

        imagedestroy($this->image);
        $this->image = $newImage;
        $this->width = $width;
        $this->height = $height;

        return $this;
    }

    public function cropCenter(int $width, int $height): self
    {
        $x = (int) (($this->width - $width) / 2);
        $y = (int) (($this->height - $height) / 2);
        
        return $this->crop($x, $y, $width, $height);
    }

    public function fit(int $width, int $height): self
    {
        $ratio = min($width / $this->width, $height / $this->height);
        $newWidth = (int) ($this->width * $ratio);
        $newHeight = (int) ($this->height * $ratio);
        
        return $this->resize($newWidth, $newHeight, false);
    }

    public function rotate(float $angle, int $backgroundColor = 0): self
    {
        if ($this->image === null) {
            throw new \RuntimeException("No image loaded");
        }

        $rotated = imagerotate($this->image, $angle, $backgroundColor);
        
        if ($rotated === false) {
            throw new \RuntimeException("Failed to rotate image");
        }

        imagedestroy($this->image);
        $this->image = $rotated;
        $this->width = imagesx($this->image);
        $this->height = imagesy($this->image);

        return $this;
    }

    public function flip(string $mode = 'horizontal'): self
    {
        if ($this->image === null) {
            throw new \RuntimeException("No image loaded");
        }

        $flipMode = match ($mode) {
            'horizontal' => IMG_FLIP_HORIZONTAL,
            'vertical' => IMG_FLIP_VERTICAL,
            'both' => IMG_FLIP_BOTH,
            default => throw new \InvalidArgumentException("Invalid flip mode: {$mode}"),
        };

        imageflip($this->image, $flipMode);

        return $this;
    }

    public function grayscale(): self
    {
        if ($this->image === null) {
            throw new \RuntimeException("No image loaded");
        }

        imagefilter($this->image, IMG_FILTER_GRAYSCALE);

        return $this;
    }

    public function brightness(int $level): self
    {
        if ($this->image === null) {
            throw new \RuntimeException("No image loaded");
        }

        imagefilter($this->image, IMG_FILTER_BRIGHTNESS, $level);

        return $this;
    }

    public function contrast(int $level): self
    {
        if ($this->image === null) {
            throw new \RuntimeException("No image loaded");
        }

        imagefilter($this->image, IMG_FILTER_CONTRAST, $level);

        return $this;
    }

    public function blur(int $passes = 1): self
    {
        if ($this->image === null) {
            throw new \RuntimeException("No image loaded");
        }

        for ($i = 0; $i < $passes; $i++) {
            imagefilter($this->image, IMG_FILTER_GAUSSIAN_BLUR);
        }

        return $this;
    }

    public function sharpen(): self
    {
        if ($this->image === null) {
            throw new \RuntimeException("No image loaded");
        }

        imagefilter($this->image, IMG_FILTER_MEAN_REMOVAL);

        return $this;
    }

    public function watermark(string $watermarkPath, string $position = 'bottom-right', int $padding = 10, int $opacity = 100): self
    {
        if ($this->image === null) {
            throw new \RuntimeException("No image loaded");
        }

        $watermark = new self();
        $watermark->load($watermarkPath);

        [$x, $y] = $this->calculateWatermarkPosition($position, $watermark->getWidth(), $watermark->getHeight(), $padding);

        imagecopymerge(
            $this->image,
            $watermark->getGdImage(),
            $x,
            $y,
            0,
            0,
            $watermark->getWidth(),
            $watermark->getHeight(),
            $opacity
        );

        return $this;
    }

    protected function calculateWatermarkPosition(string $position, int $watermarkWidth, int $watermarkHeight, int $padding): array
    {
        return match ($position) {
            'top-left' => [$padding, $padding],
            'top-right' => [$this->width - $watermarkWidth - $padding, $padding],
            'bottom-left' => [$padding, $this->height - $watermarkHeight - $padding],
            'bottom-right' => [$this->width - $watermarkWidth - $padding, $this->height - $watermarkHeight - $padding],
            'center' => [
                (int) (($this->width - $watermarkWidth) / 2),
                (int) (($this->height - $watermarkHeight) / 2),
            ],
            default => throw new \InvalidArgumentException("Invalid watermark position: {$position}"),
        };
    }

    public function save(string $path, int $quality = 90): bool
    {
        if ($this->image === null) {
            throw new \RuntimeException("No image loaded");
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        $result = match ($extension) {
            'jpg', 'jpeg' => imagejpeg($this->image, $path, $quality),
            'png' => imagepng($this->image, $path, (int) (9 - ($quality / 10))),
            'gif' => imagegif($this->image, $path),
            'webp' => imagewebp($this->image, $path, $quality),
            default => throw new \RuntimeException("Unsupported image format: {$extension}"),
        };

        return $result;
    }

    public function output(string $format = 'jpeg', int $quality = 90): void
    {
        if ($this->image === null) {
            throw new \RuntimeException("No image loaded");
        }

        header("Content-Type: image/{$format}");

        match ($format) {
            'jpeg', 'jpg' => imagejpeg($this->image, null, $quality),
            'png' => imagepng($this->image, null, (int) (9 - ($quality / 10))),
            'gif' => imagegif($this->image),
            'webp' => imagewebp($this->image, null, $quality),
            default => throw new \RuntimeException("Unsupported image format: {$format}"),
        };
    }

    public function getContents(string $format = 'jpeg', int $quality = 90): string
    {
        ob_start();
        
        match ($format) {
            'jpeg', 'jpg' => imagejpeg($this->image, null, $quality),
            'png' => imagepng($this->image, null, (int) (9 - ($quality / 10))),
            'gif' => imagegif($this->image),
            'webp' => imagewebp($this->image, null, $quality),
            default => throw new \RuntimeException("Unsupported image format: {$format}"),
        };
        
        return ob_get_clean();
    }

    protected function preserveTransparency(GdImage $image): void
    {
        imagealphablending($image, false);
        imagesavealpha($image, true);
        
        $transparent = imagecolorallocatealpha($image, 255, 255, 255, 127);
        imagefilledrectangle($image, 0, 0, imagesx($image), imagesy($image), $transparent);
    }

    public function getWidth(): int
    {
        return $this->width;
    }

    public function getHeight(): int
    {
        return $this->height;
    }

    public function getGdImage(): ?GdImage
    {
        return $this->image;
    }

    public function __destruct()
    {
        if ($this->image !== null) {
            imagedestroy($this->image);
        }
    }
}
