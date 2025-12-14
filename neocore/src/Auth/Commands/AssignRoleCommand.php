<?php

declare(strict_types=1);

namespace NeoCore\Auth\Commands;

use NeoCore\Console\Command;
use Cycle\ORM\EntityManagerInterface;
use App\Entities\User;
use App\Entities\Role;

/**
 * Assign Role Command
 * 
 * Assigns a role to a user
 */
class AssignRoleCommand extends Command
{
    protected string $signature = 'auth:role:assign {user} {role}';
    protected string $description = 'Assign a role to a user';

    protected EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
    }

    public function handle(): int
    {
        $userId = $this->argument('user');
        $roleSlug = $this->argument('role');

        // Find user
        $user = $this->entityManager->getRepository(User::class)->findByPK($userId);
        if (!$user) {
            $this->error("User with ID {$userId} not found!");
            return 1;
        }

        // Find role
        $role = $this->entityManager->getRepository(Role::class)
            ->findOne(['slug' => $roleSlug]);
        if (!$role) {
            $this->error("Role '{$roleSlug}' not found!");
            return 1;
        }

        // Assign role
        if ($user->hasRole($roleSlug)) {
            $this->warning("User already has the '{$role->name}' role!");
            return 0;
        }

        $user->assignRole($role);
        $this->entityManager->persist($user);
        $this->entityManager->run();

        $this->success("Role '{$role->name}' assigned to user '{$user->name}' successfully!");

        return 0;
    }
}
