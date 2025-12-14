# HTTP Requests

Access and validate incoming HTTP requests.

## Accessing Request

### Get Request Instance

```php
use NeoPhp\Http\Request;

public function store(Request $request): Response
{
    $name = $request->input('name');
    // ...
}
```

## Retrieving Input

### Get Input Values

```php
// Get input value
$name = $request->input('name');

// With default value
$name = $request->input('name', 'Guest');

// Get all input
$input = $request->all();

// Get specific inputs
$input = $request->only(['name', 'email']);
$input = $request->except(['password']);

// Check if input exists
if ($request->has('name')) {
    //
}

if ($request->filled('name')) {
    // Value exists and not empty
}

if ($request->missing('name')) {
    //
}
```

### Query String

```php
// Get query parameter
$name = $request->query('name');

// With default
$name = $request->query('name', 'Guest');

// All query parameters
$query = $request->query();
```

### Request Method

```php
// Get method
$method = $request->method();

// Check method
if ($request->isMethod('post')) {
    //
}

// Get intended method (handles method spoofing)
$method = $request->getRealMethod();
```

## Request Path & URL

### Path Info

```php
// Get path
$path = $request->path(); // "posts/123"

// Check path
if ($request->is('posts/*')) {
    //
}

// Get URL
$url = $request->url(); // "https://example.com/posts/123"

// Get full URL with query string
$url = $request->fullUrl(); // "https://example.com/posts/123?sort=date"
```

## Headers

### Access Headers

```php
// Get header
$token = $request->header('Authorization');

// With default
$token = $request->header('X-Custom-Header', 'default');

// Check header
if ($request->hasHeader('Authorization')) {
    //
}

// Get bearer token
$token = $request->bearerToken();
```

## IP Address

### Get Client IP

```php
$ip = $request->ip();

// Get all IPs (proxies)
$ips = $request->ips();
```

## Content Negotiation

### Accept Header

```php
// Check accepted content types
if ($request->accepts(['application/json', 'text/html'])) {
    //
}

// Prefer JSON
if ($request->prefers(['json', 'html']) === 'json') {
    return response()->json($data);
}

// Expects JSON
if ($request->expectsJson()) {
    return response()->json($data);
}
```

## File Uploads

### Access Files

```php
// Get uploaded file
$file = $request->file('photo');

// Check if file exists
if ($request->hasFile('photo')) {
    //
}

// Check if valid
if ($request->file('photo')->isValid()) {
    //
}

// Get file info
$name = $file->getClientOriginalName();
$extension = $file->getClientOriginalExtension();
$mimeType = $file->getMimeType();
$size = $file->getSize();

// Store file
$path = $file->store('photos');
$path = $file->store('photos', 's3'); // Custom disk
$path = $file->storeAs('photos', 'filename.jpg');
```

## Validation

### Validate Request

```php
public function store(Request $request): Response
{
    $validated = $request->validate([
        'title' => 'required|max:255',
        'body' => 'required',
        'publish_at' => 'nullable|date',
    ]);
    
    // Use validated data
    $post = Post::create($validated);
    
    return redirect("/posts/{$post->id}");
}
```

### Custom Error Messages

```php
$validated = $request->validate([
    'title' => 'required|max:255',
    'email' => 'required|email',
], [
    'title.required' => 'A title is required',
    'email.email' => 'Must be a valid email address',
]);
```

### Validation Errors

```php
if ($validator->fails()) {
    return back()
        ->withErrors($validator)
        ->withInput();
}

// In Blade
@error('email')
    <div class="alert alert-danger">{{ $message }}</div>
@enderror
```

## Request Data

### JSON Requests

```php
// Get JSON input
$name = $request->input('user.name');

// Decode JSON
$data = $request->json()->all();

// Get specific JSON value
$name = $request->json('user.name');
```

## Session

### Access Session

```php
// Get session value
$value = $request->session()->get('key');

// With default
$value = $request->session()->get('key', 'default');

// Store session value
$request->session()->put('key', 'value');

// Flash data for next request
$request->session()->flash('status', 'Task completed!');

// Get flashed data
$value = $request->session()->get('status');
```

## Cookies

### Access Cookies

```php
// Get cookie
$value = $request->cookie('name');

// Queue cookie for next response
cookie()->queue('name', 'value', $minutes);
```

## Old Input

### Flash Input

```php
// Flash all input
$request->flash();

// Flash only
$request->flashOnly(['name', 'email']);

// Flash except
$request->flashExcept(['password']);

// Retrieve old input
$name = $request->old('name');
```

## Request Helpers

### Convenience Methods

```php
// Check if AJAX
if ($request->ajax()) {
    //
}

// Check if wants JSON
if ($request->wantsJson()) {
    //
}

// Check if HTTPS
if ($request->secure()) {
    //
}

// Get user agent
$userAgent = $request->userAgent();

// Get referer
$referer = $request->header('referer');
```

## Best Practices

1. **Validate Early** - Validate all input
2. **Type Hints** - Always type hint Request parameter
3. **Old Input** - Flash input on validation errors
4. **Sanitize** - Sanitize user input
5. **File Validation** - Validate file uploads
6. **Security** - Never trust user input
7. **Error Messages** - Provide clear validation messages

## See Also

- [Validation](validation.md)
- [Responses](responses.md)
- [Controllers](controllers.md)
- [File Storage](../advanced/storage.md)
