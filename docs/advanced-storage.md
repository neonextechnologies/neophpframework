# Storage & File Management

NeoPhp provides a powerful file storage abstraction with support for local filesystem, S3, and other cloud storage providers.

## Configuration

Configure storage disks in `config/storage.php`:

```php
return [
    'default' => env('FILESYSTEM_DRIVER', 'local'),

    'disks' => [
        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
            'visibility' => 'private',
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL') . '/storage',
            'visibility' => 'public',
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
        ],

        'cdn' => [
            'driver' => 'cdn',
            'url' => env('CDN_URL'),
            'path' => env('CDN_PATH'),
        ],
    ],
];
```

## Basic Usage

### Storing Files

```php
use NeoPhp\Storage\Storage;

// Store file from request
$file = $request->file('avatar');
$path = Storage::disk('public')->put('avatars', $file);

// Store with custom name
$path = Storage::disk('public')->putFileAs('avatars', $file, 'user-123.jpg');

// Store from string content
Storage::disk('local')->put('file.txt', 'Contents');

// Store from stream
$stream = fopen('/path/to/file.pdf', 'r');
Storage::disk('s3')->putStream('documents/file.pdf', $stream);
```

### Retrieving Files

```php
// Check if file exists
if (Storage::disk('public')->exists('avatars/user-123.jpg')) {
    // File exists
}

// Get file contents
$contents = Storage::disk('public')->get('file.txt');

// Download file
return Storage::disk('public')->download('documents/report.pdf');

// Download with custom name
return Storage::disk('public')->download('documents/report.pdf', 'monthly-report.pdf');

// Get file URL
$url = Storage::disk('public')->url('avatars/user-123.jpg');
// https://example.com/storage/avatars/user-123.jpg

// Get temporary URL (S3)
$url = Storage::disk('s3')->temporaryUrl('documents/invoice.pdf', now()->addMinutes(30));
```

### File Information

```php
// Get file size
$size = Storage::disk('public')->size('file.pdf');

// Get last modified time
$time = Storage::disk('public')->lastModified('file.pdf');

// Get MIME type
$mime = Storage::disk('public')->mimeType('image.jpg');
```

### Deleting Files

```php
// Delete single file
Storage::disk('public')->delete('avatars/old-avatar.jpg');

// Delete multiple files
Storage::disk('public')->delete([
    'avatars/user-1.jpg',
    'avatars/user-2.jpg',
]);

// Delete directory
Storage::disk('public')->deleteDirectory('uploads/temp');
```

### Directories

```php
// List files in directory
$files = Storage::disk('public')->files('avatars');

// List files recursively
$files = Storage::disk('public')->allFiles('uploads');

// List directories
$directories = Storage::disk('public')->directories('uploads');

// List all directories recursively
$directories = Storage::disk('public')->allDirectories('uploads');

// Create directory
Storage::disk('public')->makeDirectory('new-folder');

// Copy file
Storage::disk('public')->copy('old.jpg', 'new.jpg');

// Move file
Storage::disk('public')->move('old-path/file.jpg', 'new-path/file.jpg');
```

## File Uploads

### Single File Upload

```php
public function upload(Request $request): Response
{
    $validated = $request->validate([
        'file' => 'required|file|max:10240', // Max 10MB
    ]);

    $file = $request->file('file');
    
    // Store file
    $path = $file->store('uploads', 'public');

    return response()->json([
        'path' => $path,
        'url' => Storage::disk('public')->url($path),
    ]);
}
```

### Multiple File Upload

```php
public function uploadMultiple(Request $request): Response
{
    $validated = $request->validate([
        'files.*' => 'required|file|max:10240',
    ]);

    $paths = [];
    foreach ($request->file('files') as $file) {
        $paths[] = $file->store('uploads', 'public');
    }

    return response()->json([
        'paths' => $paths,
    ]);
}
```

### Validation Rules

```php
$request->validate([
    'avatar' => 'required|image|max:2048', // Image, max 2MB
    'document' => 'required|mimes:pdf,doc,docx|max:10240',
    'video' => 'required|mimetypes:video/avi,video/mpeg|max:51200',
]);
```

## Image Processing

### Resize Images

```php
use NeoPhp\Media\ImageProcessor;

$processor = new ImageProcessor();

// Resize image
$processor->load($file)
    ->resize(800, 600)
    ->save('public/images/resized.jpg');

// Maintain aspect ratio
$processor->load($file)
    ->resize(800, null) // Auto height
    ->save('public/images/resized.jpg');
```

### Image Manipulation

```php
// Crop image
$processor->load($file)
    ->crop(400, 400, 100, 100) // width, height, x, y
    ->save('public/images/cropped.jpg');

// Fit image (cover)
$processor->load($file)
    ->fit(300, 300, 'cover')
    ->save('public/images/thumbnail.jpg');

// Fit image (contain)
$processor->load($file)
    ->fit(300, 300, 'contain')
    ->save('public/images/thumbnail.jpg');

// Rotate image
$processor->load($file)
    ->rotate(90)
    ->save('public/images/rotated.jpg');

// Convert format
$processor->load($file)
    ->format('webp')
    ->save('public/images/image.webp');

// Adjust quality
$processor->load($file)
    ->quality(80)
    ->save('public/images/compressed.jpg');
```

### Multiple Sizes

```php
public function uploadAvatar(Request $request): Response
{
    $file = $request->file('avatar');
    $processor = new ImageProcessor();
    
    // Original
    $originalPath = $file->store('avatars/original', 'public');
    
    // Thumbnail
    $processor->load($file)
        ->fit(150, 150, 'cover')
        ->save(storage_path('app/public/avatars/thumbnail/user.jpg'));
    
    // Medium
    $processor->load($file)
        ->fit(400, 400, 'cover')
        ->save(storage_path('app/public/avatars/medium/user.jpg'));
    
    return response()->json([
        'original' => Storage::disk('public')->url($originalPath),
        'thumbnail' => '/storage/avatars/thumbnail/user.jpg',
        'medium' => '/storage/avatars/medium/user.jpg',
    ]);
}
```

## Amazon S3

### Upload to S3

```php
// Store file on S3
$path = Storage::disk('s3')->put('documents', $file);

// With public visibility
$path = Storage::disk('s3')->putFile('documents', $file, 'public');

// Get S3 URL
$url = Storage::disk('s3')->url($path);
```

### S3 Presigned URLs

```php
// Generate temporary download URL
$url = Storage::disk('s3')->temporaryUrl(
    'documents/private.pdf',
    now()->addMinutes(30)
);

// Generate temporary upload URL
$url = Storage::disk('s3')->temporaryUploadUrl(
    'uploads/file.pdf',
    now()->addMinutes(30)
);
```

### S3 Configuration

```env
AWS_ACCESS_KEY_ID=your-key
AWS_SECRET_ACCESS_KEY=your-secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket
AWS_URL=https://your-bucket.s3.amazonaws.com
```

## CDN Integration

### Configure CDN

```php
// config/storage.php
'cdn' => [
    'driver' => 'cdn',
    'url' => env('CDN_URL', 'https://cdn.example.com'),
    'path' => env('CDN_PATH', '/'),
],
```

### Use CDN URLs

```php
// Upload to public disk
$path = Storage::disk('public')->put('images', $file);

// Get CDN URL
$cdnUrl = Storage::disk('cdn')->url($path);
// https://cdn.example.com/images/file.jpg
```

## Streaming Downloads

### Stream Large Files

```php
public function download(int $id): Response
{
    $file = File::findOrFail($id);
    
    return Storage::disk('s3')->response($file->path, $file->name);
}

// Or use streaming
public function stream(int $id): Response
{
    $file = File::findOrFail($id);
    
    return response()->stream(function() use ($file) {
        $stream = Storage::disk('s3')->readStream($file->path);
        fpassthru($stream);
        fclose($stream);
    }, 200, [
        'Content-Type' => $file->mime_type,
        'Content-Disposition' => 'attachment; filename="' . $file->name . '"',
    ]);
}
```

## File Security

### Private Files

```php
// Store privately
$path = Storage::disk('local')->put('private/document.pdf', $file);

// Generate temporary access
public function downloadPrivate(int $id): Response
{
    $file = File::findOrFail($id);
    
    // Verify user has permission
    if (!auth()->user()->canDownload($file)) {
        abort(403);
    }
    
    return Storage::disk('local')->download($file->path);
}
```

### Virus Scanning

```php
use NeoPhp\Media\VirusScanner;

public function upload(Request $request): Response
{
    $file = $request->file('file');
    
    // Scan for viruses
    $scanner = new VirusScanner();
    if (!$scanner->isClean($file)) {
        return response()->json([
            'error' => 'File contains malware'
        ], 400);
    }
    
    $path = Storage::disk('public')->put('uploads', $file);
    
    return response()->json(['path' => $path]);
}
```

## Best Practices

1. **Use Appropriate Disks** - Public for images, private for documents
2. **Validate Files** - Check file type, size, and content
3. **Process Images** - Generate thumbnails and optimized versions
4. **Use CDN** - Serve static files from CDN
5. **Secure Private Files** - Check permissions before serving
6. **Scan for Malware** - Scan uploaded files
7. **Set Proper MIME Types** - Ensure correct content types
8. **Handle Large Files** - Use streaming for large downloads
9. **Clean Up Old Files** - Remove unused files periodically
10. **Use Queues** - Process uploads asynchronously

## See Also

- [Media Processing](media.md)
- [S3 Integration](s3.md)
- [CDN Support](cdn.md)
- [Image Optimization](../reference/image-optimization.md)
