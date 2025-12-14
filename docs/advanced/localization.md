# Localization

Multi-language support for your application.

## Configuration

### Setup Languages

```php
// config/app.php
return [
    'locale' => 'en',
    'fallback_locale' => 'en',
    'available_locales' => ['en', 'th', 'ja', 'zh'],
];
```

## Translation Files

### Create Translation Files

```php
// lang/en/messages.php
return [
    'welcome' => 'Welcome to our application',
    'goodbye' => 'Goodbye!',
];

// lang/th/messages.php
return [
    'welcome' => 'ยินดีต้อนรับสู่แอปพลิเคชันของเรา',
    'goodbye' => 'ลาก่อน!',
];
```

### Nested Translations

```php
// lang/en/auth.php
return [
    'failed' => 'These credentials do not match our records.',
    'throttle' => 'Too many login attempts. Please try again in :seconds seconds.',
    'password' => [
        'reset' => 'Your password has been reset!',
        'sent' => 'We have emailed your password reset link!',
    ],
];
```

## Using Translations

### Basic Usage

```php
use NeoPhp\Localization\Trans;

// Get translation
echo Trans::get('messages.welcome');
echo __('messages.welcome');

// With parameters
echo Trans::get('auth.throttle', ['seconds' => 60]);
echo __('auth.throttle', ['seconds' => 60]);

// Nested keys
echo __('auth.password.reset');
```

### Pluralization

```php
// lang/en/messages.php
return [
    'apples' => '{0} There are none|{1} There is one|[2,*] There are :count',
];

// Usage
echo Trans::choice('messages.apples', 0); // "There are none"
echo Trans::choice('messages.apples', 1); // "There is one"
echo Trans::choice('messages.apples', 5); // "There are 5"
```

## Changing Locale

### Set Current Locale

```php
use NeoPhp\Localization\App;

// Set locale
App::setLocale('th');

// Get current locale
$locale = App::getLocale(); // "th"

// Check locale
if (App::isLocale('th')) {
    // Current locale is Thai
}
```

### Locale Middleware

```php
class SetLocale
{
    public function handle($request, $next)
    {
        $locale = $request->segment(1);
        
        if (in_array($locale, config('app.available_locales'))) {
            App::setLocale($locale);
        }
        
        return $next($request);
    }
}
```

## JSON Translations

### Translation Keys

```json
// lang/en.json
{
    "Welcome": "Welcome",
    "Login": "Login",
    "Email": "Email Address"
}

// lang/th.json
{
    "Welcome": "ยินดีต้อนรับ",
    "Login": "เข้าสู่ระบบ",
    "Email": "ที่อยู่อีเมล"
}
```

### Usage

```php
echo __('Welcome'); // Auto-translated
```

## Blade Directives

### Translation in Views

```blade
<h1>{{ __('messages.welcome') }}</h1>

<p>@lang('messages.goodbye')</p>

<p>{{ __('There are :count apples', ['count' => 5]) }}</p>

@choice('messages.apples', 5)
```

## Language Switcher

### Implement Switcher

```php
class LanguageController
{
    public function switch(string $locale): Response
    {
        if (in_array($locale, config('app.available_locales'))) {
            session()->put('locale', $locale);
            App::setLocale($locale);
        }
        
        return redirect()->back();
    }
}
```

### Blade Component

```blade
<div class="language-switcher">
    @foreach(config('app.available_locales') as $locale)
        <a href="/language/{{ $locale }}" 
           class="{{ App::getLocale() === $locale ? 'active' : '' }}">
            {{ strtoupper($locale) }}
        </a>
    @endforeach
</div>
```

## Database Translations

### Translatable Model

```php
#[Entity]
class Post
{
    #[Column(type: 'primary')]
    public int $id;
    
    #[Column(type: 'json')]
    private string $title_translations;
    
    #[Column(type: 'json')]
    private string $content_translations;
    
    public function getTitle(?string $locale = null): string
    {
        $locale = $locale ?? App::getLocale();
        $translations = json_decode($this->title_translations, true);
        return $translations[$locale] ?? $translations[config('app.fallback_locale')];
    }
    
    public function setTitle(string $value, string $locale): void
    {
        $translations = json_decode($this->title_translations ?? '{}', true);
        $translations[$locale] = $value;
        $this->title_translations = json_encode($translations);
    }
}
```

### Usage

```php
$post = new Post();
$post->setTitle('Hello World', 'en');
$post->setTitle('สวัสดีชาวโลก', 'th');

App::setLocale('th');
echo $post->getTitle(); // "สวัสดีชาวโลก"
```

## Date & Time Localization

### Format Dates

```php
use Carbon\Carbon;

Carbon::setLocale('th');

$date = Carbon::now();
echo $date->translatedFormat('l j F Y'); // "วันจันทร์ 14 ธันวาคม 2025"

// Relative time
echo $date->diffForHumans(); // "1 วันที่แล้ว"
```

## Number Formatting

### Format Numbers

```php
use NumberFormatter;

$locale = App::getLocale();
$fmt = new NumberFormatter($locale, NumberFormatter::DECIMAL);

echo $fmt->format(1234567.89); // "1,234,567.89" (en)
                                // "1 234 567,89" (fr)

// Currency
$fmt = new NumberFormatter($locale, NumberFormatter::CURRENCY);
echo $fmt->formatCurrency(1234.56, 'USD'); // "$1,234.56"
```

## Best Practices

1. **Fallback Locale** - Always set a fallback locale
2. **Key Convention** - Use dot notation for nested keys
3. **Parameterized** - Use parameters instead of concatenation
4. **Cache Translations** - Cache loaded translations
5. **RTL Support** - Consider right-to-left languages
6. **Professional Translation** - Use professional translators
7. **Consistent Keys** - Use consistent naming conventions

## See Also

- [Configuration](../configuration.md)
- [Views](../basics/views.md)
- [Middleware](../basics/middleware.md)
