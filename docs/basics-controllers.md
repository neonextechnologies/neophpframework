# Controllers

Handle HTTP requests and return responses.

## Creating Controllers

### Basic Controller

```php
namespace App\Controllers;

use NeoPhp\Http\Request;
use NeoPhp\Http\Response;

class PostController
{
    public function index(): Response
    {
        $posts = Post::all();
        return view('posts.index', compact('posts'));
    }
    
    public function show(int $id): Response
    {
        $post = Post::findOrFail($id);
        return view('posts.show', compact('post'));
    }
}
```

### Resource Controller

```php
class PostController
{
    public function index(): Response
    {
        $posts = Post::paginate(15);
        return view('posts.index', compact('posts'));
    }
    
    public function create(): Response
    {
        return view('posts.create');
    }
    
    public function store(Request $request): Response
    {
        $validated = $request->validate([
            'title' => 'required|max:255',
            'content' => 'required',
        ]);
        
        $post = Post::create($validated);
        
        return redirect("/posts/{$post->id}")
            ->with('success', 'Post created successfully');
    }
    
    public function edit(int $id): Response
    {
        $post = Post::findOrFail($id);
        return view('posts.edit', compact('post'));
    }
    
    public function update(Request $request, int $id): Response
    {
        $post = Post::findOrFail($id);
        
        $validated = $request->validate([
            'title' => 'required|max:255',
            'content' => 'required',
        ]);
        
        $post->update($validated);
        
        return redirect("/posts/{$post->id}")
            ->with('success', 'Post updated successfully');
    }
    
    public function destroy(int $id): Response
    {
        $post = Post::findOrFail($id);
        $post->delete();
        
        return redirect('/posts')
            ->with('success', 'Post deleted successfully');
    }
}
```

## Dependency Injection

### Constructor Injection

```php
class PostController
{
    public function __construct(
        private PostRepository $posts,
        private UserRepository $users
    ) {}
    
    public function index(): Response
    {
        $posts = $this->posts->paginate();
        return view('posts.index', compact('posts'));
    }
}
```

### Method Injection

```php
public function store(Request $request, PostService $service): Response
{
    $post = $service->create($request->validated());
    return redirect("/posts/{$post->id}");
}
```

## Route Model Binding

### Implicit Binding

```php
// Route
$router->get('/posts/{post}', [PostController::class, 'show']);

// Controller
public function show(Post $post): Response
{
    return view('posts.show', compact('post'));
}
```

### Custom Binding

```php
// Route
$router->get('/posts/{post:slug}', [PostController::class, 'show']);

// Controller (binds by slug instead of id)
public function show(Post $post): Response
{
    return view('posts.show', compact('post'));
}
```

## API Controllers

### JSON Response

```php
class ApiPostController
{
    public function index(): Response
    {
        $posts = Post::paginate(15);
        return response()->json($posts);
    }
    
    public function show(Post $post): Response
    {
        return response()->json($post);
    }
    
    public function store(Request $request): Response
    {
        $validated = $request->validate([
            'title' => 'required|max:255',
            'content' => 'required',
        ]);
        
        $post = Post::create($validated);
        
        return response()->json($post, 201);
    }
    
    public function update(Request $request, Post $post): Response
    {
        $validated = $request->validate([
            'title' => 'required|max:255',
            'content' => 'required',
        ]);
        
        $post->update($validated);
        
        return response()->json($post);
    }
    
    public function destroy(Post $post): Response
    {
        $post->delete();
        return response()->json(null, 204);
    }
}
```

## Controller Middleware

### Apply Middleware

```php
class PostController
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('verified')->only(['create', 'store']);
        $this->middleware('can:update,post')->only(['edit', 'update']);
    }
}
```

## Response Types

### Different Responses

```php
// View response
return view('posts.index', $data);

// JSON response
return response()->json($data);

// Redirect
return redirect('/posts');
return redirect()->back();
return redirect()->route('posts.show', ['id' => 1]);

// Download
return response()->download($pathToFile);
return response()->download($pathToFile, $name, $headers);

// File
return response()->file($pathToFile);

// Stream
return response()->stream($callback, 200, $headers);
```

## Best Practices

1. **Single Responsibility** - Each method should do one thing
2. **Thin Controllers** - Move business logic to services
3. **Type Hinting** - Always type hint parameters
4. **Validation** - Validate all input
5. **Authorization** - Check permissions
6. **Resource Controllers** - Use for CRUD operations
7. **Dependency Injection** - Inject dependencies

## See Also

- [Routing](routing.md)
- [Requests](requests.md)
- [Responses](responses.md)
- [Middleware](middleware.md)
