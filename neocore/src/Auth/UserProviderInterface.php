<?php

declare(strict_types=1);

namespace NeoCore\Auth;

/**
 * User Provider Interface
 * 
 * Defines methods for retrieving users from storage
 */
interface UserProviderInterface
{
    /**
     * Retrieve a user by their unique identifier
     */
    public function retrieveById(mixed $identifier): ?Authenticatable;

    /**
     * Retrieve a user by their credentials
     */
    public function retrieveByCredentials(array $credentials): ?Authenticatable;

    /**
     * Validate a user against the given credentials
     */
    public function validateCredentials(Authenticatable $user, array $credentials): bool;

    /**
     * Retrieve a user by token (for remember me functionality)
     */
    public function retrieveByToken(mixed $identifier, string $token): ?Authenticatable;

    /**
     * Update the "remember me" token for the given user
     */
    public function updateRememberToken(Authenticatable $user, string $token): void;
}
