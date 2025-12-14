# Content Management System

Build CMS functionality into your application.

## Page Management

### Page Model

```php
#[Entity(table: 'pages')]
class Page
{
    #[Column(type: 'primary')]
    public int $id;
    
    #[Column(type: 'string')]
    public string $title;
    
    #[Column(type: 'string', unique: true)]
    public string $slug;
    
    #[Column(type: 'text')]
    public string $content;
    
    #[Column(type: 'string')]
    public string $template = 'default';
    
    #[Column(type: 'enum', values: ['draft', 'published', 'archived'])]
    public string $status = 'draft';
    
    #[Column(type: 'integer')]
    public int $parent_id = 0;
    
    #[Column(type: 'json')]
    private string $meta_json;
    
    #[Column(type: 'datetime', nullable: true)]
    public ?\DateTimeInterface $published_at = null;
    
    public function getMeta(): array
    {
        return json_decode($this->meta_json ?? '{}', true);
    }
    
    public function setMeta(array $meta): void
    {
        $this->meta_json = json_encode($meta);
    }
}
```

### Page Controller

```php
class PageController
{
    public function show(string $slug): Response
    {
        $page = Page::where('slug', $slug)
            ->where('status', 'published')
            ->firstOrFail();
        
        Meta::setTitle($page->title);
        Meta::setDescription($page->getMeta()['description'] ?? '');
        
        return view("templates.{$page->template}", compact('page'));
    }
}
```

## Content Blocks

### Block System

```php
#[Entity(table: 'content_blocks')]
class ContentBlock
{
    #[Column(type: 'primary')]
    public int $id;
    
    #[Column(type: 'string')]
    public string $key;
    
    #[Column(type: 'string')]
    public string $type; // text, html, image, video
    
    #[Column(type: 'text')]
    public string $content;
    
    #[Column(type: 'json', nullable: true)]
    private ?string $settings_json = null;
    
    public function getSettings(): array
    {
        return json_decode($this->settings_json ?? '{}', true);
    }
}
```

### Render Blocks

```blade
{{-- In Blade template --}}
@foreach($page->blocks as $block)
    @include("blocks.{$block->type}", ['block' => $block])
@endforeach
```

### Block Templates

```blade
{{-- resources/views/blocks/text.blade.php --}}
<div class="block block-text">
    {{ $block->content }}
</div>

{{-- resources/views/blocks/html.blade.php --}}
<div class="block block-html">
    {!! $block->content !!}
</div>

{{-- resources/views/blocks/image.blade.php --}}
<div class="block block-image">
    <img src="{{ $block->content }}" alt="{{ $block->getSettings()['alt'] ?? '' }}">
</div>
```

## Menu Builder

### Menu Model

```php
#[Entity(table: 'menus')]
class Menu
{
    #[Column(type: 'primary')]
    public int $id;
    
    #[Column(type: 'string')]
    public string $name;
    
    #[Column(type: 'string')]
    public string $location;
    
    #[HasMany(target: MenuItem::class)]
    public array $items = [];
}

#[Entity(table: 'menu_items')]
class MenuItem
{
    #[Column(type: 'primary')]
    public int $id;
    
    #[Column(type: 'integer')]
    public int $menu_id;
    
    #[Column(type: 'string')]
    public string $title;
    
    #[Column(type: 'string')]
    public string $url;
    
    #[Column(type: 'integer')]
    public int $parent_id = 0;
    
    #[Column(type: 'integer')]
    public int $order = 0;
    
    #[Column(type: 'string', nullable: true)]
    public ?string $icon = null;
    
    #[Column(type: 'string', nullable: true)]
    public ?string $target = '_self';
}
```

### Render Menu

```blade
{{-- Recursive menu rendering --}}
@foreach($menu->items->where('parent_id', 0)->sortBy('order') as $item)
    <li>
        <a href="{{ $item->url }}" target="{{ $item->target }}">
            @if($item->icon)
                <i class="{{ $item->icon }}"></i>
            @endif
            {{ $item->title }}
        </a>
        
        @if($item->children->count() > 0)
            <ul>
                @include('partials.menu', ['items' => $item->children])
            </ul>
        @endif
    </li>
@endforeach
```

## Media Library

### Media Model

```php
#[Entity(table: 'media')]
class Media
{
    #[Column(type: 'primary')]
    public int $id;
    
    #[Column(type: 'string')]
    public string $filename;
    
    #[Column(type: 'string')]
    public string $path;
    
    #[Column(type: 'string')]
    public string $mime_type;
    
    #[Column(type: 'integer')]
    public int $size;
    
    #[Column(type: 'integer', nullable: true)]
    public ?int $width = null;
    
    #[Column(type: 'integer', nullable: true)]
    public ?int $height = null;
    
    #[Column(type: 'string', nullable: true)]
    public ?string $alt_text = null;
    
    public function url(): string
    {
        return Storage::url($this->path);
    }
}
```

### Media Upload

```php
public function upload(Request $request): Response
{
    $request->validate([
        'file' => 'required|file|mimes:jpg,png,gif,pdf|max:10240',
    ]);
    
    $file = $request->file('file');
    $path = $file->store('media', 'public');
    
    $media = new Media();
    $media->filename = $file->getClientOriginalName();
    $media->path = $path;
    $media->mime_type = $file->getMimeType();
    $media->size = $file->getSize();
    
    if (Str::startsWith($file->getMimeType(), 'image/')) {
        $image = Image::make($file);
        $media->width = $image->width();
        $media->height = $image->height();
    }
    
    $em->persist($media);
    $em->run();
    
    return response()->json($media);
}
```

## Widgets

### Widget System

```php
#[Entity(table: 'widgets')]
class Widget
{
    #[Column(type: 'primary')]
    public int $id;
    
    #[Column(type: 'string')]
    public string $name;
    
    #[Column(type: 'string')]
    public string $type;
    
    #[Column(type: 'string')]
    public string $area;
    
    #[Column(type: 'integer')]
    public int $order = 0;
    
    #[Column(type: 'json')]
    private string $settings_json;
    
    public function getSettings(): array
    {
        return json_decode($this->settings_json, true);
    }
}
```

### Render Widgets

```blade
{{-- In layout --}}
@foreach(getWidgets('sidebar') as $widget)
    @include("widgets.{$widget->type}", ['widget' => $widget])
@endforeach
```

## Page Templates

### Template System

```php
// config/cms.php
return [
    'templates' => [
        'default' => 'Default Page',
        'home' => 'Homepage',
        'blog' => 'Blog Post',
        'contact' => 'Contact Page',
    ],
];
```

### Template Files

```blade
{{-- resources/views/templates/default.blade.php --}}
@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>{{ $page->title }}</h1>
        <div class="content">
            {!! $page->content !!}
        </div>
    </div>
@endsection
```

## Version Control

### Page Revisions

```php
#[Entity(table: 'page_revisions')]
class PageRevision
{
    #[Column(type: 'primary')]
    public int $id;
    
    #[Column(type: 'integer')]
    public int $page_id;
    
    #[Column(type: 'text')]
    public string $content;
    
    #[Column(type: 'json')]
    private string $data_json;
    
    #[Column(type: 'integer')]
    public int $user_id;
    
    #[Column(type: 'datetime')]
    public \DateTimeInterface $created_at;
}
```

## Best Practices

1. **Slugs** - Use unique, SEO-friendly slugs
2. **Versioning** - Keep revision history
3. **Draft Mode** - Allow preview before publishing
4. **SEO Fields** - Include meta title, description
5. **Media Management** - Organize media efficiently
6. **Access Control** - Implement proper permissions
7. **Caching** - Cache published pages

## See Also

- [SEO Management](seo.md)
- [Media Storage](storage.md)
- [Authorization](../security/authorization.md)
