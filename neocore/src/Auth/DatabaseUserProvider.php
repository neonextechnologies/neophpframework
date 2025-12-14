<?php

declare(strict_types=1);

namespace NeoCore\Auth;

use Cycle\Database\DatabaseInterface;

/**
 * Database User Provider
 * 
 * Retrieves users from a database table
 */
class DatabaseUserProvider implements UserProviderInterface
{
    protected DatabaseInterface $db;
    protected string $table;

    public function __construct(DatabaseInterface $db, string $table = 'users')
    {
        $this->db = $db;
        $this->table = $table;
    }

    /**
     * Retrieve a user by their unique identifier
     */
    public function retrieveById(mixed $identifier): ?Authenticatable
    {
        $user = $this->db->select()->from($this->table)
            ->where('id', '=', $identifier)
            ->run()
            ->fetch();

        return $user ? $this->getGenericUser($user) : null;
    }

    /**
     * Retrieve a user by their credentials
     */
    public function retrieveByCredentials(array $credentials): ?Authenticatable
    {
        $query = $this->db->select()->from($this->table);

        foreach ($credentials as $key => $value) {
            if ($key !== 'password') {
                $query->where($key, '=', $value);
            }
        }

        $user = $query->run()->fetch();

        return $user ? $this->getGenericUser($user) : null;
    }

    /**
     * Validate a user against the given credentials
     */
    public function validateCredentials(Authenticatable $user, array $credentials): bool
    {
        $plain = $credentials['password'] ?? '';
        return password_verify($plain, $user->getAuthPassword());
    }

    /**
     * Retrieve a user by token
     */
    public function retrieveByToken(mixed $identifier, string $token): ?Authenticatable
    {
        $user = $this->db->select()->from($this->table)
            ->where('id', '=', $identifier)
            ->where('remember_token', '=', $token)
            ->run()
            ->fetch();

        return $user ? $this->getGenericUser($user) : null;
    }

    /**
     * Update the "remember me" token
     */
    public function updateRememberToken(Authenticatable $user, string $token): void
    {
        $this->db->update($this->table, [
            'remember_token' => $token
        ])->where('id', '=', $user->getAuthIdentifier())->run();
    }

    /**
     * Get a generic user instance from the given user data
     */
    protected function getGenericUser(array $user): GenericUser
    {
        return new GenericUser($user);
    }
}
