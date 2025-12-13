<?php

/**
 * Example User Service
 * 
 * Services contain business logic and coordinate between models, jobs, and events.
 */

namespace App\Services;

use App\Models\User;
use NeoCore\System\Core\Database;
use NeoCore\System\Core\EventBus;

class UserService
{
    private User $userModel;
    private EventBus $eventBus;

    public function __construct()
    {
        $db = Database::connection();
        $this->userModel = new User($db);
        $this->eventBus = new EventBus();
    }

    /**
     * Register new user
     */
    public function register(array $data): ?array
    {
        // Check if email exists
        $existing = $this->userModel->findByEmail($data['email']);
        if ($existing) {
            return null; // Email already exists
        }

        // Create user
        $userId = $this->userModel->createUser($data);
        if (!$userId) {
            return null;
        }

        // Get created user
        $user = $this->userModel->find($userId);

        // Dispatch event
        $this->eventBus->dispatch('user.registered', $user);

        return $user;
    }

    /**
     * Login user
     */
    public function login(string $email, string $password): ?array
    {
        $user = $this->userModel->findByEmail($email);
        
        if (!$user || !password_verify($password, $user['password'])) {
            return null;
        }

        // Update last login
        $this->userModel->update($user['id'], [
            'last_login' => date('Y-m-d H:i:s')
        ]);

        // Dispatch event
        $this->eventBus->dispatch('user.logged_in', $user);

        // Remove password from response
        unset($user['password']);
        return $user;
    }

    /**
     * Update user profile
     */
    public function updateProfile(int $userId, array $data): bool
    {
        // Remove sensitive fields
        unset($data['password'], $data['email']);

        $success = $this->userModel->update($userId, $data);

        if ($success) {
            $user = $this->userModel->find($userId);
            $this->eventBus->dispatch('user.updated', $user);
        }

        return $success;
    }

    /**
     * Change password
     */
    public function changePassword(int $userId, string $oldPassword, string $newPassword): bool
    {
        $user = $this->userModel->find($userId);
        
        if (!$user || !password_verify($oldPassword, $user['password'])) {
            return false;
        }

        return $this->userModel->updatePassword($userId, $newPassword);
    }

    /**
     * Get user statistics
     */
    public function getStatistics(): array
    {
        $activeUsers = $this->userModel->getActiveUsers();
        $allUsers = $this->userModel->findAll(1000);

        return [
            'total' => count($allUsers),
            'active' => count($activeUsers),
            'inactive' => count($allUsers) - count($activeUsers)
        ];
    }
}
