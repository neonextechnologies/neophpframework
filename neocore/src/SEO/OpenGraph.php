<?php

declare(strict_types=1);

namespace NeoCore\SEO;

/**
 * Open Graph Manager
 * 
 * Manages Open Graph meta tags for social media sharing
 */
class OpenGraph
{
    protected array $tags = [];
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->loadDefaults();
    }

    /**
     * Load default configuration
     */
    protected function loadDefaults(): void
    {
        if (isset($this->config['opengraph'])) {
            $og = $this->config['opengraph'];
            
            if (isset($og['type'])) {
                $this->setType($og['type']);
            }
            
            if (isset($og['site_name'])) {
                $this->setSiteName($og['site_name']);
            }
            
            if (isset($og['locale'])) {
                $this->setLocale($og['locale']);
            }
        }
    }

    /**
     * Set Open Graph title
     */
    public function setTitle(string $title): self
    {
        $this->tags['og:title'] = $title;
        return $this;
    }

    /**
     * Set Open Graph description
     */
    public function setDescription(string $description): self
    {
        $this->tags['og:description'] = $description;
        return $this;
    }

    /**
     * Set Open Graph type
     */
    public function setType(string $type): self
    {
        $this->tags['og:type'] = $type;
        return $this;
    }

    /**
     * Set Open Graph URL
     */
    public function setUrl(string $url): self
    {
        $this->tags['og:url'] = $url;
        return $this;
    }

    /**
     * Set Open Graph image
     */
    public function setImage(string $image, ?int $width = null, ?int $height = null): self
    {
        $this->tags['og:image'] = $image;
        
        if ($width !== null) {
            $this->tags['og:image:width'] = (string) $width;
        } elseif (isset($this->config['opengraph']['image']['width'])) {
            $this->tags['og:image:width'] = (string) $this->config['opengraph']['image']['width'];
        }
        
        if ($height !== null) {
            $this->tags['og:image:height'] = (string) $height;
        } elseif (isset($this->config['opengraph']['image']['height'])) {
            $this->tags['og:image:height'] = (string) $this->config['opengraph']['image']['height'];
        }
        
        return $this;
    }

    /**
     * Set Open Graph site name
     */
    public function setSiteName(string $siteName): self
    {
        $this->tags['og:site_name'] = $siteName;
        return $this;
    }

    /**
     * Set Open Graph locale
     */
    public function setLocale(string $locale): self
    {
        $this->tags['og:locale'] = $locale;
        return $this;
    }

    /**
     * Set article author (for article type)
     */
    public function setArticleAuthor(string $author): self
    {
        $this->tags['article:author'] = $author;
        return $this;
    }

    /**
     * Set article published time
     */
    public function setArticlePublishedTime(string $time): self
    {
        $this->tags['article:published_time'] = $time;
        return $this;
    }

    /**
     * Set article modified time
     */
    public function setArticleModifiedTime(string $time): self
    {
        $this->tags['article:modified_time'] = $time;
        return $this;
    }

    /**
     * Set article section/category
     */
    public function setArticleSection(string $section): self
    {
        $this->tags['article:section'] = $section;
        return $this;
    }

    /**
     * Add article tag
     */
    public function addArticleTag(string $tag): self
    {
        if (!isset($this->tags['article:tag'])) {
            $this->tags['article:tag'] = [];
        }
        
        if (!is_array($this->tags['article:tag'])) {
            $this->tags['article:tag'] = [$this->tags['article:tag']];
        }
        
        $this->tags['article:tag'][] = $tag;
        return $this;
    }

    /**
     * Set custom tag
     */
    public function setTag(string $property, string $content): self
    {
        $this->tags[$property] = $content;
        return $this;
    }

    /**
     * Get all tags
     */
    public function getTags(): array
    {
        return $this->tags;
    }

    /**
     * Render Open Graph tags
     */
    public function render(): string
    {
        $html = '';
        
        foreach ($this->tags as $property => $content) {
            if (is_array($content)) {
                foreach ($content as $value) {
                    $html .= sprintf(
                        '<meta property="%s" content="%s">' . "\n",
                        htmlspecialchars($property, ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars($value, ENT_QUOTES, 'UTF-8')
                    );
                }
            } else {
                $html .= sprintf(
                    '<meta property="%s" content="%s">' . "\n",
                    htmlspecialchars($property, ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars($content, ENT_QUOTES, 'UTF-8')
                );
            }
        }
        
        return $html;
    }

    /**
     * Convert to string
     */
    public function __toString(): string
    {
        return $this->render();
    }
}
