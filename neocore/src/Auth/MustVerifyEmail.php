<?php

declare(strict_types=1);

namespace NeoCore\Auth;

/**
 * Must Verify Email Interface
 * 
 * Indicates that a user must verify their email address
 */
interface MustVerifyEmail
{
    /**
     * Determine if the user has verified their email address
     */
    public function hasVerifiedEmail(): bool;

    /**
     * Mark the given user's email as verified
     */
    public function markEmailAsVerified(): bool;

    /**
     * Send the email verification notification
     */
    public function sendEmailVerificationNotification(): void;

    /**
     * Get the email address that should be used for verification
     */
    public function getEmailForVerification(): string;
}
