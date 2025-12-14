<?php

declare(strict_types=1);

namespace NeoCore\Auth;

/**
 * Generic User
 * 
 * A simple implementation of the Authenticatable interface
 */
class GenericUser implements Authenticatable
{
    protected array $attributes;

    public function __construct(array $attributes)
    {
        $this->attributes = $attributes;
    }

    /**
     * Get the name of the unique identifier for the user
     */
    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    /**
     * Get the unique identifier for the user
     */
    public function getAuthIdentifier(): mixed
    {
        return $this->attributes[$this->getAuthIdentifierName()];
    }

    /**
     * Get the password for the user
     */
    public function getAuthPassword(): ?string
    {
        return $this->attributes['password'] ?? null;
    }

    /**
     * Get the token value for the "remember me" session
     */
    public function getRememberToken(): ?string
    {
        return $this->attributes[$this->getRememberTokenName()] ?? null;
    }

    /**
     * Set the token value for the "remember me" session
     */
    public function setRememberToken(string $value): void
    {
        $this->attributes[$this->getRememberTokenName()] = $value;
    }

    /**
     * Get the column name for the "remember me" token
     */
    public function getRememberTokenName(): string
    {
        return 'remember_token';
    }

    /**
     * Dynamically access attributes
     */
    public function __get(string $key)
    {
        return $this->attributes[$key] ?? null;
    }

    /**
     * Dynamically set attributes
     */
    public function __set(string $key, $value): void
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Dynamically check if an attribute exists
     */
    public function __isset(string $key): bool
    {
        return isset($this->attributes[$key]);
    }
}
