<?php

declare(strict_types=1);

namespace NeoCore\SEO;

use DateTimeInterface;
use DateTimeImmutable;

/**
 * Sitemap URL
 * 
 * Represents a single URL in the sitemap
 */
class SitemapUrl
{
    public function __construct(
        public string $loc,
        public ?DateTimeInterface $lastmod = null,
        public string $changefreq = 'weekly',
        public float $priority = 0.5,
        public array $images = [],
        public array $videos = []
    ) {}

    /**
     * Set location
     */
    public function setLoc(string $loc): self
    {
        $this->loc = $loc;
        return $this;
    }

    /**
     * Set last modified date
     */
    public function setLastMod(DateTimeInterface $lastmod): self
    {
        $this->lastmod = $lastmod;
        return $this;
    }

    /**
     * Set change frequency
     */
    public function setChangeFreq(string $changefreq): self
    {
        $this->changefreq = $changefreq;
        return $this;
    }

    /**
     * Set priority
     */
    public function setPriority(float $priority): self
    {
        $this->priority = max(0.0, min(1.0, $priority));
        return $this;
    }

    /**
     * Add image
     */
    public function addImage(string $loc, ?string $caption = null, ?string $title = null): self
    {
        $this->images[] = [
            'loc' => $loc,
            'caption' => $caption,
            'title' => $title,
        ];
        return $this;
    }

    /**
     * Add video
     */
    public function addVideo(string $thumbnail, string $title, string $description, ?string $content = null): self
    {
        $this->videos[] = [
            'thumbnail' => $thumbnail,
            'title' => $title,
            'description' => $description,
            'content' => $content,
        ];
        return $this;
    }

    /**
     * Convert to XML
     */
    public function toXml(): string
    {
        $xml = "  <url>\n";
        $xml .= "    <loc>" . htmlspecialchars($this->loc, ENT_XML1, 'UTF-8') . "</loc>\n";
        
        if ($this->lastmod) {
            $xml .= "    <lastmod>" . $this->lastmod->format('Y-m-d\TH:i:sP') . "</lastmod>\n";
        }
        
        $xml .= "    <changefreq>{$this->changefreq}</changefreq>\n";
        $xml .= "    <priority>" . number_format($this->priority, 1) . "</priority>\n";
        
        // Add images
        foreach ($this->images as $image) {
            $xml .= "    <image:image>\n";
            $xml .= "      <image:loc>" . htmlspecialchars($image['loc'], ENT_XML1, 'UTF-8') . "</image:loc>\n";
            
            if ($image['caption']) {
                $xml .= "      <image:caption>" . htmlspecialchars($image['caption'], ENT_XML1, 'UTF-8') . "</image:caption>\n";
            }
            
            if ($image['title']) {
                $xml .= "      <image:title>" . htmlspecialchars($image['title'], ENT_XML1, 'UTF-8') . "</image:title>\n";
            }
            
            $xml .= "    </image:image>\n";
        }
        
        // Add videos
        foreach ($this->videos as $video) {
            $xml .= "    <video:video>\n";
            $xml .= "      <video:thumbnail_loc>" . htmlspecialchars($video['thumbnail'], ENT_XML1, 'UTF-8') . "</video:thumbnail_loc>\n";
            $xml .= "      <video:title>" . htmlspecialchars($video['title'], ENT_XML1, 'UTF-8') . "</video:title>\n";
            $xml .= "      <video:description>" . htmlspecialchars($video['description'], ENT_XML1, 'UTF-8') . "</video:description>\n";
            
            if ($video['content']) {
                $xml .= "      <video:content_loc>" . htmlspecialchars($video['content'], ENT_XML1, 'UTF-8') . "</video:content_loc>\n";
            }
            
            $xml .= "    </video:video>\n";
        }
        
        $xml .= "  </url>\n";
        
        return $xml;
    }
}
