<?php

declare(strict_types=1);

namespace NeoCore\Auth\Access;

/**
 * Policy Interface
 * 
 * Defines the contract for authorization policies
 */
interface PolicyInterface
{
    /**
     * Determine if the given ability should be granted before any other policies
     */
    public function before($user, string $ability): ?bool;
}
