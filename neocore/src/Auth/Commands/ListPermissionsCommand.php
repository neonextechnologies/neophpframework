<?php

declare(strict_types=1);

namespace NeoCore\Auth\Commands;

use NeoCore\Console\Command;
use Cycle\ORM\EntityManagerInterface;
use App\Entities\Permission;

/**
 * List Permissions Command
 * 
 * Lists all permissions
 */
class ListPermissionsCommand extends Command
{
    protected string $signature = 'auth:permission:list {--group=}';
    protected string $description = 'List all permissions';

    protected EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    public function handle(): int
    {
        $group = $this->option('group');

        $repo = $this->entityManager->getRepository(Permission::class);
        $permissions = $group 
            ? $repo->findAll(['group' => $group])
            : $repo->findAll();

        if (empty($permissions)) {
            $this->warning('No permissions found.');
            return 0;
        }

        $this->info($group ? "Permissions in group '{$group}':" : 'All permissions:');
        $this->line('');

        $grouped = [];
        foreach ($permissions as $permission) {
            $g = $permission->group ?? 'Other';
            $grouped[$g][] = $permission;
        }

        foreach ($grouped as $groupName => $perms) {
            $this->line("  <fg=yellow>{$groupName}</>:");
            foreach ($perms as $permission) {
                $this->line("    - <fg=cyan>{$permission->slug}</> ({$permission->name})");
                if ($permission->description) {
                    $this->line("      {$permission->description}");
                }
            }
            $this->line('');
        }

        return 0;
    }
}
