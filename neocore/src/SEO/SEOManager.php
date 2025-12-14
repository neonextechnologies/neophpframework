<?php

declare(strict_types=1);

namespace NeoCore\SEO;

/**
 * SEO Manager
 * 
 * Unified interface for all SEO functionality
 */
class SEOManager
{
    protected MetaTag $metaTag;
    protected OpenGraph $openGraph;
    protected TwitterCard $twitterCard;
    protected Schema $schema;
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
        $this->metaTag = new MetaTag($config);
        $this->openGraph = new OpenGraph($config);
        $this->twitterCard = new TwitterCard($config);
        $this->schema = new Schema($config);
    }

    /**
     * Get MetaTag instance
     */
    public function meta(): MetaTag
    {
        return $this->metaTag;
    }

    /**
     * Get OpenGraph instance
     */
    public function og(): OpenGraph
    {
        return $this->openGraph;
    }

    /**
     * Get TwitterCard instance
     */
    public function twitter(): TwitterCard
    {
        return $this->twitterCard;
    }

    /**
     * Get Schema instance
     */
    public function schema(): Schema
    {
        return $this->schema;
    }

    /**
     * Set page title for all platforms
     */
    public function setTitle(string $title, bool $appendSuffix = true): self
    {
        $this->metaTag->setTitle($title, $appendSuffix);
        $this->openGraph->setTitle($title);
        $this->twitterCard->setTitle($title);
        return $this;
    }

    /**
     * Set description for all platforms
     */
    public function setDescription(string $description): self
    {
        $this->metaTag->setDescription($description);
        $this->openGraph->setDescription($description);
        $this->twitterCard->setDescription($description);
        return $this;
    }

    /**
     * Set image for all platforms
     */
    public function setImage(string $image, ?string $alt = null): self
    {
        $this->openGraph->setImage($image);
        $this->twitterCard->setImage($image);
        
        if ($alt) {
            $this->twitterCard->setImageAlt($alt);
        }
        
        return $this;
    }

    /**
     * Set URL for all platforms
     */
    public function setUrl(string $url): self
    {
        $this->metaTag->setCanonical($url);
        $this->openGraph->setUrl($url);
        return $this;
    }

    /**
     * Configure for blog post
     */
    public function forBlogPost(
        string $title,
        string $description,
        string $author,
        string $publishedAt,
        ?string $image = null,
        ?string $url = null
    ): self {
        $this->setTitle($title);
        $this->setDescription($description);
        
        if ($image) {
            $this->setImage($image);
        }
        
        if ($url) {
            $this->setUrl($url);
        }
        
        $this->openGraph->setType('article');
        $this->openGraph->setArticleAuthor($author);
        $this->openGraph->setArticlePublishedTime($publishedAt);
        
        $this->schema->blogPost($title, $description, $author, $publishedAt, null, $image);
        
        return $this;
    }

    /**
     * Configure for product page
     */
    public function forProduct(
        string $name,
        string $description,
        float $price,
        string $currency = 'USD',
        ?string $image = null,
        ?string $availability = null
    ): self {
        $this->setTitle($name);
        $this->setDescription($description);
        
        if ($image) {
            $this->setImage($image);
        }
        
        $this->openGraph->setType('product');
        $this->openGraph->setProperty('product:price:amount', (string) $price);
        $this->openGraph->setProperty('product:price:currency', $currency);
        
        $offer = $this->schema->offer($price, $currency, $availability ?? 'https://schema.org/InStock');
        $this->schema->product($name, $description, $image, $offer);
        
        return $this;
    }

    /**
     * Render all SEO tags
     */
    public function render(): string
    {
        return $this->metaTag->render() .
               $this->openGraph->render() .
               $this->twitterCard->render() .
               $this->schema->render();
    }

    /**
     * Convert to string
     */
    public function __toString(): string
    {
        return $this->render();
    }
}
