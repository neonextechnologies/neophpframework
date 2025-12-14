# Helper Functions

Convenient utility functions for common tasks.

## Array Helpers

### array_add()

```php
// Add element if key doesn't exist
$array = ['name' => 'John'];
$array = array_add($array, 'age', 30);
// ['name' => 'John', 'age' => 30]
```

### array_get()

```php
// Get value with default
$array = ['user' => ['name' => 'John']];
$name = array_get($array, 'user.name'); // 'John'
$age = array_get($array, 'user.age', 25); // 25 (default)
```

### array_has()

```php
// Check if key exists
$array = ['user' => ['name' => 'John']];
array_has($array, 'user.name'); // true
array_has($array, 'user.age'); // false
```

### array_only()

```php
// Get only specified keys
$array = ['name' => 'John', 'age' => 30, 'email' => 'john@example.com'];
$filtered = array_only($array, ['name', 'email']);
// ['name' => 'John', 'email' => 'john@example.com']
```

### array_except()

```php
// Get all except specified keys
$array = ['name' => 'John', 'age' => 30, 'email' => 'john@example.com'];
$filtered = array_except($array, ['age']);
// ['name' => 'John', 'email' => 'john@example.com']
```

### array_flatten()

```php
// Flatten multidimensional array
$array = ['name' => 'John', 'languages' => ['PHP', 'JavaScript']];
$flattened = array_flatten($array);
// ['John', 'PHP', 'JavaScript']
```

## String Helpers

### str_contains()

```php
str_contains('Hello World', 'World'); // true
str_contains('Hello World', 'PHP'); // false
```

### str_starts_with()

```php
str_starts_with('Hello World', 'Hello'); // true
str_starts_with('Hello World', 'World'); // false
```

### str_ends_with()

```php
str_ends_with('Hello World', 'World'); // true
str_ends_with('Hello World', 'Hello'); // false
```

### str_limit()

```php
// Limit string length
str_limit('The quick brown fox', 10); // 'The quick...'
str_limit('The quick brown fox', 10, '>>>'); // 'The quick>>>'
```

### str_slug()

```php
// Convert to URL-friendly slug
str_slug('Hello World'); // 'hello-world'
str_slug('Hello World', '_'); // 'hello_world'
```

### str_random()

```php
// Generate random string
str_random(); // 'x4Sk2P9d3f'
str_random(10); // '5f3c8a2b1e'
```

### camel_case()

```php
camel_case('hello_world'); // 'helloWorld'
camel_case('hello-world'); // 'helloWorld'
```

### snake_case()

```php
snake_case('helloWorld'); // 'hello_world'
snake_case('HelloWorld'); // 'hello_world'
```

### studly_case()

```php
studly_case('hello_world'); // 'HelloWorld'
studly_case('hello-world'); // 'HelloWorld'
```

### title_case()

```php
title_case('hello world'); // 'Hello World'
```

## Path Helpers

### app_path()

```php
// Get app directory path
app_path(); // '/var/www/app'
app_path('Controllers/UserController.php');
```

### base_path()

```php
// Get base directory path
base_path(); // '/var/www'
base_path('config/app.php');
```

### config_path()

```php
// Get config directory path
config_path(); // '/var/www/config'
config_path('database.php');
```

### public_path()

```php
// Get public directory path
public_path(); // '/var/www/public'
public_path('css/app.css');
```

### storage_path()

```php
// Get storage directory path
storage_path(); // '/var/www/storage'
storage_path('logs/app.log');
```

### resource_path()

```php
// Get resources directory path
resource_path(); // '/var/www/resources'
resource_path('views/welcome.blade.php');
```

## URL Helpers

### url()

```php
// Generate URL
url(); // 'http://example.com'
url('/posts/1'); // 'http://example.com/posts/1'
url('/posts', ['id' => 1]); // 'http://example.com/posts?id=1'
```

### route()

```php
// Generate route URL
route('posts.show', ['id' => 1]); // 'http://example.com/posts/1'
route('profile', ['user' => 1]); // 'http://example.com/users/1/profile'
```

### asset()

```php
// Generate asset URL
asset('css/app.css'); // 'http://example.com/css/app.css'
asset('js/app.js'); // 'http://example.com/js/app.js'
```

### secure_url()

```php
// Generate HTTPS URL
secure_url('posts/1'); // 'https://example.com/posts/1'
```

## Response Helpers

### response()

```php
// Create response
response('Hello World');
response()->json(['name' => 'John']);
response()->download($filePath);
```

### redirect()

```php
// Create redirect
redirect('/home');
redirect()->back();
redirect()->route('posts.show', ['id' => 1]);
```

### view()

```php
// Create view response
view('posts.index');
view('posts.show', ['post' => $post]);
```

### abort()

```php
// Abort with HTTP status
abort(404);
abort(403, 'Unauthorized action.');
abort_if($user->banned, 403);
abort_unless($user->isAdmin(), 403);
```

## Value Helpers

### value()

```php
// Return value or closure result
value(10); // 10
value(function () {
    return 10;
}); // 10
```

### with()

```php
// Return value (useful for chaining)
with(new User)->save();
```

### tap()

```php
// Tap into value and return it
tap($user, function ($user) {
    $user->name = 'John';
}); // Returns $user
```

### optional()

```php
// Safely access properties
optional($user)->name; // Returns name or null
optional(null)->name; // Returns null (no error)
```

### data_get()

```php
// Get data with dot notation
$data = ['user' => ['name' => 'John', 'age' => 30]];
data_get($data, 'user.name'); // 'John'
data_get($data, 'user.email', 'default@example.com'); // 'default@example.com'
```

### data_set()

```php
// Set data with dot notation
$data = ['user' => ['name' => 'John']];
data_set($data, 'user.age', 30);
// ['user' => ['name' => 'John', 'age' => 30]]
```

## Collection Helpers

### collect()

```php
// Create collection
$collection = collect([1, 2, 3]);
$collection->map(function ($item) {
    return $item * 2;
});
```

## Miscellaneous

### env()

```php
// Get environment variable
env('APP_ENV'); // 'production'
env('DB_HOST', 'localhost'); // With default
```

### config()

```php
// Get configuration value
config('app.name'); // 'NeoPhp Framework'
config('database.default'); // 'mysql'

// Set configuration
config(['app.debug' => true]);
```

### cache()

```php
// Get from cache
cache('key');

// Store in cache
cache(['key' => 'value'], 3600);

// Remember
cache()->remember('users', 3600, function () {
    return User::all();
});
```

### session()

```php
// Get session value
session('key');

// Store session value
session(['key' => 'value']);

// Flash session value
session()->flash('status', 'Success!');
```

### logger()

```php
// Log message
logger('User logged in', ['user_id' => 1]);
logger()->info('Processing payment');
logger()->error('Payment failed', ['order_id' => 123]);
```

### now()

```php
// Get current date/time
now(); // Carbon instance
now()->addDays(7);
now()->format('Y-m-d');
```

### today()

```php
// Get today's date
today(); // Carbon instance (time set to 00:00:00)
```

### dd() / dump()

```php
// Dump and die
dd($variable);

// Dump
dump($variable);
```

### blank() / filled()

```php
// Check if blank
blank(''); // true
blank(null); // true
blank('   '); // true
blank('text'); // false

// Check if filled
filled('text'); // true
filled(''); // false
```

## Best Practices

1. **Consistency** - Use helpers for consistent code
2. **Readability** - Helpers improve code readability
3. **Type Safety** - Be aware of return types
4. **Performance** - Some helpers add overhead
5. **Documentation** - Document custom helpers
6. **Namespacing** - Avoid conflicts with custom functions
7. **Testing** - Test custom helpers thoroughly

## See Also

- [Collections](collections.md)
- [Strings](https://www.php.net/manual/en/ref.strings.php)
- [Arrays](https://www.php.net/manual/en/ref.array.php)
