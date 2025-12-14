# Forms

Build and handle HTML forms with CSRF protection and validation.

## Creating Forms

### Basic Form

```blade
<form method="POST" action="/posts">
    @csrf
    
    <label for="title">Title:</label>
    <input type="text" name="title" id="title" value="{{ old('title') }}">
    @error('title')
        <span class="error">{{ $message }}</span>
    @enderror
    
    <label for="body">Body:</label>
    <textarea name="body" id="body">{{ old('body') }}</textarea>
    @error('body')
        <span class="error">{{ $message }}</span>
    @enderror
    
    <button type="submit">Create Post</button>
</form>
```

## CSRF Protection

### CSRF Token

```blade
{{-- Blade directive --}}
<form method="POST" action="/posts">
    @csrf
    <!-- Rest of form -->
</form>

{{-- HTML input --}}
<form method="POST" action="/posts">
    <input type="hidden" name="_token" value="{{ csrf_token() }}">
    <!-- Rest of form -->
</form>
```

### AJAX Requests

```javascript
// Set CSRF token in AJAX header
$.ajaxSetup({
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});

// Or for each request
$.ajax({
    url: '/posts',
    method: 'POST',
    data: { title: 'New Post' },
    headers: {
        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    }
});
```

## Method Spoofing

### PUT, PATCH, DELETE

```blade
{{-- PUT --}}
<form method="POST" action="/posts/1">
    @csrf
    @method('PUT')
    <!-- Form fields -->
</form>

{{-- PATCH --}}
<form method="POST" action="/posts/1">
    @csrf
    @method('PATCH')
    <!-- Form fields -->
</form>

{{-- DELETE --}}
<form method="POST" action="/posts/1">
    @csrf
    @method('DELETE')
    <button type="submit">Delete</button>
</form>
```

## Form Components

### Text Input

```blade
<div class="form-group">
    <label for="name">Name:</label>
    <input 
        type="text" 
        name="name" 
        id="name" 
        value="{{ old('name', $user->name ?? '') }}"
        class="@error('name') is-invalid @enderror"
    >
    @error('name')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
```

### Textarea

```blade
<div class="form-group">
    <label for="bio">Bio:</label>
    <textarea 
        name="bio" 
        id="bio" 
        rows="5"
        class="@error('bio') is-invalid @enderror"
    >{{ old('bio', $user->bio ?? '') }}</textarea>
    @error('bio')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
```

### Select Dropdown

```blade
<div class="form-group">
    <label for="category">Category:</label>
    <select name="category_id" id="category" class="@error('category_id') is-invalid @enderror">
        <option value="">Select Category</option>
        @foreach($categories as $category)
            <option 
                value="{{ $category->id }}"
                {{ old('category_id', $post->category_id ?? '') == $category->id ? 'selected' : '' }}
            >
                {{ $category->name }}
            </option>
        @endforeach
    </select>
    @error('category_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
```

### Checkbox

```blade
<div class="form-check">
    <input 
        type="checkbox" 
        name="published" 
        id="published" 
        value="1"
        {{ old('published', $post->published ?? false) ? 'checked' : '' }}
    >
    <label for="published">Published</label>
</div>

{{-- Multiple checkboxes --}}
@foreach($tags as $tag)
    <div class="form-check">
        <input 
            type="checkbox" 
            name="tags[]" 
            value="{{ $tag->id }}"
            {{ in_array($tag->id, old('tags', $post->tags->pluck('id')->toArray() ?? [])) ? 'checked' : '' }}
        >
        <label>{{ $tag->name }}</label>
    </div>
@endforeach
```

### Radio Buttons

```blade
<div class="form-group">
    <label>Status:</label>
    
    <div class="form-check">
        <input 
            type="radio" 
            name="status" 
            value="draft" 
            {{ old('status', $post->status ?? '') == 'draft' ? 'checked' : '' }}
        >
        <label>Draft</label>
    </div>
    
    <div class="form-check">
        <input 
            type="radio" 
            name="status" 
            value="published"
            {{ old('status', $post->status ?? '') == 'published' ? 'checked' : '' }}
        >
        <label>Published</label>
    </div>
</div>
```

### File Upload

```blade
<form method="POST" action="/posts" enctype="multipart/form-data">
    @csrf
    
    <div class="form-group">
        <label for="image">Image:</label>
        <input 
            type="file" 
            name="image" 
            id="image"
            accept="image/*"
            class="@error('image') is-invalid @enderror"
        >
        @error('image')
            <div class="invalid-feedback">{{ $message }}</div>
        @enderror
    </div>
    
    <button type="submit">Upload</button>
</form>
```

## Old Input

### Repopulate Form

```blade
{{-- Text input --}}
<input type="text" name="name" value="{{ old('name') }}">

{{-- With default value --}}
<input type="text" name="name" value="{{ old('name', $user->name) }}">

{{-- Textarea --}}
<textarea name="bio">{{ old('bio', $user->bio) }}</textarea>

{{-- Select --}}
<option value="1" {{ old('category_id') == 1 ? 'selected' : '' }}>Category 1</option>

{{-- Checkbox --}}
<input type="checkbox" name="active" {{ old('active') ? 'checked' : '' }}>

{{-- Radio --}}
<input type="radio" name="status" value="active" {{ old('status') == 'active' ? 'checked' : '' }}>
```

## Validation Errors

### Display All Errors

```blade
@if ($errors->any())
    <div class="alert alert-danger">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
```

### Display Field Errors

```blade
@error('email')
    <div class="alert alert-danger">{{ $message }}</div>
@enderror

{{-- Multiple error messages --}}
@if ($errors->has('email'))
    @foreach ($errors->get('email') as $error)
        <div class="alert alert-danger">{{ $error }}</div>
    @endforeach
@endif
```

### Inline Errors

```blade
<input 
    type="text" 
    name="email" 
    class="form-control @error('email') is-invalid @enderror"
>
@error('email')
    <span class="invalid-feedback">{{ $message }}</span>
@enderror
```

## Form Builder Helper

### Custom Form Builder

```php
namespace App\Helpers;

class FormBuilder
{
    public static function text(string $name, $value = '', array $attributes = []): string
    {
        $value = old($name, $value);
        $class = isset($attributes['class']) ? $attributes['class'] : 'form-control';
        
        if (session('errors') && session('errors')->has($name)) {
            $class .= ' is-invalid';
        }
        
        $attributes['class'] = $class;
        $attrs = self::buildAttributes($attributes);
        
        return sprintf(
            '<input type="text" name="%s" value="%s" %s>',
            $name,
            htmlspecialchars($value),
            $attrs
        );
    }
    
    public static function select(string $name, array $options, $selected = null, array $attributes = []): string
    {
        $selected = old($name, $selected);
        $class = isset($attributes['class']) ? $attributes['class'] : 'form-control';
        
        if (session('errors') && session('errors')->has($name)) {
            $class .= ' is-invalid';
        }
        
        $attributes['class'] = $class;
        $attrs = self::buildAttributes($attributes);
        
        $html = sprintf('<select name="%s" %s>', $name, $attrs);
        
        foreach ($options as $value => $label) {
            $isSelected = $value == $selected ? ' selected' : '';
            $html .= sprintf(
                '<option value="%s"%s>%s</option>',
                $value,
                $isSelected,
                htmlspecialchars($label)
            );
        }
        
        $html .= '</select>';
        
        return $html;
    }
    
    private static function buildAttributes(array $attributes): string
    {
        $html = [];
        foreach ($attributes as $key => $value) {
            $html[] = sprintf('%s="%s"', $key, htmlspecialchars($value));
        }
        return implode(' ', $html);
    }
}
```

## Client-Side Validation

### HTML5 Validation

```blade
<form method="POST" action="/posts">
    @csrf
    
    <input 
        type="text" 
        name="title" 
        required 
        minlength="3" 
        maxlength="255"
    >
    
    <input 
        type="email" 
        name="email" 
        required
    >
    
    <input 
        type="url" 
        name="website"
    >
    
    <input 
        type="number" 
        name="age" 
        min="18" 
        max="100"
    >
    
    <button type="submit">Submit</button>
</form>
```

### JavaScript Validation

```javascript
document.querySelector('form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    let errors = [];
    
    // Validate title
    const title = document.querySelector('[name="title"]').value;
    if (title.length < 3) {
        errors.push('Title must be at least 3 characters');
    }
    
    // Validate email
    const email = document.querySelector('[name="email"]').value;
    if (!email.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
        errors.push('Invalid email address');
    }
    
    if (errors.length > 0) {
        alert(errors.join('\n'));
        return;
    }
    
    this.submit();
});
```

## Best Practices

1. **CSRF Protection** - Always include CSRF token
2. **Validation** - Validate on both client and server
3. **Old Input** - Repopulate form on errors
4. **Error Messages** - Display clear error messages
5. **Accessibility** - Use proper labels and ARIA attributes
6. **File Uploads** - Use multipart/form-data
7. **Method Spoofing** - Use @method for PUT/PATCH/DELETE

## See Also

- [Validation](validation.md)
- [Requests](requests.md)
- [CSRF Protection](../security/csrf.md)
- [File Storage](../advanced/storage.md)
