# Installation

## Requirements

Before installing NeoCore, ensure your system meets the following requirements:

- **PHP 8.0 or higher**
- **PDO Extension** (for database)
- **JSON Extension** (usually enabled by default)
- **mod_rewrite** (Apache) or equivalent web server URL rewriting

## Installation Methods

### Method 1: Clone from GitHub

```bash
# Clone the repository
git clone https://github.com/yourusername/neocore.git
cd neocore

# Copy environment file
cp .env.example .env

# Edit .env with your settings
nano .env

# Start development server
php neocore serve
```

### Method 2: Composer Create-Project (Coming Soon)

```bash
composer create-project neocore/framework myproject
cd myproject
php neocore serve
```

### Method 3: Download ZIP

1. Download the latest release from [GitHub Releases](https://github.com/yourusername/neocore/releases)
2. Extract to your web server directory
3. Copy `.env.example` to `.env`
4. Configure your environment
5. Point your web server to the `public/` directory

## Configuration

### Environment Variables

Edit `.env` file:

```env
# Application
APP_NAME="My NeoCore App"
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:8000

# Database
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=neocore
DB_USERNAME=root
DB_PASSWORD=

# Redis (optional)
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=

# Queue
QUEUE_DRIVER=file
```

### Database Setup

1. Create a database:

```sql
CREATE DATABASE neocore CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. Update `.env` with your database credentials

3. Run migrations:

```bash
php neocore migrate
```

## Web Server Configuration

### Apache

NeoCore includes a `.htaccess` file in the `public/` directory. Ensure `mod_rewrite` is enabled:

```bash
# Ubuntu/Debian
sudo a2enmod rewrite
sudo systemctl restart apache2
```

**VirtualHost Configuration:**

```apache
<VirtualHost *:80>
    ServerName myapp.local
    DocumentRoot /var/www/neocore/public
    
    <Directory /var/www/neocore/public>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
    </Directory>
    
    ErrorLog ${APACHE_LOG_DIR}/neocore_error.log
    CustomLog ${APACHE_LOG_DIR}/neocore_access.log combined
</VirtualHost>
```

### Nginx

```nginx
server {
    listen 80;
    server_name myapp.local;
    root /var/www/neocore/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### Development Server

For development, use PHP's built-in server:

```bash
php neocore serve

# Custom host and port
php neocore serve 0.0.0.0 8080
```

Visit `http://localhost:8000`

## Directory Permissions

Ensure the following directories are writable:

```bash
chmod -R 755 storage/
chmod -R 755 storage/logs/
chmod -R 755 storage/cache/
chmod -R 755 storage/sessions/
chmod -R 755 storage/queue/
```

## Verification

Test your installation:

```bash
# List available commands
php neocore list

# Check PHP version
php -v

# Test database connection
php neocore migrate
```

Visit your application in a browser. You should see:

```json
{
  "success": true,
  "message": "Success",
  "data": {
    "message": "Welcome to NeoCore PHP Framework",
    "version": "1.0.0"
  }
}
```

## Optional: Install Development Dependencies

For testing and development tools:

```bash
composer install
```

This installs:
- PHPUnit for testing
- Development autoloader

**Note:** Composer is NOT required in production. The framework runs without it.

## Troubleshooting

### "Class not found" errors

Ensure the autoloader is working:

```bash
# Check system/Core/Autoloader.php exists
ls -la system/Core/Autoloader.php
```

### Database connection errors

1. Check `.env` database credentials
2. Verify MySQL/MariaDB is running
3. Ensure database exists
4. Test connection:

```bash
mysql -u root -p -e "SELECT 1;"
```

### Permission errors

```bash
# Fix ownership (Ubuntu/Debian)
sudo chown -R www-data:www-data storage/
sudo chmod -R 755 storage/
```

### Apache .htaccess not working

```bash
# Enable mod_rewrite
sudo a2enmod rewrite
sudo systemctl restart apache2

# Check AllowOverride is set to All in VirtualHost
```

## Next Steps

Your NeoCore installation is ready! Continue to:

- [Configuration](configuration.md) - Learn about configuration options
- [Directory Structure](directory-structure.md) - Understand the layout
- [Routing](basics/routing.md) - Define your first routes
