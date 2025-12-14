<?php

declare(strict_types=1);

namespace NeoCore\Auth\Commands;

use NeoCore\Console\Command;
use Cycle\ORM\EntityManagerInterface;
use App\Entities\Permission;

/**
 * Create Permission Command
 * 
 * Creates a new permission
 */
class CreatePermissionCommand extends Command
{
    protected string $signature = 'auth:permission:create {name} {slug?} {--group=}';
    protected string $description = 'Create a new permission';

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
        $group = $this->option('group');

        // Check if permission already exists
        $existing = $this->entityManager->getRepository(Permission::class)
            ->findOne(['slug' => $slug]);

        if ($existing) {
            $this->error("Permission '{$slug}' already exists!");
            return 1;
        }

        // Create permission
        $permission = new Permission();
        $permission->name = $name;
        $permission->slug = $slug;
        $permission->description = $this->ask("Description (optional):");
        $permission->group = $group;

        $this->entityManager->persist($permission);
        $this->entityManager->run();

        $this->success("Permission '{$name}' created successfully!");
        $this->line("  Slug: {$slug}");
        if ($group) {
            $this->line("  Group: {$group}");
        }

        return 0;
    }

    protected function slugify(string $text): string
    {
        return strtolower(trim(preg_replace('/[^A-Za-z0-9.-]+/', '.', $text), '.'));
    }
}
