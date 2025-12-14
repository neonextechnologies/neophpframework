# SEO Management

Optimize your application for search engines.

## Meta Tags

### Set Meta Tags

```php
use NeoPhp\SEO\Meta;

// In controller
Meta::setTitle('Home Page - My Website');
Meta::setDescription('Welcome to my website');
Meta::setKeywords(['website', 'home', 'welcome']);
Meta::setCanonical(url()->current());
Meta::setRobots('index, follow');
```

### Dynamic Meta Tags

```php
public function show(Post $post): Response
{
    Meta::setTitle($post->title . ' - Blog');
    Meta::setDescription(Str::limit($post->excerpt, 160));
    Meta::setImage($post->featured_image);
    Meta::setCanonical(url("/posts/{$post->slug}"));
    
    return view('posts.show', compact('post'));
}
```

### Render Meta Tags

```blade
<!DOCTYPE html>
<html>
<head>
    {!! Meta::render() !!}
</head>
<body>
    @yield('content')
</body>
</html>
```

## Open Graph

### Facebook/OG Tags

```php
Meta::setOgTitle('My Article Title');
Meta::setOgDescription('Article description');
Meta::setOgImage('https://example.com/image.jpg');
Meta::setOgUrl(url()->current());
Meta::setOgType('article');
Meta::setOgSiteName('My Website');
```

### Article Meta

```php
Meta::setOgType('article');
Meta::set('article:published_time', $post->published_at->toIso8601String());
Meta::set('article:modified_time', $post->updated_at->toIso8601String());
Meta::set('article:author', $post->author->name);
Meta::set('article:section', $post->category->name);
Meta::set('article:tag', $post->tags->pluck('name')->toArray());
```

## Twitter Cards

### Twitter Meta Tags

```php
Meta::setTwitterCard('summary_large_image');
Meta::setTwitterSite('@mywebsite');
Meta::setTwitterCreator('@author');
Meta::setTwitterTitle('Article Title');
Meta::setTwitterDescription('Article description');
Meta::setTwitterImage('https://example.com/image.jpg');
```

## Structured Data

### JSON-LD Schema

```php
use NeoPhp\SEO\Schema;

// Article schema
Schema::article([
    'headline' => $post->title,
    'description' => $post->excerpt,
    'image' => $post->image_url,
    'datePublished' => $post->published_at->toIso8601String(),
    'dateModified' => $post->updated_at->toIso8601String(),
    'author' => [
        '@type' => 'Person',
        'name' => $post->author->name,
    ],
]);

// Render schema
{!! Schema::render() !!}
```

### Organization Schema

```php
Schema::organization([
    'name' => 'My Company',
    'url' => 'https://example.com',
    'logo' => 'https://example.com/logo.png',
    'contactPoint' => [
        '@type' => 'ContactPoint',
        'telephone' => '+1-555-555-5555',
        'contactType' => 'customer service',
    ],
    'sameAs' => [
        'https://facebook.com/mycompany',
        'https://twitter.com/mycompany',
    ],
]);
```

### Breadcrumb Schema

```php
Schema::breadcrumb([
    ['name' => 'Home', 'url' => url('/')],
    ['name' => 'Blog', 'url' => url('/blog')],
    ['name' => 'Article', 'url' => url("/blog/{$post->slug}")],
]);
```

## Sitemap

### Generate Sitemap

```php
use NeoPhp\SEO\Sitemap;

public function sitemap(): Response
{
    $sitemap = Sitemap::create();
    
    // Add URLs
    $sitemap->add(url('/'), now(), 'daily', 1.0);
    $sitemap->add(url('/about'), now(), 'monthly', 0.8);
    
    // Add posts
    Post::published()->get()->each(function ($post) use ($sitemap) {
        $sitemap->add(
            url("/posts/{$post->slug}"),
            $post->updated_at,
            'weekly',
            0.9
        );
    });
    
    return response($sitemap->render())
        ->header('Content-Type', 'application/xml');
}
```

### Sitemap Index

```php
public function sitemapIndex(): Response
{
    $sitemapIndex = Sitemap::index();
    
    $sitemapIndex->add(url('/sitemap-posts.xml'), now());
    $sitemapIndex->add(url('/sitemap-pages.xml'), now());
    
    return response($sitemapIndex->render())
        ->header('Content-Type', 'application/xml');
}
```

## Robots.txt

### Dynamic Robots.txt

```php
public function robots(): Response
{
    $robots = "User-agent: *\n";
    
    if (app()->environment('production')) {
        $robots .= "Disallow: /admin\n";
        $robots .= "Disallow: /api\n";
        $robots .= "Allow: /\n";
    } else {
        $robots .= "Disallow: /\n";
    }
    
    $robots .= "\nSitemap: " . url('/sitemap.xml');
    
    return response($robots)
        ->header('Content-Type', 'text/plain');
}
```

## Canonical URLs

### Set Canonical

```php
// Prevent duplicate content
Meta::setCanonical(url('/products/123'));

// Pagination
if ($page > 1) {
    Meta::set('rel', 'prev', url("/products?page=" . ($page - 1)));
}
if ($posts->hasMorePages()) {
    Meta::set('rel', 'next', url("/products?page=" . ($page + 1)));
}
```

## URL Optimization

### Friendly URLs

```php
// Good
/blog/how-to-optimize-seo

// Bad
/blog?id=123&action=view
```

### Slug Generation

```php
use Illuminate\Support\Str;

$post->slug = Str::slug($post->title);
$post->slug = Str::slug($post->title, '-', 'th'); // Thai language
```

## Best Practices

1. **Unique Titles** - Each page should have unique title
2. **Meta Description** - Keep under 160 characters
3. **Canonical URLs** - Use canonical tags to prevent duplicates
4. **Mobile-Friendly** - Ensure responsive design
5. **Page Speed** - Optimize loading times
6. **Structured Data** - Use Schema.org markup
7. **XML Sitemap** - Generate and submit sitemap
8. **Robots.txt** - Configure properly
9. **HTTPS** - Use secure connections
10. **Alt Text** - Add alt text to images

## See Also

- [CMS](cms.md)
- [API Documentation](api.md)
- [Caching](caching.md)
