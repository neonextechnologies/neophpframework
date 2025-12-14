<?php

declare(strict_types=1);

namespace NeoCore\SEO;

/**
 * Meta Tag Manager
 * 
 * Manages HTML meta tags for SEO
 */
class MetaTag
{
    protected array $tags = [];
    protected array $properties = [];
    protected array $links = [];
    protected ?string $title = null;
    protected string $titleSeparator = ' | ';
    protected ?string $titleSuffix = null;

    public function __construct(array $config = [])
    {
        $this->loadConfig($config);
    }

    /**
     * Load configuration
     */
    protected function loadConfig(array $config): void
    {
        if (isset($config['defaults'])) {
            $defaults = $config['defaults'];
            
            if (isset($defaults['title'])) {
                $this->titleSuffix = $defaults['title'];
            }
            
            if (isset($defaults['title_separator'])) {
                $this->titleSeparator = $defaults['title_separator'];
            }
            
            if (isset($defaults['description'])) {
                $this->setDescription($defaults['description']);
            }
            
            if (isset($defaults['keywords'])) {
                $this->setKeywords($defaults['keywords']);
            }
            
            if (isset($defaults['author'])) {
                $this->setAuthor($defaults['author']);
            }
            
            if (isset($defaults['robots'])) {
                $this->setRobots($defaults['robots']);
            }
        }
    }

    /**
     * Set page title
     */
    public function setTitle(string $title, bool $appendSuffix = true): self
    {
        if ($appendSuffix && $this->titleSuffix) {
            $this->title = $title . $this->titleSeparator . $this->titleSuffix;
        } else {
            $this->title = $title;
        }
        
        return $this;
    }

    /**
     * Get page title
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * Set description
     */
    public function setDescription(string $description): self
    {
        return $this->setTag('description', $description);
    }

    /**
     * Set keywords
     */
    public function setKeywords(string|array $keywords): self
    {
        if (is_array($keywords)) {
            $keywords = implode(', ', $keywords);
        }
        
        return $this->setTag('keywords', $keywords);
    }

    /**
     * Set author
     */
    public function setAuthor(string $author): self
    {
        return $this->setTag('author', $author);
    }

    /**
     * Set robots
     */
    public function setRobots(string $robots): self
    {
        return $this->setTag('robots', $robots);
    }

    /**
     * Set viewport
     */
    public function setViewport(string $viewport = 'width=device-width, initial-scale=1'): self
    {
        return $this->setTag('viewport', $viewport);
    }

    /**
     * Set canonical URL
     */
    public function setCanonical(string $url): self
    {
        $this->links['canonical'] = $url;
        return $this;
    }

    /**
     * Set generic meta tag
     */
    public function setTag(string $name, string $content): self
    {
        $this->tags[$name] = $content;
        return $this;
    }

    /**
     * Set property meta tag (for Open Graph, etc.)
     */
    public function setProperty(string $property, string $content): self
    {
        $this->properties[$property] = $content;
        return $this;
    }

    /**
     * Add link tag
     */
    public function addLink(string $rel, string $href, array $attributes = []): self
    {
        $this->links[$rel] = array_merge(['href' => $href], $attributes);
        return $this;
    }

    /**
     * Remove meta tag
     */
    public function removeTag(string $name): self
    {
        unset($this->tags[$name]);
        return $this;
    }

    /**
     * Remove property
     */
    public function removeProperty(string $property): self
    {
        unset($this->properties[$property]);
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
     * Get all properties
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * Get all links
     */
    public function getLinks(): array
    {
        return $this->links;
    }

    /**
     * Render title tag
     */
    public function renderTitle(): string
    {
        if (!$this->title) {
            return '';
        }
        
        return '<title>' . htmlspecialchars($this->title, ENT_QUOTES, 'UTF-8') . '</title>' . "\n";
    }

    /**
     * Render meta tags
     */
    public function renderTags(): string
    {
        $html = '';
        
        foreach ($this->tags as $name => $content) {
            $html .= sprintf(
                '<meta name="%s" content="%s">' . "\n",
                htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($content, ENT_QUOTES, 'UTF-8')
            );
        }
        
        return $html;
    }

    /**
     * Render property tags
     */
    public function renderProperties(): string
    {
        $html = '';
        
        foreach ($this->properties as $property => $content) {
            $html .= sprintf(
                '<meta property="%s" content="%s">' . "\n",
                htmlspecialchars($property, ENT_QUOTES, 'UTF-8'),
                htmlspecialchars($content, ENT_QUOTES, 'UTF-8')
            );
        }
        
        return $html;
    }

    /**
     * Render link tags
     */
    public function renderLinks(): string
    {
        $html = '';
        
        foreach ($this->links as $rel => $data) {
            if (is_string($data)) {
                $html .= sprintf(
                    '<link rel="%s" href="%s">' . "\n",
                    htmlspecialchars($rel, ENT_QUOTES, 'UTF-8'),
                    htmlspecialchars($data, ENT_QUOTES, 'UTF-8')
                );
            } else {
                $attributes = '';
                foreach ($data as $key => $value) {
                    $attributes .= sprintf(
                        ' %s="%s"',
                        htmlspecialchars($key, ENT_QUOTES, 'UTF-8'),
                        htmlspecialchars($value, ENT_QUOTES, 'UTF-8')
                    );
                }
                $html .= sprintf('<link rel="%s"%s>' . "\n", htmlspecialchars($rel, ENT_QUOTES, 'UTF-8'), $attributes);
            }
        }
        
        return $html;
    }

    /**
     * Render all meta tags
     */
    public function render(): string
    {
        return $this->renderTitle() .
               $this->renderTags() .
               $this->renderProperties() .
               $this->renderLinks();
    }

    /**
     * Convert to string
     */
    public function __toString(): string
    {
        return $this->render();
    }
}
