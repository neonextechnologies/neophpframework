# HTTP Responses

Return HTTP responses from your application.

## Creating Responses

### Basic Responses

```php
// String response
return 'Hello World';

// Array (converted to JSON)
return ['name' => 'John', 'age' => 30];

// Response object
return response('Hello World', 200)
    ->header('Content-Type', 'text/plain');
```

## Response Types

### View Responses

```php
// Return view
return view('posts.index', ['posts' => $posts]);

// With data
return view('posts.show')
    ->with('post', $post)
    ->with('comments', $comments);
```

### JSON Responses

```php
// JSON response
return response()->json(['name' => 'John', 'age' => 30]);

// With status code
return response()->json(['error' => 'Not Found'], 404);

// JSONP
return response()->jsonp('callback', ['name' => 'John']);
```

### File Responses

```php
// Download file
return response()->download($pathToFile);
return response()->download($pathToFile, $name, $headers);

// Display file
return response()->file($pathToFile);
return response()->file($pathToFile, $headers);

// Stream response
return response()->stream(function () {
    echo 'Hello World';
}, 200, $headers);
```

## Redirects

### Redirect Responses

```php
// Basic redirect
return redirect('/home');

// Redirect back
return redirect()->back();

// Redirect to named route
return redirect()->route('posts.show', ['id' => 1]);

// Redirect to controller action
return redirect()->action([PostController::class, 'show'], ['id' => 1]);

// Redirect to external URL
return redirect()->away('https://example.com');
```

### Flash Data

```php
// With flash data
return redirect('/home')->with('status', 'Profile updated!');

// With input
return redirect()->back()->withInput();

// With errors
return redirect()->back()->withErrors($validator);
```

## Response Headers

### Setting Headers

```php
// Single header
return response('Content')
    ->header('Content-Type', 'text/plain')
    ->header('X-Custom-Header', 'Value');

// Multiple headers
return response('Content')->withHeaders([
    'Content-Type' => 'text/plain',
    'X-Custom-Header' => 'Value',
]);
```

### Cookie Responses

```php
// Attach cookie
return response('Hello World')
    ->cookie('name', 'value', $minutes);

// Cookie with options
return response('Hello World')
    ->cookie('name', 'value', $minutes, $path, $domain, $secure, $httpOnly);

// Queue cookie
cookie()->queue('name', 'value', $minutes);
return response('Hello World');

// Forget cookie
return response('Hello World')
    ->withoutCookie('name');
```

## Response Macros

### Custom Response Types

```php
// Register macro
Response::macro('caps', function ($value) {
    return response()->make(strtoupper($value));
});

// Use macro
return response()->caps('hello'); // "HELLO"
```

## Status Codes

### HTTP Status Codes

```php
// 200 OK (default)
return response('Content');

// 201 Created
return response()->json($data, 201);

// 204 No Content
return response()->noContent();

// 301 Moved Permanently
return redirect('/new-url', 301);

// 302 Found (temporary redirect)
return redirect('/temp-url', 302);

// 400 Bad Request
return response('Bad Request', 400);

// 401 Unauthorized
return response('Unauthorized', 401);

// 403 Forbidden
return response('Forbidden', 403);

// 404 Not Found
return response('Not Found', 404);

// 500 Internal Server Error
return response('Server Error', 500);
```

## Response Caching

### Cache Control

```php
return response('Content')
    ->header('Cache-Control', 'public, max-age=3600')
    ->header('Expires', gmdate('D, d M Y H:i:s', time() + 3600) . ' GMT');

// No cache
return response('Content')
    ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
    ->header('Pragma', 'no-cache')
    ->header('Expires', '0');
```

## Streaming Responses

### Stream Large Content

```php
return response()->stream(function () {
    $handle = fopen('large-file.csv', 'r');
    
    while (($line = fgets($handle)) !== false) {
        echo $line;
        ob_flush();
        flush();
    }
    
    fclose($handle);
}, 200, [
    'Content-Type' => 'text/csv',
    'Content-Disposition' => 'attachment; filename="export.csv"',
]);
```

### Chunked Responses

```php
return response()->stream(function () {
    for ($i = 1; $i <= 1000; $i++) {
        echo "Line {$i}\n";
        
        if ($i % 100 === 0) {
            ob_flush();
            flush();
            usleep(100000); // 0.1 second
        }
    }
}, 200, ['Content-Type' => 'text/plain']);
```

## Response Testing

### Assert Responses

```php
$response = $this->get('/');

$response->assertStatus(200);
$response->assertOk();
$response->assertSuccessful();
$response->assertRedirect('/home');
$response->assertHeader('Content-Type', 'application/json');
$response->assertCookie('name');
$response->assertJson(['name' => 'John']);
$response->assertJsonStructure(['data' => ['id', 'name']]);
$response->assertViewIs('posts.index');
$response->assertViewHas('post');
```

## Best Practices

1. **Status Codes** - Use appropriate HTTP status codes
2. **Consistency** - Keep response format consistent
3. **Headers** - Set appropriate headers
4. **Caching** - Use caching headers when appropriate
5. **Streaming** - Stream large responses
6. **Error Handling** - Return meaningful error responses
7. **Security Headers** - Include security headers

## See Also

- [Requests](requests.md)
- [Controllers](controllers.md)
- [Views](views.md)
- [API Documentation](../advanced/api.md)
