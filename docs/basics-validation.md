# Validation

Validate incoming HTTP request data.

## Basic Validation

### Validate Request

```php
public function store(Request $request): Response
{
    $validated = $request->validate([
        'title' => 'required|max:255',
        'body' => 'required',
        'author' => 'required|email',
    ]);
    
    // Use validated data
    Post::create($validated);
    
    return redirect('/posts');
}
```

## Validation Rules

### Common Rules

```php
$request->validate([
    // Required
    'name' => 'required',
    'email' => 'required|email',
    
    // String length
    'title' => 'min:3|max:255',
    'password' => 'between:8,20',
    
    // Numeric
    'age' => 'integer|min:18|max:100',
    'price' => 'numeric|between:0,999.99',
    
    // Unique in database
    'email' => 'unique:users,email',
    'username' => 'unique:users,username,' . $userId, // Except current user
    
    // Exists in database
    'category_id' => 'exists:categories,id',
    
    // Confirmed (password confirmation)
    'password' => 'required|confirmed',
    
    // Boolean
    'accept_terms' => 'accepted',
    'active' => 'boolean',
    
    // Date
    'birth_date' => 'date',
    'start_date' => 'date|after:today',
    'end_date' => 'date|after:start_date',
    
    // File
    'avatar' => 'file|image|max:2048', // Max 2MB
    'document' => 'file|mimes:pdf,doc,docx|max:10240',
    
    // Array
    'tags' => 'array',
    'tags.*' => 'string|max:50',
    
    // URL
    'website' => 'url',
    
    // IP Address
    'ip_address' => 'ip',
    
    // JSON
    'metadata' => 'json',
    
    // Regular Expression
    'phone' => 'regex:/^[0-9]{10}$/',
]);
```

### Conditional Rules

```php
$request->validate([
    'email' => 'required_if:contact_method,email',
    'phone' => 'required_unless:contact_method,email',
    'address' => 'required_with:city,state',
    'zip_code' => 'required_with_all:address,city,state',
    'apartment' => 'required_without:house_number',
]);
```

## Custom Error Messages

### Specify Messages

```php
$validated = $request->validate([
    'title' => 'required|max:255',
    'email' => 'required|email',
], [
    'title.required' => 'A title is required for the post',
    'title.max' => 'The title cannot exceed 255 characters',
    'email.required' => 'An email address is required',
    'email.email' => 'The email must be a valid email address',
]);
```

### Custom Attribute Names

```php
$validated = $request->validate([
    'email' => 'required|email',
], [], [
    'email' => 'email address',
]);
// Error: "The email address field is required."
```

## Displaying Errors

### In Blade Templates

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

{{-- For specific field --}}
@error('email')
    <div class="alert alert-danger">{{ $message }}</div>
@enderror

{{-- Old input --}}
<input type="text" name="name" value="{{ old('name') }}">
```

## Form Request Validation

### Create Form Request

```php
namespace App\Requests;

use NeoPhp\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::check();
    }
    
    public function rules(): array
    {
        return [
            'title' => 'required|max:255',
            'body' => 'required',
            'category_id' => 'required|exists:categories,id',
        ];
    }
    
    public function messages(): array
    {
        return [
            'title.required' => 'A title is required',
            'category_id.exists' => 'Invalid category selected',
        ];
    }
}
```

### Use Form Request

```php
public function store(StorePostRequest $request): Response
{
    // Automatically validated
    $validated = $request->validated();
    
    Post::create($validated);
    
    return redirect('/posts');
}
```

## Custom Validation Rules

### Closure Rules

```php
$request->validate([
    'email' => [
        'required',
        'email',
        function ($attribute, $value, $fail) {
            if (User::where('email', $value)->exists()) {
                $fail('The ' . $attribute . ' is already taken.');
            }
        },
    ],
]);
```

### Rule Objects

```php
namespace App\Rules;

use NeoPhp\Validation\Rule;

class Uppercase implements Rule
{
    public function passes($attribute, $value): bool
    {
        return strtoupper($value) === $value;
    }
    
    public function message(): string
    {
        return 'The :attribute must be uppercase.';
    }
}

// Usage
$request->validate([
    'name' => ['required', new Uppercase],
]);
```

## Conditional Validation

### Sometimes Validate

```php
$validator = Validator::make($data, [
    'email' => 'required|email',
]);

$validator->sometimes('reason', 'required|max:500', function ($input) {
    return $input->games >= 100;
});
```

## Array Validation

### Validate Array Items

```php
$request->validate([
    'photos' => 'required|array',
    'photos.*' => 'image|max:2048',
    
    'users' => 'array',
    'users.*.name' => 'required|string',
    'users.*.email' => 'required|email|unique:users,email',
]);
```

## After Validation Hook

### Post-Validation Logic

```php
$validator = Validator::make($data, $rules);

$validator->after(function ($validator) {
    if ($this->somethingElseIsInvalid()) {
        $validator->errors()->add('field', 'Something is wrong!');
    }
});

if ($validator->fails()) {
    return back()->withErrors($validator)->withInput();
}
```

## Validating Passwords

### Password Rules

```php
use NeoPhp\Validation\Rules\Password;

$request->validate([
    'password' => [
        'required',
        'confirmed',
        Password::min(8)
            ->mixedCase()
            ->numbers()
            ->symbols()
            ->uncompromised(),
    ],
]);
```

## Manual Validation

### Create Validator

```php
use NeoPhp\Validation\Validator;

$validator = Validator::make($request->all(), [
    'title' => 'required|max:255',
    'body' => 'required',
]);

if ($validator->fails()) {
    return back()
        ->withErrors($validator)
        ->withInput();
}

$validated = $validator->validated();
```

## Best Practices

1. **Form Requests** - Use Form Requests for complex validation
2. **Custom Rules** - Create reusable custom rules
3. **Clear Messages** - Provide clear error messages
4. **Validate Early** - Validate as soon as possible
5. **Type Safety** - Use strict validation rules
6. **Sanitization** - Sanitize input after validation
7. **Security** - Always validate and never trust user input

## See Also

- [Requests](requests.md)
- [Controllers](controllers.md)
- [Security Best Practices](../security/best-practices.md)
