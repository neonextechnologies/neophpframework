<?php

declare(strict_types=1);

namespace NeoCore\CMS;

/**
 * Content Block
 * 
 * Represents a content block in a page
 */
class ContentBlock
{
    public function __construct(
        public string $type,
        public array $data = [],
        public int $order = 0,
        public array $settings = []
    ) {}

    /**
     * Create text block
     */
    public static function text(string $content, int $order = 0): self
    {
        return new self('text', ['content' => $content], $order);
    }

    /**
     * Create HTML block
     */
    public static function html(string $content, int $order = 0): self
    {
        return new self('html', ['content' => $content], $order);
    }

    /**
     * Create markdown block
     */
    public static function markdown(string $content, int $order = 0): self
    {
        return new self('markdown', ['content' => $content], $order);
    }

    /**
     * Create image block
     */
    public static function image(string $src, ?string $alt = null, ?string $caption = null, int $order = 0): self
    {
        return new self('image', [
            'src' => $src,
            'alt' => $alt,
            'caption' => $caption,
        ], $order);
    }

    /**
     * Create gallery block
     */
    public static function gallery(array $images, int $order = 0): self
    {
        return new self('gallery', ['images' => $images], $order);
    }

    /**
     * Create video block
     */
    public static function video(string $url, ?string $poster = null, int $order = 0): self
    {
        return new self('video', [
            'url' => $url,
            'poster' => $poster,
        ], $order);
    }

    /**
     * Create code block
     */
    public static function code(string $code, string $language = 'php', int $order = 0): self
    {
        return new self('code', [
            'code' => $code,
            'language' => $language,
        ], $order);
    }

    /**
     * Create quote block
     */
    public static function quote(string $text, ?string $author = null, int $order = 0): self
    {
        return new self('quote', [
            'text' => $text,
            'author' => $author,
        ], $order);
    }

    /**
     * Create accordion block
     */
    public static function accordion(array $items, int $order = 0): self
    {
        return new self('accordion', ['items' => $items], $order);
    }

    /**
     * Create tabs block
     */
    public static function tabs(array $tabs, int $order = 0): self
    {
        return new self('tabs', ['tabs' => $tabs], $order);
    }

    /**
     * Set block settings
     */
    public function setSettings(array $settings): self
    {
        $this->settings = array_merge($this->settings, $settings);
        return $this;
    }

    /**
     * Get setting value
     */
    public function getSetting(string $key, mixed $default = null): mixed
    {
        return $this->settings[$key] ?? $default;
    }

    /**
     * Get data value
     */
    public function getData(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Render block (basic rendering)
     */
    public function render(): string
    {
        return match($this->type) {
            'text' => $this->renderText(),
            'html' => $this->renderHtml(),
            'markdown' => $this->renderMarkdown(),
            'image' => $this->renderImage(),
            'gallery' => $this->renderGallery(),
            'video' => $this->renderVideo(),
            'code' => $this->renderCode(),
            'quote' => $this->renderQuote(),
            'accordion' => $this->renderAccordion(),
            'tabs' => $this->renderTabs(),
            default => '',
        };
    }

    protected function renderText(): string
    {
        return '<div class="content-block text-block">' . 
               nl2br(htmlspecialchars($this->getData('content', ''), ENT_QUOTES, 'UTF-8')) . 
               '</div>';
    }

    protected function renderHtml(): string
    {
        return '<div class="content-block html-block">' . 
               $this->getData('content', '') . 
               '</div>';
    }

    protected function renderMarkdown(): string
    {
        // Basic markdown rendering (in production, use a proper markdown parser)
        $content = $this->getData('content', '');
        return '<div class="content-block markdown-block">' . 
               nl2br(htmlspecialchars($content, ENT_QUOTES, 'UTF-8')) . 
               '</div>';
    }

    protected function renderImage(): string
    {
        $src = htmlspecialchars($this->getData('src', ''), ENT_QUOTES, 'UTF-8');
        $alt = htmlspecialchars($this->getData('alt', ''), ENT_QUOTES, 'UTF-8');
        $caption = $this->getData('caption');

        $html = '<figure class="content-block image-block">';
        $html .= '<img src="' . $src . '" alt="' . $alt . '">';
        
        if ($caption) {
            $html .= '<figcaption>' . htmlspecialchars($caption, ENT_QUOTES, 'UTF-8') . '</figcaption>';
        }
        
        $html .= '</figure>';
        return $html;
    }

    protected function renderGallery(): string
    {
        $images = $this->getData('images', []);
        $html = '<div class="content-block gallery-block">';
        
        foreach ($images as $image) {
            $src = htmlspecialchars($image['src'] ?? '', ENT_QUOTES, 'UTF-8');
            $alt = htmlspecialchars($image['alt'] ?? '', ENT_QUOTES, 'UTF-8');
            $html .= '<img src="' . $src . '" alt="' . $alt . '">';
        }
        
        $html .= '</div>';
        return $html;
    }

    protected function renderVideo(): string
    {
        $url = htmlspecialchars($this->getData('url', ''), ENT_QUOTES, 'UTF-8');
        $poster = $this->getData('poster');

        $html = '<div class="content-block video-block">';
        $html .= '<video controls';
        
        if ($poster) {
            $html .= ' poster="' . htmlspecialchars($poster, ENT_QUOTES, 'UTF-8') . '"';
        }
        
        $html .= '><source src="' . $url . '"></video>';
        $html .= '</div>';
        return $html;
    }

    protected function renderCode(): string
    {
        $code = htmlspecialchars($this->getData('code', ''), ENT_QUOTES, 'UTF-8');
        $language = htmlspecialchars($this->getData('language', 'php'), ENT_QUOTES, 'UTF-8');

        return '<div class="content-block code-block">' .
               '<pre><code class="language-' . $language . '">' . $code . '</code></pre>' .
               '</div>';
    }

    protected function renderQuote(): string
    {
        $text = htmlspecialchars($this->getData('text', ''), ENT_QUOTES, 'UTF-8');
        $author = $this->getData('author');

        $html = '<blockquote class="content-block quote-block">';
        $html .= '<p>' . $text . '</p>';
        
        if ($author) {
            $html .= '<cite>' . htmlspecialchars($author, ENT_QUOTES, 'UTF-8') . '</cite>';
        }
        
        $html .= '</blockquote>';
        return $html;
    }

    protected function renderAccordion(): string
    {
        $items = $this->getData('items', []);
        $html = '<div class="content-block accordion-block">';
        
        foreach ($items as $index => $item) {
            $title = htmlspecialchars($item['title'] ?? '', ENT_QUOTES, 'UTF-8');
            $content = $item['content'] ?? '';
            
            $html .= '<div class="accordion-item">';
            $html .= '<div class="accordion-title">' . $title . '</div>';
            $html .= '<div class="accordion-content">' . $content . '</div>';
            $html .= '</div>';
        }
        
        $html .= '</div>';
        return $html;
    }

    protected function renderTabs(): string
    {
        $tabs = $this->getData('tabs', []);
        $html = '<div class="content-block tabs-block">';
        
        // Tab headers
        $html .= '<div class="tabs-header">';
        foreach ($tabs as $index => $tab) {
            $title = htmlspecialchars($tab['title'] ?? '', ENT_QUOTES, 'UTF-8');
            $active = $index === 0 ? ' active' : '';
            $html .= '<button class="tab-button' . $active . '">' . $title . '</button>';
        }
        $html .= '</div>';
        
        // Tab contents
        $html .= '<div class="tabs-content">';
        foreach ($tabs as $index => $tab) {
            $content = $tab['content'] ?? '';
            $active = $index === 0 ? ' active' : '';
            $html .= '<div class="tab-pane' . $active . '">' . $content . '</div>';
        }
        $html .= '</div>';
        
        $html .= '</div>';
        return $html;
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'data' => $this->data,
            'order' => $this->order,
            'settings' => $this->settings,
        ];
    }

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['type'] ?? 'text',
            $data['data'] ?? [],
            $data['order'] ?? 0,
            $data['settings'] ?? []
        );
    }
}
