<?php

declare(strict_types=1);

namespace NeoCore\Auth\Access;

use NeoCore\Auth\Authenticatable;

/**
 * Authorizable Trait
 * 
 * Provides authorization methods for models
 */
trait Authorizable
{
    /**
     * Determine if the entity has a given ability
     */
    public function can(string $ability, $arguments = []): bool
    {
        return app('gate')->forUser($this)->check($ability, $arguments);
    }

    /**
     * Determine if the entity does not have a given ability
     */
    public function cannot(string $ability, $arguments = []): bool
    {
        return !$this->can($ability, $arguments);
    }

    /**
     * Determine if the entity has any of the given abilities
     */
    public function canAny(array $abilities, $arguments = []): bool
    {
        return app('gate')->forUser($this)->any($abilities, $arguments);
    }

    /**
     * Authorize a given action for the current user
     */
    public function authorize(string $ability, $arguments = []): void
    {
        app('gate')->forUser($this)->authorize($ability, $arguments);
    }
}
