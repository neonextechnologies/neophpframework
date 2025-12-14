# Soft Deletes

Soft deleting allows you to mark records as deleted without actually removing them from your database. This is useful for maintaining data integrity, audit trails, and the ability to restore deleted records.

## Table of Contents

- [Setup](#setup)
- [Using Soft Deletes](#using-soft-deletes)
- [Querying Soft Deleted Records](#querying-soft-deleted-records)
- [Restoring Records](#restoring-records)
- [Force Deleting](#force-deleting)
- [Pruning Old Records](#pruning-old-records)
- [Repository Methods](#repository-methods)
- [Best Practices](#best-practices)

## Setup

### Database Schema

Add a `deleted_at` column to your table:

```php
// In your migration
$table->datetime('deleted_at')->nullable();
```

Or using raw SQL:

```sql
ALTER TABLE users ADD COLUMN deleted_at DATETIME DEFAULT NULL;
```

### Entity Setup

Add the `SoftDeletes` trait to your entity:

```php
use NeoPhp\Database\Traits\SoftDeletes;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Column;

#[Entity(table: 'users')]
class User
{
    use SoftDeletes;

    #[Column(type: 'primary')]
    public int $id;

    #[Column(type: 'string')]
    public string $name;

    #[Column(type: 'string')]
    public string $email;

    #[Column(type: 'datetime', nullable: true)]
    public ?\DateTimeImmutable $deleted_at = null;

    // Custom deleted_at column name (optional)
    protected string $deletedAtColumn = 'deleted_at';
}
```

### Repository Setup

Add the `HasSoftDeletes` concern to your repository:

```php
use Cycle\ORM\Select\Repository;
use NeoPhp\Database\Concerns\HasSoftDeletes;

class UserRepository extends Repository
{
    use HasSoftDeletes;

    // Your repository methods...
}
```

## Using Soft Deletes

### Soft Deleting Records

```php
// Soft delete a single record
$user = $userRepository->findById(1);
$user->delete(); // Sets deleted_at to current timestamp

// Soft delete by ID
$userRepository->softDeleteById(1);

// Soft delete multiple records
$userRepository->softDeleteWhere(['role' => 'guest']);
```

### Checking if Deleted

```php
// Check if a record is soft deleted
if ($user->trashed()) {
    echo "User is deleted";
}

// Check by ID
if ($userRepository->isTrashed(1)) {
    echo "User is deleted";
}

// Get count of soft deleted records
$count = $userRepository->countTrashed();
```

## Querying Soft Deleted Records

By default, soft deleted records are automatically excluded from all queries.

### Including Soft Deleted Records

```php
// Get all records including soft deleted
$allUsers = $userRepository->findWithTrashed();

// Query with soft deleted
$users = $userRepository->query()
    ->withTrashed()
    ->where('role', 'admin')
    ->fetchAll();
```

### Only Soft Deleted Records

```php
// Get only soft deleted records
$deletedUsers = $userRepository->findOnlyTrashed();

// Query only soft deleted
$users = $userRepository->query()
    ->onlyTrashed()
    ->where('role', 'admin')
    ->fetchAll();
```

### Excluding Soft Deleted (Default)

```php
// This is the default behavior
$activeUsers = $userRepository->findAll();

// Explicitly exclude soft deleted
$users = $userRepository->query()
    ->withoutTrashed()
    ->fetchAll();
```

## Restoring Records

### Restore a Single Record

```php
// Restore a soft deleted record
$user = $userRepository->findByIdWithTrashed(1);
$user->restore(); // Sets deleted_at to null

// Restore by ID
$userRepository->restoreById(1);
```

### Restore Multiple Records

```php
// Restore all users with a specific role
$count = $userRepository->restoreWhere(['role' => 'subscriber']);
echo "Restored {$count} user(s)";

// Restore using query builder
$count = $userRepository->query()
    ->onlyTrashed()
    ->where('role', 'subscriber')
    ->restore();
```

## Force Deleting

Force deleting permanently removes records from the database.

### Force Delete Single Record

```php
// Permanently delete a record
$user = $userRepository->findByIdWithTrashed(1);
$user->forceDelete();

// Force delete by ID
$userRepository->forceDeleteById(1);
```

### Force Delete Multiple Records

```php
// Force delete by criteria
$count = $userRepository->forceDeleteWhere(['status' => 'banned']);

// Force delete using query builder
$count = $userRepository->query()
    ->withTrashed()
    ->where('status', 'banned')
    ->forceDelete();
```

## Pruning Old Records

Clean up old soft-deleted records automatically.

### Via Repository

```php
// Delete records soft-deleted more than 30 days ago
$pruned = $userRepository->pruneDeleted(30);
echo "Pruned {$pruned} record(s)";

// Delete records soft-deleted more than 90 days ago
$pruned = $userRepository->pruneDeleted(90);
```

### Via CLI Command

```bash
# Prune users soft-deleted more than 30 days ago
php neo model:prune --model=App\\Models\\User --days=30

# Prune without confirmation
php neo model:prune --model=App\\Models\\User --days=30 --force

# Prune with custom days
php neo model:prune --model=App\\Models\\User --days=90 --force
```

### Scheduled Pruning

Add to your schedule configuration:

```php
// config/schedule.php
return [
    'tasks' => [
        [
            'command' => 'model:prune --model=App\\Models\\User --days=30 --force',
            'frequency' => '0 0 * * 0', // Weekly on Sunday at midnight
            'description' => 'Prune old deleted users',
        ],
        [
            'command' => 'model:prune --model=App\\Models\\Post --days=90 --force',
            'frequency' => '0 0 1 * *', // Monthly on the 1st at midnight
            'description' => 'Prune old deleted posts',
        ],
    ],
];
```

## Repository Methods

### Query Methods

```php
// Get query builder with soft delete support
$query = $repository->query();

// Include soft deleted
$query->withTrashed();

// Only soft deleted
$query->onlyTrashed();

// Exclude soft deleted (default)
$query->withoutTrashed();
```

### Find Methods

```php
// Find all including soft deleted
$records = $repository->findWithTrashed();

// Find only soft deleted
$records = $repository->findOnlyTrashed();

// Find by ID including soft deleted
$record = $repository->findByIdWithTrashed($id);
```

### Delete Methods

```php
// Soft delete by ID
$repository->softDeleteById($id);

// Soft delete by criteria
$count = $repository->softDeleteWhere(['status' => 'inactive']);

// Force delete by ID
$repository->forceDeleteById($id);

// Force delete by criteria
$count = $repository->forceDeleteWhere(['status' => 'banned']);
```

### Restore Methods

```php
// Restore by ID
$repository->restoreById($id);

// Restore by criteria
$count = $repository->restoreWhere(['role' => 'subscriber']);
```

### Utility Methods

```php
// Check if record is trashed
$isTrashed = $repository->isTrashed($id);

// Count soft deleted records
$count = $repository->countTrashed();

// Prune old soft deleted records
$pruned = $repository->pruneDeleted($days);
```

## Advanced Usage

### Custom Deleted At Column

```php
class User
{
    use SoftDeletes;

    // Use a custom column name
    protected string $deletedAtColumn = 'removed_at';

    #[Column(type: 'datetime', nullable: true)]
    public ?\DateTimeImmutable $removed_at = null;
}
```

### Conditional Soft Deletes

```php
// Only soft delete if condition is met
$user = $userRepository->findById(1);

if ($user->hasActiveSubscription()) {
    // Soft delete to allow restoration
    $user->delete();
} else {
    // Force delete if no active subscription
    $user->forceDelete();
}
```

### Soft Delete Events

```php
class User
{
    use SoftDeletes;

    protected function runSoftDelete(): void
    {
        // Before soft delete
        $this->fireEvent('deleting');

        parent::runSoftDelete();

        // After soft delete
        $this->fireEvent('deleted');
    }

    public function restore(): void
    {
        // Before restore
        $this->fireEvent('restoring');

        parent::restore();

        // After restore
        $this->fireEvent('restored');
    }
}
```

### Cascade Soft Deletes

```php
class User
{
    use SoftDeletes;

    public function delete(): void
    {
        // Soft delete related records
        foreach ($this->posts as $post) {
            $post->delete();
        }

        foreach ($this->comments as $comment) {
            $comment->delete();
        }

        // Then soft delete the user
        parent::delete();
    }
}
```

## Examples

### Complete Entity Example

```php
<?php

declare(strict_types=1);

namespace App\Models;

use NeoPhp\Database\Traits\SoftDeletes;
use Cycle\Annotated\Annotation\Entity;
use Cycle\Annotated\Annotation\Column;
use DateTimeImmutable;

#[Entity(table: 'users')]
class User
{
    use SoftDeletes;

    #[Column(type: 'primary')]
    public int $id;

    #[Column(type: 'string')]
    public string $name;

    #[Column(type: 'string')]
    public string $email;

    #[Column(type: 'string')]
    public string $status;

    #[Column(type: 'datetime')]
    public DateTimeImmutable $created_at;

    #[Column(type: 'datetime')]
    public DateTimeImmutable $updated_at;

    #[Column(type: 'datetime', nullable: true)]
    public ?DateTimeImmutable $deleted_at = null;

    protected string $deletedAtColumn = 'deleted_at';

    public function __construct(string $name, string $email)
    {
        $this->name = $name;
        $this->email = $email;
        $this->status = 'active';
        $this->created_at = new DateTimeImmutable();
        $this->updated_at = new DateTimeImmutable();
    }
}
```

### Complete Repository Example

```php
<?php

declare(strict_types=1);

namespace App\Repositories;

use Cycle\ORM\Select\Repository;
use NeoPhp\Database\Concerns\HasSoftDeletes;
use App\Models\User;

class UserRepository extends Repository
{
    use HasSoftDeletes;

    /**
     * Find active users (not soft deleted)
     */
    public function findActiveUsers(): array
    {
        return $this->query()
            ->where('status', 'active')
            ->fetchAll();
    }

    /**
     * Find inactive and deleted users
     */
    public function findInactiveUsers(): array
    {
        return $this->query()
            ->withTrashed()
            ->where('status', 'inactive')
            ->fetchAll();
    }

    /**
     * Bulk restore users by IDs
     */
    public function bulkRestore(array $ids): int
    {
        $count = 0;

        foreach ($ids as $id) {
            if ($this->restoreById($id)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Clean up test users
     */
    public function cleanupTestUsers(): int
    {
        return $this->forceDeleteWhere(['email' => 'test@example.com']);
    }
}
```

### Controller Example

```php
<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\UserRepository;

class UserController
{
    public function __construct(
        private UserRepository $userRepository
    ) {}

    public function index()
    {
        // Get active users only (default)
        $users = $this->userRepository->findAll();
        return view('users.index', ['users' => $users]);
    }

    public function trashed()
    {
        // Get only soft deleted users
        $users = $this->userRepository->findOnlyTrashed();
        return view('users.trashed', ['users' => $users]);
    }

    public function destroy(int $id)
    {
        // Soft delete user
        $this->userRepository->softDeleteById($id);
        return redirect('/users')->with('success', 'User deleted successfully');
    }

    public function restore(int $id)
    {
        // Restore soft deleted user
        $this->userRepository->restoreById($id);
        return redirect('/users')->with('success', 'User restored successfully');
    }

    public function forceDestroy(int $id)
    {
        // Permanently delete user
        $this->userRepository->forceDeleteById($id);
        return redirect('/users')->with('success', 'User permanently deleted');
    }
}
```

## Best Practices

1. **Always use soft deletes for user data**: Preserve data for audit trails and potential restoration

2. **Set up automatic pruning**: Schedule cleanup jobs to remove old soft-deleted records

3. **Document your soft delete policy**: Let users know how long deleted records are kept

4. **Use force delete for sensitive data**: When required by regulations (e.g., GDPR), use force delete to permanently remove data

5. **Implement cascade soft deletes**: When deleting parent records, consider soft deleting related records

6. **Monitor soft deleted records**: Keep track of how many records are soft deleted to manage database size

7. **Provide restore functionality**: Give administrators the ability to restore accidentally deleted records

8. **Test soft delete behavior**: Ensure queries properly exclude soft deleted records

9. **Index the deleted_at column**: Improve query performance with an index on the soft delete column

```sql
CREATE INDEX idx_users_deleted_at ON users(deleted_at);
```

10. **Consider storage costs**: Regularly prune old soft deleted records to manage database size

## Troubleshooting

### Soft Deleted Records Still Appearing

Make sure:
- Entity uses the `SoftDeletes` trait
- Repository uses the `HasSoftDeletes` concern
- `deleted_at` column exists in database
- Queries use the repository's query builder

### Restore Not Working

Check:
- Record is actually soft deleted (`trashed()` returns true)
- `deleted_at` column is not nullable
- Entity manager is properly persisting changes

### Force Delete Not Working

Verify:
- Entity implements the force delete logic
- ORM is properly configured
- Entity manager has proper permissions

## Performance Considerations

### Indexing

Add indexes for better performance:

```sql
-- Index on deleted_at for filtering
CREATE INDEX idx_deleted_at ON users(deleted_at);

-- Composite index for common queries
CREATE INDEX idx_status_deleted_at ON users(status, deleted_at);
```

### Query Optimization

```php
// Good: Use repository methods
$users = $repository->findAll(); // Automatically filters deleted

// Avoid: Manual queries without soft delete filtering
$users = $entityManager->getRepository(User::class)
    ->select()
    ->fetchAll(); // Might include soft deleted records
```

### Bulk Operations

For bulk operations, use batch processing:

```php
// Process in chunks to avoid memory issues
$chunkSize = 1000;
$offset = 0;

while (true) {
    $users = $repository->query()
        ->onlyTrashed()
        ->limit($chunkSize)
        ->offset($offset)
        ->fetchAll();

    if (empty($users)) {
        break;
    }

    foreach ($users as $user) {
        $user->forceDelete();
    }

    $offset += $chunkSize;
}
```
