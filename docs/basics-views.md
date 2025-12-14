# Latte Template Engine

NeoPhp Framework uses **Latte Template Engine** for creating UI, which has a syntax similar to Blade but is 2x faster

## Features

- **2x faster than Blade** - Compiled templates + aggressive caching
- **Auto-escaping** - Prevents XSS automatically
- **Blade-like Syntax** - Easy to learn if you've used Laravel
- **Template Inheritance** - Layouts, blocks, includes
- **Custom Filters** - Extend functionality

## Configuration

File `config/view.php`:

```php
return [
    'path' => __DIR__ . '/../resources/views',
    'cache' => __DIR__ . '/../storage/cache/views',
    'auto_refresh' => true, // Auto-recompile in development
    
    'globals' => [
        'app_name' => 'NeoPhp Framework',
        'app_version' => '1.0.0',
    ],
];
```

## Directory Structure

```
resources/views/
├── layouts/
│   ├── app.latte          # Main layout
│   └── messages.latte     # Flash messages partial
├── home.latte             # Homepage
├── users/
│   ├── index.latte        # User list
│   └── show.latte         # User detail
└── products/
    └── index.latte        # Product list
```

## Using in Controller

```php
<?php

namespace App\Http\Controllers;

use NeoPhp\System\Core\Controller;

class HomeController extends Controller
{
    public function index(Request $request, Response $response)
    {
        return $this->view($response, 'home', [
            'title' => 'Welcome',
            'users' => ['John', 'Jane', 'Bob']
        ]);
    }
}
```

### Helper Function

```php
// Short form
return view($response, 'home', ['title' => 'Welcome']);

// Check if template exists
if ($this->viewExists('users/index')) {
    // ...
}
```

## Basic Syntax

### Variables

```latte
{* Comment *}

{* Output *}
<h1>{$title}</h1>
<p>{$description}</p>

{* Auto-escaped *}
{$htmlContent}

{* Raw HTML (beware of XSS!) *}
{$htmlContent|noescape}
```

### Conditions

```latte
{if $user->isAdmin()}
    <p>Welcome Admin!</p>
{elseif $user->isModerator()}
    <p>Welcome Moderator!</p>
{else}
    <p>Welcome User!</p>
{/if}

{* Short syntax *}
{if $count > 0}
    <p>You have {$count} items</p>
{/if}
```

### Loops

```latte
{* Foreach *}
{foreach $users as $user}
    <div class="user">
        <h3>{$user->name}</h3>
        <p>{$user->email}</p>
    </div>
{/foreach}

{* Empty state *}
{foreach $users as $user}
    <li>{$user->name}</li>
{else}
    <li>No users found</li>
{/foreach}

{* With index *}
{foreach $items as $index => $item}
    <li>{$index + 1}. {$item->name}</li>
{/foreach}

{* Loop info *}
{foreach $items as $item}
    {if $iterator->isFirst()}<ul>{/if}
        <li>{$item}</li>
    {if $iterator->isLast()}</ul>{/if}
{/foreach}
```

### For Loop

```latte
{for $i = 1; $i <= 10; $i++}
    <div>Item {$i}</div>
{/for}
```

## Template Inheritance

### Layout (layouts/app.latte)

```latte
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{block title}NeoPhp{/block}</title>
    
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; }
        header { background: #333; color: white; padding: 1rem; }
        main { padding: 2rem; }
    </style>
    
    {block styles}{/block}
</head>
<body>
    <header>
        <h1>{$app_name}</h1>
        <nav>
            <a href="/">Home</a>
            <a href="/users">Users</a>
            <a href="/products">Products</a>
        </nav>
    </header>

    <main>
        {include 'layouts/messages.latte'}
        
        {block content}
            <p>Default content</p>
        {/block}
    </main>

    <footer>
        <p>&copy; 2024 {$app_name} v{$app_version}</p>
    </footer>

    {block scripts}{/block}
</body>
</html>
```

### Child Template (home.latte)

```latte
{extends 'layouts/app.latte'}

{block title}Welcome - NeoPhp{/block}

{block content}
    <h2>Welcome to NeoPhp Framework</h2>
    
    <div class="features">
        <h3>Features</h3>
        <ul>
            {foreach $features as $feature}
                <li>{$feature}</li>
            {/foreach}
        </ul>
    </div>
{/block}

{block scripts}
    <script>
        console.log('Home page loaded');
    </script>
{/block}
```

## Includes

### Partial (layouts/messages.latte)

```latte
{if isset($success)}
    <div class="alert alert-success">{$success}</div>
{/if}

{if isset($error)}
    <div class="alert alert-error">{$error}</div>
{/if}

{if isset($info)}
    <div class="alert alert-info">{$info}</div>
{/if}
```

### Include Partial

```latte
{* Simple include *}
{include 'layouts/messages.latte'}

{* Include with variables *}
{include 'components/card.latte', title: 'My Card', content: $data}

{* Include dynamic *}
{include $templateName}
```

## Filters

Filters are used to transform data values:

### Built-in Filters

```latte
{* String filters *}
{$text|upper}              {* UPPERCASE *}
{$text|lower}              {* lowercase *}
{$text|capitalize}         {* Capitalize First Letter *}
{$text|trim}               {* Remove whitespace *}
{$text|truncate: 50}       {* Truncate to 50 chars *}

{* Number filters *}
{$price|number: 2}         {* 1,234.56 *}
{$price|number: 0, ',', '.'} {* 1.234 *}

{* Date filters *}
{$date|date: 'Y-m-d'}      {* 2024-12-12 *}
{$date|date: 'F j, Y'}     {* December 12, 2024 *}

{* Array filters *}
{$items|length}            {* Count items *}
{$items|first}             {* First item *}
{$items|last}              {* Last item *}

{* JSON *}
{$data|json}               {* JSON encode *}

{* Default value *}
{$value|default: 'N/A'}    {* If value is empty *}
```

### Custom Filters (NeoPhp)

```latte
{* URL helper *}
{$path|url}                {* /path *}
{'users/123'|url}          {* /users/123 *}

{* Asset helper *}
{$file|asset}              {* /assets/file.js *}
{'css/style.css'|asset}    {* /assets/css/style.css *}

{* Date format *}
{$date|date}               {* 2024-12-12 10:30:00 *}
{$date|date: 'd/m/Y'}      {* 12/12/2024 *}

{* Number format *}
{$price|number: 2}         {* 1,234.56 *}

{* JSON *}
{$data|json}               {* {"key":"value"} *}

{* Truncate *}
{$text|truncate: 100}      {* Truncate to 100 chars... *}

{* Slug *}
{$title|slug}              {* convert-to-slug *}
```

### Chain Filters

```latte
{$text|trim|upper|truncate: 50}

{$price|number: 2|default: '0.00'}

{$date|date: 'Y-m-d'|default: 'N/A'}
```

## Advanced Features

### Ternary Operator

```latte
{$status ? 'Active' : 'Inactive'}

{$user->isAdmin() ? 'Admin Panel' : 'User Panel'}
```

### Null-safe Operator

```latte
{$user?->profile?->avatar ?? '/default-avatar.png'}

{$post?->author?->name ?? 'Anonymous'}
```

### Operators

```latte
{* Comparison *}
{if $age >= 18} Adult {/if}
{if $role === 'admin'} Admin {/if}
{if $status !== 'banned'} Active {/if}

{* Logical *}
{if $isAdmin && $isActive}...{/if}
{if $isGuest || $isMember}...{/if}

{* Math *}
{$price * 1.07}
{$total - $discount}
```

### Variables

```latte
{* Define variable *}
{var $total = $price * $quantity}
{var $name = 'John Doe'}

{* Use variable *}
<p>Total: {$total}</p>
```

### N:attributes

```latte
{* Conditional rendering *}
<div n:if="$user->isAdmin()">Admin Panel</div>

{* Loop *}
<ul>
    <li n:foreach="$items as $item">{$item->name}</li>
</ul>

{* Class attribute *}
<div n:class="$isActive ? 'active' : ''">Content</div>

{* Multiple attributes *}
<div n:if="$visible" n:class="$class">Content</div>
```

## Real-world Examples

### User List (users/index.latte)

```latte
{extends 'layouts/app.latte'}

{block title}Users - NeoPhp{/block}

{block content}
    <h2>Users ({$total})</h2>

    {if count($users) > 0}
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Status</th>
                    <th>Created</th>
                </tr>
            </thead>
            <tbody>
                {foreach $users as $user}
                    <tr>
                        <td>{$user->id}</td>
                        <td>{$user->name}</td>
                        <td>{$user->email}</td>
                        <td>
                            <span class="badge badge-{$user->status}">
                                {$user->status|upper}
                            </span>
                        </td>
                        <td>{$user->createdAt|date: 'd/m/Y'}</td>
                    </tr>
                {/foreach}
            </tbody>
        </table>
    {else}
        <p>No users found.</p>
    {/if}
{/block}
```

### Product Grid (products/index.latte)

```latte
{extends 'layouts/app.latte'}

{block title}Products{/block}

{block content}
    <h2>Products</h2>

    <div class="grid">
        {foreach $products as $product}
            <div class="card">
                <h3>{$product->name}</h3>
                <p>{$product->description|truncate: 100}</p>
                
                <div class="price">
                    ฿{$product->price|number: 2}
                </div>
                
                <div class="stock">
                    {if $product->isInStock()}
                        <span class="badge-success">In Stock ({$product->stock})</span>
                    {else}
                        <span class="badge-danger">Out of Stock</span>
                    {/if}
                </div>
                
                <a href="/products/{$product->slug}" class="btn">
                    View Details
                </a>
            </div>
        {/foreach}
    </div>
{/block}
```

## CLI Commands

### Clear View Cache

```bash
php neo view:clear
```

Clears compiled templates in `storage/cache/views/`

## Adding Custom Filters

Edit file `system/Core/ViewService.php`:

```php
private function addCustomFilters(): void
{
    $latte = $this->getEngine();
    
    // Add new filter
    $latte->addFilter('myfilter', function ($value) {
        return strtoupper($value);
    });
}
```

Usage:

```latte
{$text|myfilter}
```

## Best Practices

1. **Use Layouts** - Don't copy-paste HTML repeatedly
2. **Escape Output** - Use `|noescape` only when necessary
3. **Partials** - Separate small components into partials
4. **Cache** - Enable cache in production
5. **Type Hints** - Pass data with clear types from controller

## Performance Tips

- Templates compile once, then cached
- Disable `auto_refresh` in production
- Use `{block}` instead of `{include}` when possible
- Watch out for nested loops (N+1 problem)

## Comparison with Blade

| Feature | Latte | Blade |
|---------|-------|-------|
| Speed | 2x faster | Baseline |
| Auto-escape | Yes | Yes |
| Syntax | `{$var}` | `{{ $var }}` |
| Raw HTML | `{$var\|noescape}` | `{!! $var !!}` |
| Comments | `{* comment *}` | `{{-- comment --}}` |
| Extends | `{extends}` | `@extends` |
| Blocks | `{block}` | `@section` |
| Include | `{include}` | `@include` |
| Foreach | `{foreach}` | `@foreach` |
| If | `{if}` | `@if` |

## Troubleshooting

### Template Not Found

Check path in `config/view.php`:

```php
'path' => __DIR__ . '/../resources/views',
```

### Cache Issues

Clear cache:

```bash
php neo view:clear
```

Or:

```bash
rm -rf storage/cache/views/*
```

### Syntax Errors

- Use `{*` comment `*}` instead of `<!--` HTML comment in logic
- Close all tags properly: `{if}...{/if}`
- Check variable names: `{$variable}` not `{variable}`
