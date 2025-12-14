# Database Pagination

Paginate database query results efficiently.

## Basic Pagination

### Paginate Results

```php
// Paginate with 15 items per page
$users = DB::table('users')->paginate(15);

// Custom per page
$users = DB::table('users')->paginate($perPage = 20);

// Get current page from request
$page = request()->get('page', 1);
$users = DB::table('users')->paginate(15, $page);
```

### Access Paginated Data

```php
foreach ($users as $user) {
    echo $user->name;
}

// Pagination info
$users->total();        // Total items
$users->perPage();      // Items per page
$users->currentPage();  // Current page number
$users->lastPage();     // Last page number
$users->hasMorePages(); // Has more pages?
$users->nextPageUrl();  // Next page URL
$users->previousPageUrl(); // Previous page URL
```

## Simple Pagination

### Previous/Next Only

```php
// Simple pagination (no total count)
$users = DB::table('users')->simplePaginate(15);

// Faster for large datasets
$users = User::where('active', true)->simplePaginate(20);
```

## Cursor Pagination

### Efficient Large Datasets

```php
// Cursor-based pagination
$users = DB::table('users')
    ->orderBy('id')
    ->cursorPaginate(15);

// Next page
$cursor = request()->get('cursor');
$users = DB::table('users')
    ->orderBy('id')
    ->cursorPaginate(15, $cursor);
```

## Custom Pagination

### Manual Pagination

```php
class Paginator
{
    public static function make(array $items, int $total, int $perPage, int $currentPage): array
    {
        return [
            'data' => array_slice($items, 0, $perPage),
            'current_page' => $currentPage,
            'per_page' => $perPage,
            'total' => $total,
            'last_page' => ceil($total / $perPage),
            'from' => (($currentPage - 1) * $perPage) + 1,
            'to' => min($currentPage * $perPage, $total),
        ];
    }
}

// Usage
$page = request()->get('page', 1);
$perPage = 15;
$offset = ($page - 1) * $perPage;

$users = DB::table('users')
    ->offset($offset)
    ->limit($perPage)
    ->get();

$total = DB::table('users')->count();

$paginated = Paginator::make($users, $total, $perPage, $page);
```

## API Pagination

### JSON Response

```php
class UserController
{
    public function index(Request $request): Response
    {
        $users = User::paginate($request->get('per_page', 15));
        
        return response()->json([
            'data' => $users->items(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'last_page' => $users->lastPage(),
            ],
            'links' => [
                'first' => $users->url(1),
                'last' => $users->url($users->lastPage()),
                'prev' => $users->previousPageUrl(),
                'next' => $users->nextPageUrl(),
            ],
        ]);
    }
}
```

## Pagination Links

### Generate Links

```php
// In Blade template
{{ $users->links() }}

// Custom view
{{ $users->links('pagination.custom') }}

// Bootstrap 5
{{ $users->links('pagination.bootstrap-5') }}

// Tailwind
{{ $users->links('pagination.tailwind') }}
```

### Custom Pagination View

```blade
{{-- resources/views/pagination/custom.blade.php --}}
@if ($paginator->hasPages())
    <nav>
        <ul class="pagination">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <li class="disabled"><span>Previous</span></li>
            @else
                <li><a href="{{ $paginator->previousPageUrl() }}">Previous</a></li>
            @endif

            {{-- Pagination Elements --}}
            @foreach ($elements as $element)
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <li class="active"><span>{{ $page }}</span></li>
                        @else
                            <li><a href="{{ $url }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <li><a href="{{ $paginator->nextPageUrl() }}">Next</a></li>
            @else
                <li class="disabled"><span>Next</span></li>
            @endif
        </ul>
    </nav>
@endif
```

## Appending Query Strings

### Preserve Query Parameters

```php
// Append to pagination links
$users = User::paginate(15)->appends([
    'sort' => 'name',
    'direction' => 'asc',
]);

// Append from request
$users = User::paginate(15)->appends(request()->query());

// Fragment
$users = User::paginate(15)->fragment('users');
// Generates: /users?page=2#users
```

## Pagination with Filtering

### Combined Filters

```php
public function index(Request $request): Response
{
    $query = User::query();
    
    if ($search = $request->get('search')) {
        $query->where('name', 'like', "%{$search}%");
    }
    
    if ($role = $request->get('role')) {
        $query->where('role', $role);
    }
    
    $users = $query->paginate(15)->appends($request->query());
    
    return view('users.index', compact('users'));
}
```

## Performance Optimization

### Index for Pagination

```sql
-- Index for efficient pagination
CREATE INDEX idx_users_created_at ON users(created_at DESC);

-- Composite index for filtered pagination
CREATE INDEX idx_users_status_created ON users(status, created_at DESC);
```

### Cursor Pagination for Large Datasets

```php
// Better for very large tables
$users = User::orderBy('id')
    ->cursorPaginate(100);

// No OFFSET, uses WHERE id > ?
// Much faster for large offsets
```

## Best Practices

1. **Reasonable Page Size** - Keep page size between 10-100 items
2. **Use Indexes** - Index columns used in ORDER BY
3. **Cursor for Large Sets** - Use cursor pagination for tables with millions of rows
4. **Cache Total Count** - Cache total count for expensive queries
5. **Limit Max Page** - Prevent accessing very high page numbers
6. **Simple for Performance** - Use simplePaginate when total count isn't needed
7. **Consistent Ordering** - Always use ORDER BY for predictable results

## See Also

- [Query Builder](query-builder.md)
- [Models](models.md)
- [API Documentation](../advanced/api.md)
