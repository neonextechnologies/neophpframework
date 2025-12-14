<?php

declare(strict_types=1);

namespace NeoCore\SEO;

/**
 * Schema.org Structured Data Generator
 * 
 * Generates JSON-LD structured data for SEO
 */
class Schema
{
    protected array $data = [];
    protected array $config;

    public function __construct(array $config = [])
    {
        $this->config = $config;
    }

    /**
     * Add schema data
     */
    public function add(array $schema): self
    {
        $this->data[] = $schema;
        return $this;
    }

    /**
     * Create Organization schema
     */
    public function organization(string $name, string $url, ?string $logo = null): self
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $name,
            'url' => $url,
        ];

        if ($logo) {
            $schema['logo'] = $logo;
        }

        // Add from config if available
        if (isset($this->config['schema']['organization'])) {
            $org = $this->config['schema']['organization'];
            
            if (!$logo && isset($org['logo'])) {
                $schema['logo'] = $org['logo'];
            }
        }

        return $this->add($schema);
    }

    /**
     * Create WebSite schema
     */
    public function website(string $name, string $url, ?array $potentialAction = null): self
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $name,
            'url' => $url,
        ];

        if ($potentialAction) {
            $schema['potentialAction'] = $potentialAction;
        }

        return $this->add($schema);
    }

    /**
     * Create SearchAction for website
     */
    public function searchAction(string $target, string $queryInput = 'required name=search_term_string'): array
    {
        return [
            '@type' => 'SearchAction',
            'target' => $target,
            'query-input' => $queryInput,
        ];
    }

    /**
     * Create Article schema
     */
    public function article(
        string $headline,
        string $description,
        string $author,
        string $datePublished,
        ?string $dateModified = null,
        ?string $image = null
    ): self {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $headline,
            'description' => $description,
            'author' => [
                '@type' => 'Person',
                'name' => $author,
            ],
            'datePublished' => $datePublished,
        ];

        if ($dateModified) {
            $schema['dateModified'] = $dateModified;
        }

        if ($image) {
            $schema['image'] = $image;
        }

        return $this->add($schema);
    }

    /**
     * Create BlogPosting schema
     */
    public function blogPost(
        string $headline,
        string $description,
        string $author,
        string $datePublished,
        ?string $dateModified = null,
        ?string $image = null
    ): self {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting',
            'headline' => $headline,
            'description' => $description,
            'author' => [
                '@type' => 'Person',
                'name' => $author,
            ],
            'datePublished' => $datePublished,
        ];

        if ($dateModified) {
            $schema['dateModified'] = $dateModified;
        }

        if ($image) {
            $schema['image'] = $image;
        }

        return $this->add($schema);
    }

    /**
     * Create Product schema
     */
    public function product(
        string $name,
        string $description,
        ?string $image = null,
        ?array $offers = null
    ): self {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $name,
            'description' => $description,
        ];

        if ($image) {
            $schema['image'] = $image;
        }

        if ($offers) {
            $schema['offers'] = $offers;
        }

        return $this->add($schema);
    }

    /**
     * Create Offer schema
     */
    public function offer(
        float $price,
        string $priceCurrency = 'USD',
        string $availability = 'https://schema.org/InStock'
    ): array {
        return [
            '@type' => 'Offer',
            'price' => $price,
            'priceCurrency' => $priceCurrency,
            'availability' => $availability,
        ];
    }

    /**
     * Create BreadcrumbList schema
     */
    public function breadcrumb(array $items): self
    {
        $listItems = [];
        
        foreach ($items as $position => $item) {
            $listItems[] = [
                '@type' => 'ListItem',
                'position' => $position + 1,
                'name' => $item['name'],
                'item' => $item['url'] ?? null,
            ];
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => $listItems,
        ];

        return $this->add($schema);
    }

    /**
     * Create FAQ schema
     */
    public function faq(array $questions): self
    {
        $mainEntity = [];
        
        foreach ($questions as $qa) {
            $mainEntity[] = [
                '@type' => 'Question',
                'name' => $qa['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $qa['answer'],
                ],
            ];
        }

        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $mainEntity,
        ];

        return $this->add($schema);
    }

    /**
     * Create Person schema
     */
    public function person(
        string $name,
        ?string $jobTitle = null,
        ?string $url = null,
        ?string $image = null
    ): self {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Person',
            'name' => $name,
        ];

        if ($jobTitle) {
            $schema['jobTitle'] = $jobTitle;
        }

        if ($url) {
            $schema['url'] = $url;
        }

        if ($image) {
            $schema['image'] = $image;
        }

        return $this->add($schema);
    }

    /**
     * Create LocalBusiness schema
     */
    public function localBusiness(
        string $name,
        string $address,
        ?string $telephone = null,
        ?array $geo = null
    ): self {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'LocalBusiness',
            'name' => $name,
            'address' => $address,
        ];

        if ($telephone) {
            $schema['telephone'] = $telephone;
        }

        if ($geo) {
            $schema['geo'] = $geo;
        }

        return $this->add($schema);
    }

    /**
     * Get all schema data
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Render as JSON-LD
     */
    public function render(): string
    {
        if (empty($this->data)) {
            return '';
        }

        $html = '<script type="application/ld+json">' . "\n";
        
        if (count($this->data) === 1) {
            $html .= json_encode($this->data[0], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } else {
            $html .= json_encode($this->data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }
        
        $html .= "\n</script>\n";

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
