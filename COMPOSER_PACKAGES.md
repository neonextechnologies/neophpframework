# NeoCore + Composer Packages

NeoCore รองรับการใช้งาน **Composer packages ทั้งหมด** เพราะเป็น PHP framework มาตรฐานที่รองรับ PSR-4

---

## 🎯 Packages แนะนำสำหรับ NeoCore

### 📦 ติดตั้ง Packages

```bash
cd d:\neonexserver\www\neophpframework\neocore

# Environment Variables
composer require vlucas/phpdotenv

# ORM
composer require cycle/orm cycle/database cycle/annotated

# Template Engine
composer require latte/latte
# หรือ
composer require league/plates

# Validation
composer require respect/validation
# หรือ
composer require rakit/validation

# Logging
composer require monolog/monolog

# HTTP Client
composer require guzzlehttp/guzzle

# DateTime
composer require nesbot/carbon

# UUID
composer require ramsey/uuid

# Email
composer require phpmailer/phpmailer
# หรือ
composer require symfony/mailer

# Filesystem
composer require league/flysystem

# Testing
composer require phpunit/phpunit --dev
```

---

## 💡 ตัวอย่างการใช้งาน Packages

### 1. DotEnv - Environment Variables

**ติดตั้ง:**
```bash
composer require vlucas/phpdotenv
```

**การใช้งาน:**

**public/index.php:**
```php
<?php

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

// ใช้งาน
$dbHost = $_ENV['DB_HOST'];
$dbName = $_ENV['DB_DATABASE'];
```

**.env:**
```env
APP_NAME="NeoCore Application"
APP_ENV=production
APP_DEBUG=false

DB_HOST=localhost
DB_DATABASE=neocore
DB_USERNAME=root
DB_PASSWORD=secret
```

---

### 2. Monolog - Advanced Logging

**ติดตั้ง:**
```bash
composer require monolog/monolog
```

**สร้าง Logger Service:**

**app/Services/Logger.php:**
```php
<?php

namespace App\Services;

use Monolog\Logger as MonologLogger;
use Monolog\Handler\StreamHandler;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Formatter\LineFormatter;

class Logger
{
    private static ?MonologLogger $logger = null;

    public static function instance(): MonologLogger
    {
        if (self::$logger === null) {
            self::$logger = new MonologLogger('neocore');

            // Daily rotating log files
            $handler = new RotatingFileHandler(
                STORAGE_PATH . '/logs/app.log',
                30, // Keep 30 days
                MonologLogger::DEBUG
            );

            $formatter = new LineFormatter(
                "[%datetime%] %channel%.%level_name%: %message% %context%\n",
                'Y-m-d H:i:s'
            );
            $handler->setFormatter($formatter);

            self::$logger->pushHandler($handler);
        }

        return self::$logger;
    }

    public static function info(string $message, array $context = []): void
    {
        self::instance()->info($message, $context);
    }

    public static function error(string $message, array $context = []): void
    {
        self::instance()->error($message, $context);
    }

    public static function warning(string $message, array $context = []): void
    {
        self::instance()->warning($message, $context);
    }

    public static function debug(string $message, array $context = []): void
    {
        self::instance()->debug($message, $context);
    }
}
```

**ใช้งาน:**
```php
<?php

use App\Services\Logger;

Logger::info('User logged in', ['user_id' => 123]);
Logger::error('Database connection failed', ['error' => $e->getMessage()]);
Logger::debug('API request', ['endpoint' => '/api/users']);
```

---

### 3. Respect/Validation - Advanced Validation

**ติดตั้ง:**
```bash
composer require respect/validation
```

**ใช้งาน:**

**app/Http/Controllers/UserController.php:**
```php
<?php

namespace App\Http\Controllers;

use Respect\Validation\Validator as v;
use Respect\Validation\Exceptions\ValidationException;
use NeoCore\System\Core\Controller;
use NeoCore\System\Core\Request;
use NeoCore\System\Core\Response;

class UserController extends Controller
{
    public function store(Request $request, Response $response): Response
    {
        $data = $request->all();

        try {
            // Advanced validation
            $validator = v::key('name', v::stringType()->length(3, 100))
                ->key('email', v::email()->length(null, 255))
                ->key('password', v::stringType()->length(8, null))
                ->key('age', v::optional(v::intVal()->min(18)->max(120)))
                ->key('website', v::optional(v::url()));

            $validator->assert($data);

        } catch (ValidationException $e) {
            return $this->respondValidationError($response, [
                'errors' => $e->getMessages()
            ]);
        }

        // Process data
        return $this->respondSuccess($response, $data, 'User created');
    }
}
```

---

### 4. Guzzle - HTTP Client

**ติดตั้ง:**
```bash
composer require guzzlehttp/guzzle
```

**ใช้งาน:**

**app/Services/ApiClient.php:**
```php
<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class ApiClient
{
    private Client $client;

    public function __construct()
    {
        $this->client = new Client([
            'base_uri' => 'https://api.example.com',
            'timeout' => 10.0,
            'headers' => [
                'Accept' => 'application/json',
                'User-Agent' => 'NeoCore/1.0'
            ]
        ]);
    }

    public function getUsers(): array
    {
        try {
            $response = $this->client->get('/users');
            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            Logger::error('API request failed', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    public function createUser(array $data): ?array
    {
        try {
            $response = $this->client->post('/users', [
                'json' => $data
            ]);
            return json_decode($response->getBody(), true);
        } catch (RequestException $e) {
            return null;
        }
    }
}
```

---

### 5. Carbon - DateTime Helper

**ติดตั้ง:**
```bash
composer require nesbot/carbon
```

**ใช้งาน:**
```php
<?php

use Carbon\Carbon;

// Current time
$now = Carbon::now();
echo $now->toDateTimeString(); // 2025-12-12 15:30:00

// Parse and format
$date = Carbon::parse('2025-01-01');
echo $date->format('d/m/Y'); // 01/01/2025
echo $date->diffForHumans(); // 1 month ago

// Add/Subtract
$tomorrow = Carbon::now()->addDay();
$lastWeek = Carbon::now()->subWeek();

// Comparisons
if ($date->isPast()) {
    echo 'Date is in the past';
}

// Use in models
class User extends Model
{
    public function getCreatedAtAttribute(): Carbon
    {
        return Carbon::parse($this->attributes['created_at']);
    }
}
```

---

### 6. PHPMailer - Email Sending

**ติดตั้ง:**
```bash
composer require phpmailer/phpmailer
```

**สร้าง Mail Service:**

**app/Services/Mailer.php:**
```php
<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class Mailer
{
    private PHPMailer $mail;

    public function __construct()
    {
        $this->mail = new PHPMailer(true);

        // SMTP Configuration
        $this->mail->isSMTP();
        $this->mail->Host = $_ENV['MAIL_HOST'] ?? 'smtp.gmail.com';
        $this->mail->SMTPAuth = true;
        $this->mail->Username = $_ENV['MAIL_USERNAME'];
        $this->mail->Password = $_ENV['MAIL_PASSWORD'];
        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mail->Port = $_ENV['MAIL_PORT'] ?? 587;
        $this->mail->CharSet = 'UTF-8';
    }

    public function send(string $to, string $subject, string $body): bool
    {
        try {
            $this->mail->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME']);
            $this->mail->addAddress($to);
            $this->mail->Subject = $subject;
            $this->mail->Body = $body;
            $this->mail->isHTML(true);

            return $this->mail->send();
        } catch (Exception $e) {
            Logger::error('Mail sending failed', [
                'error' => $this->mail->ErrorInfo
            ]);
            return false;
        }
    }

    public function sendWelcomeEmail(string $email, string $name): bool
    {
        $body = "
            <h1>Welcome, {$name}!</h1>
            <p>Thank you for joining our platform.</p>
        ";

        return $this->send($email, 'Welcome to NeoCore', $body);
    }
}
```

---

### 7. UUID - Unique Identifiers

**ติดตั้ง:**
```bash
composer require ramsey/uuid
```

**ใช้งาน:**
```php
<?php

use Ramsey\Uuid\Uuid;

// Generate UUID
$uuid = Uuid::uuid4();
echo $uuid->toString(); // e.g., 123e4567-e89b-12d3-a456-426614174000

// Use in models
class User extends Model
{
    protected string $primaryKey = 'uuid';

    public function insert(array $data): ?string
    {
        $data['uuid'] = Uuid::uuid4()->toString();
        return parent::insert($data);
    }
}
```

---

### 8. Flysystem - Filesystem Abstraction

**ติดตั้ง:**
```bash
composer require league/flysystem
composer require league/flysystem-aws-s3-v3  # For S3
```

**ใช้งาน:**

**app/Services/Storage.php:**
```php
<?php

namespace App\Services;

use League\Flysystem\Filesystem;
use League\Flysystem\Local\LocalFilesystemAdapter;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use Aws\S3\S3Client;

class Storage
{
    private Filesystem $filesystem;

    public function __construct(string $driver = 'local')
    {
        if ($driver === 's3') {
            $client = new S3Client([
                'credentials' => [
                    'key' => $_ENV['AWS_ACCESS_KEY_ID'],
                    'secret' => $_ENV['AWS_SECRET_ACCESS_KEY'],
                ],
                'region' => $_ENV['AWS_DEFAULT_REGION'],
                'version' => 'latest',
            ]);

            $adapter = new AwsS3V3Adapter($client, $_ENV['AWS_BUCKET']);
        } else {
            $adapter = new LocalFilesystemAdapter(STORAGE_PATH . '/uploads');
        }

        $this->filesystem = new Filesystem($adapter);
    }

    public function write(string $path, string $contents): bool
    {
        try {
            $this->filesystem->write($path, $contents);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function read(string $path): ?string
    {
        try {
            return $this->filesystem->read($path);
        } catch (\Exception $e) {
            return null;
        }
    }

    public function delete(string $path): bool
    {
        try {
            $this->filesystem->delete($path);
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function exists(string $path): bool
    {
        return $this->filesystem->fileExists($path);
    }
}
```

---

### 9. JWT - Authentication

**ติดตั้ง:**
```bash
composer require firebase/php-jwt
```

**ใช้งาน:**

**app/Services/JWTAuth.php:**
```php
<?php

namespace App\Services;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class JWTAuth
{
    private string $secret;

    public function __construct()
    {
        $this->secret = $_ENV['JWT_SECRET'] ?? 'your-secret-key';
    }

    public function createToken(array $payload): string
    {
        $payload['iat'] = time();
        $payload['exp'] = time() + (60 * 60 * 24); // 24 hours

        return JWT::encode($payload, $this->secret, 'HS256');
    }

    public function verifyToken(string $token): ?array
    {
        try {
            $decoded = JWT::decode($token, new Key($this->secret, 'HS256'));
            return (array)$decoded;
        } catch (\Exception $e) {
            return null;
        }
    }
}

// ใช้งาน
$jwt = new JWTAuth();

// Create token
$token = $jwt->createToken([
    'user_id' => 123,
    'email' => 'user@example.com'
]);

// Verify token
$payload = $jwt->verifyToken($token);
if ($payload) {
    echo $payload['user_id'];
}
```

---

### 10. Redis - Caching

**ติดตั้ง:**
```bash
composer require predis/predis
```

**ใช้งาน:**

**app/Services/Cache.php:**
```php
<?php

namespace App\Services;

use Predis\Client;

class Cache
{
    private Client $redis;

    public function __construct()
    {
        $this->redis = new Client([
            'scheme' => 'tcp',
            'host' => $_ENV['REDIS_HOST'] ?? '127.0.0.1',
            'port' => $_ENV['REDIS_PORT'] ?? 6379,
        ]);
    }

    public function get(string $key)
    {
        $value = $this->redis->get($key);
        return $value ? json_decode($value, true) : null;
    }

    public function set(string $key, $value, int $ttl = 3600): bool
    {
        $this->redis->setex($key, $ttl, json_encode($value));
        return true;
    }

    public function delete(string $key): bool
    {
        $this->redis->del([$key]);
        return true;
    }

    public function remember(string $key, int $ttl, callable $callback)
    {
        $value = $this->get($key);

        if ($value !== null) {
            return $value;
        }

        $value = $callback();
        $this->set($key, $value, $ttl);

        return $value;
    }
}

// ใช้งาน
$cache = new Cache();

// Cache user data
$user = $cache->remember('user:123', 3600, function() {
    return $userModel->find(123);
});
```

---

## 🔧 ตัวอย่าง Controller ที่ใช้หลาย Packages

**app/Http/Controllers/UserController.php:**
```php
<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use Ramsey\Uuid\Uuid;
use Respect\Validation\Validator as v;
use NeoCore\System\Core\Controller;
use NeoCore\System\Core\Request;
use NeoCore\System\Core\Response;
use App\Services\Logger;
use App\Services\Cache;
use App\Services\Mailer;

class UserController extends Controller
{
    private Cache $cache;
    private Mailer $mailer;

    public function __construct()
    {
        $this->cache = new Cache();
        $this->mailer = new Mailer();
    }

    public function store(Request $request, Response $response): Response
    {
        $data = $request->all();

        // Validation with Respect/Validation
        try {
            $validator = v::key('name', v::stringType()->length(3, 100))
                ->key('email', v::email());
            $validator->assert($data);
        } catch (\Exception $e) {
            Logger::warning('Validation failed', ['errors' => $e->getMessage()]);
            return $this->respondError($response, 'Validation failed', 422);
        }

        // Generate UUID
        $data['uuid'] = Uuid::uuid4()->toString();
        $data['created_at'] = Carbon::now()->toDateTimeString();

        // Save to database
        $db = Database::connection();
        $userModel = new User($db);
        $userId = $userModel->insert($data);

        // Clear cache
        $this->cache->delete('users:all');

        // Send welcome email
        $this->mailer->sendWelcomeEmail($data['email'], $data['name']);

        // Log
        Logger::info('User created', ['user_id' => $userId]);

        return $this->respondSuccess($response, [
            'id' => $userId,
            'uuid' => $data['uuid']
        ], 'User created');
    }

    public function index(Request $request, Response $response): Response
    {
        // Cache users list
        $users = $this->cache->remember('users:all', 3600, function() {
            $db = Database::connection();
            $userModel = new User($db);
            return $userModel->findAll(100);
        });

        return $this->respondSuccess($response, $users);
    }
}
```

---

## ✅ สรุป

NeoCore สามารถใช้ **Composer packages ทั้งหมด** ได้เหมือน framework อื่นๆ:

### Packages แนะนำ:
1. **vlucas/phpdotenv** - Environment variables
2. **monolog/monolog** - Logging
3. **respect/validation** - Validation
4. **guzzlehttp/guzzle** - HTTP client
5. **nesbot/carbon** - DateTime
6. **phpmailer/phpmailer** - Email
7. **ramsey/uuid** - UUID
8. **league/flysystem** - File storage
9. **firebase/php-jwt** - JWT authentication
10. **predis/predis** - Redis caching

### ข้อดี:
- ✅ ใช้ได้ทุก package ใน Packagist
- ✅ ไม่ขึ้นกับ Laravel ecosystem
- ✅ เลือก package ที่ดีที่สุดได้เอง
- ✅ Mix & match ตามต้องการ

**NeoCore ให้เสรีภาพเต็มที่ในการเลือกใช้ tools ที่ชอบ!** 🚀
