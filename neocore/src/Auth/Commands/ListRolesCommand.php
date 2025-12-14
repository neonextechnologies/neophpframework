<?php

declare(strict_types=1);

namespace NeoCore\Auth\Commands;

use NeoCore\Console\Command;
use Cycle\ORM\EntityManagerInterface;
use App\Entities\Role;

/**
 * List Roles Command
 * 
 * Lists all roles with their permissions
 */
class ListRolesCommand extends Command
{
    protected string $signature = 'auth:role:list';
    protected string $description = 'List all roles';

    protected EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    public function handle(): int
    {
        $roles = $this->entityManager->getRepository(Role::class)->findAll();

        if (empty($roles)) {
            $this->warning('No roles found.');
            return 0;
        }

        $this->info('Roles:');
        $this->line('');

        foreach ($roles as $role) {
            $this->line("  <fg=cyan>{$role->name}</> (slug: {$role->slug})");
            if ($role->description) {
                $this->line("    Description: {$role->description}");
            }
            $this->line("    Permissions: " . count($role->permissions));
            if (count($role->permissions) > 0) {
                foreach ($role->permissions as $permission) {
                    $this->line("      - {$permission->slug}");
                }
            }
            $this->line('');
        }

        return 0;
    }
}
