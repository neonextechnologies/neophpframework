# Database Migrations

Version control for your database schema.

## Creating Migrations

### Generate Migration

```bash
php artisan make:migration create_users_table
php artisan make:migration add_status_to_posts_table
```

### Migration Structure

```php
use Cycle\Database\Schema\AbstractTable;

return new class {
    public function up(AbstractTable $table): void
    {
        $table->bigPrimary('id');
        $table->string('name');
        $table->string('email')->unique();
        $table->string('password');
        $table->timestamps();
    }
    
    public function down(AbstractTable $table): void
    {
        $table->drop();
    }
};
```

## Table Operations

### Create Table

```php
public function up(AbstractTable $table): void
{
    $table->bigPrimary('id');
    $table->string('name', 100);
    $table->text('description')->nullable();
    $table->boolean('active')->default(true);
    $table->integer('views')->default(0);
    $table->decimal('price', 10, 2);
    $table->json('meta')->nullable();
    $table->timestamps();
}
```

### Drop Table

```php
public function down(AbstractTable $table): void
{
    $table->drop();
}
```

### Rename Table

```php
public function up(): void
{
    Schema::rename('users', 'customers');
}
```

## Column Types

### Available Types

```php
$table->bigPrimary('id');
$table->primary('id');
$table->bigInteger('votes');
$table->integer('votes');
$table->smallInteger('votes');
$table->tinyInteger('votes');
$table->float('amount', 8, 2);
$table->double('amount', 8, 2);
$table->decimal('amount', 8, 2);
$table->boolean('confirmed');
$table->string('name', 100);
$table->text('description');
$table->datetime('created_at');
$table->date('birth_date');
$table->time('sunrise');
$table->timestamp('added_on');
$table->json('options');
$table->binary('data');
$table->enum('status', ['draft', 'published']);
```

### Column Modifiers

```php
$table->string('email')->nullable();
$table->integer('votes')->default(0);
$table->string('name')->unique();
$table->string('email')->index();
$table->text('bio')->comment('User biography');
$table->timestamp('created_at')->useCurrent();
$table->timestamp('updated_at')->useCurrentOnUpdate();
```

## Modifying Columns

### Add Columns

```php
public function up(AbstractTable $table): void
{
    $table->string('phone')->nullable();
    $table->boolean('verified')->default(false);
}
```

### Change Columns

```php
public function up(AbstractTable $table): void
{
    $table->string('name', 200)->change();
    $table->text('bio')->nullable()->change();
}
```

### Rename Columns

```php
public function up(): void
{
    Schema::table('users', function ($table) {
        $table->renameColumn('name', 'full_name');
    });
}
```

### Drop Columns

```php
public function up(AbstractTable $table): void
{
    $table->dropColumn('status');
    $table->dropColumn(['votes', 'avatar']);
}
```

## Indexes

### Create Indexes

```php
$table->string('email')->unique();
$table->index('email');
$table->index(['account_id', 'created_at']);
$table->unique(['email', 'tenant_id']);
$table->fulltext('body');
```

### Drop Indexes

```php
$table->dropIndex('users_email_unique');
$table->dropUnique('users_email_unique');
$table->dropPrimary('users_id_primary');
```

## Foreign Keys

### Add Foreign Keys

```php
$table->foreignId('user_id')
    ->constrained()
    ->onDelete('cascade');

$table->foreignId('category_id')
    ->constrained('categories')
    ->onUpdate('cascade')
    ->onDelete('set null');
```

### Drop Foreign Keys

```php
$table->dropForeign(['user_id']);
$table->dropForeign('posts_user_id_foreign');
```

## Running Migrations

### Execute Migrations

```bash
# Run all migrations
php artisan migrate

# Rollback last migration
php artisan migrate:rollback

# Rollback all migrations
php artisan migrate:reset

# Refresh database
php artisan migrate:refresh

# Fresh migration (drop all tables)
php artisan migrate:fresh
```

## Best Practices

1. **Never Edit Existing** - Create new migrations instead
2. **Descriptive Names** - Use clear migration names
3. **Small Changes** - One logical change per migration
4. **Test Rollback** - Always test down() method
5. **Team Coordination** - Communicate schema changes
6. **Backup Data** - Backup before running migrations
7. **Version Control** - Commit migrations to git
8. **Production Safety** - Be careful with data loss operations

## See Also

- [Database Getting Started](getting-started.md)
- [Models](models.md)
- [Seeding](seeding.md)
