<?php

declare(strict_types=1);

namespace NeoCore\SEO;

/**
 * Twitter Card Manager
 * 
 * Manages Twitter Card meta tags
 */
class TwitterCard
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
        if (isset($this->config['twitter'])) {
            $twitter = $this->config['twitter'];
            
            if (isset($twitter['card'])) {
                $this->setCard($twitter['card']);
            }
            
            if (isset($twitter['site'])) {
                $this->setSite($twitter['site']);
            }
            
            if (isset($twitter['creator'])) {
                $this->setCreator($twitter['creator']);
            }
        }
    }

    /**
     * Set Twitter card type
     */
    public function setCard(string $card): self
    {
        $this->tags['twitter:card'] = $card;
        return $this;
    }

    /**
     * Set Twitter site account
     */
    public function setSite(string $site): self
    {
        $this->tags['twitter:site'] = $site;
        return $this;
    }

    /**
     * Set Twitter creator account
     */
    public function setCreator(string $creator): self
    {
        $this->tags['twitter:creator'] = $creator;
        return $this;
    }

    /**
     * Set Twitter title
     */
    public function setTitle(string $title): self
    {
        $this->tags['twitter:title'] = $title;
        return $this;
    }

    /**
     * Set Twitter description
     */
    public function setDescription(string $description): self
    {
        $this->tags['twitter:description'] = $description;
        return $this;
    }

    /**
     * Set Twitter image
     */
    public function setImage(string $image): self
    {
        $this->tags['twitter:image'] = $image;
        return $this;
    }

    /**
     * Set Twitter image alt text
     */
    public function setImageAlt(string $alt): self
    {
        $this->tags['twitter:image:alt'] = $alt;
        return $this;
    }

    /**
     * Set custom tag
     */
    public function setTag(string $name, string $content): self
    {
        $this->tags[$name] = $content;
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
     * Render Twitter Card tags
     */
    public function render(): string
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
     * Convert to string
     */
    public function __toString(): string
    {
        return $this->render();
    }
}
