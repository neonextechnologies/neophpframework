<?php

declare(strict_types=1);

namespace NeoCore\Auth\Commands;

use NeoCore\Console\Command;
use Cycle\ORM\EntityManagerInterface;
use App\Entities\Role;
use App\Entities\Permission;

/**
 * Create Role Command
 * 
 * Creates a new role with optional permissions
 */
class CreateRoleCommand extends Command
{
    protected string $signature = 'auth:role:create {name} {slug?} {--permissions=}';
    protected string $description = 'Create a new role';

    protected EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    public function handle(): int
    {
        $name = $this->argument('name');
        $slug = $this->argument('slug') ?? $this->slugify($name);
        $permissionSlugs = $this->option('permissions') 
            ? explode(',', $this->option('permissions'))
            : [];

        // Check if role already exists
        $existingRole = $this->entityManager->getRepository(Role::class)
            ->findOne(['slug' => $slug]);

        if ($existingRole) {
            $this->error("Role '{$slug}' already exists!");
            return 1;
        }

        // Create role
        $role = new Role();
        $role->name = $name;
        $role->slug = $slug;
        $role->description = $this->ask("Description (optional):");

        // Add permissions
        if (!empty($permissionSlugs)) {
            foreach ($permissionSlugs as $permSlug) {
                $permission = $this->entityManager->getRepository(Permission::class)
                    ->findOne(['slug' => trim($permSlug)]);

                if ($permission) {
                    $role->grantPermission($permission);
                    $this->line("  Added permission: {$permission->slug}");
                }
            }
        }

        $this->entityManager->persist($role);
        $this->entityManager->run();

        $this->success("Role '{$name}' created successfully!");
        $this->line("  Slug: {$slug}");
        $this->line("  Permissions: " . count($role->permissions));

        return 0;
    }

    protected function slugify(string $text): string
    {
        return strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $text), '-'));
    }
}
