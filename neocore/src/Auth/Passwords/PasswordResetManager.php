<?php

declare(strict_types=1);

namespace NeoCore\Auth\Passwords;

use NeoCore\Auth\UserProviderInterface;
use Cycle\Database\DatabaseInterface;

/**
 * Password Reset Manager
 * 
 * Handles password reset token generation and validation
 */
class PasswordResetManager
{
    public const RESET_LINK_SENT = 'passwords.sent';
    public const PASSWORD_RESET = 'passwords.reset';
    public const INVALID_USER = 'passwords.user';
    public const INVALID_TOKEN = 'passwords.token';
    public const RESET_THROTTLED = 'passwords.throttled';

    protected UserProviderInterface $provider;
    protected DatabaseInterface $db;
    protected string $table;
    protected int $expires;
    protected int $throttle;

    public function __construct(
        UserProviderInterface $provider,
        DatabaseInterface $db,
        array $config = []
    ) {
        $this->provider = $provider;
        $this->db = $db;
        $this->table = $config['table'] ?? 'password_resets';
        $this->expires = $config['expire'] ?? 60; // minutes
        $this->throttle = $config['throttle'] ?? 60; // seconds
    }

    /**
     * Send a password reset link to a user
     */
    public function sendResetLink(array $credentials): string
    {
        $user = $this->provider->retrieveByCredentials($credentials);

        if (is_null($user)) {
            return static::INVALID_USER;
        }

        if ($this->recentlyCreatedToken($user->email)) {
            return static::RESET_THROTTLED;
        }

        $token = $this->createToken($user->email);

        // Send email with reset link
        // TODO: Implement email sending
        // mail()->to($user->email)->send(new PasswordResetEmail($token));

        return static::RESET_LINK_SENT;
    }

    /**
     * Reset the password for the given token
     */
    public function reset(array $credentials, callable $callback): string
    {
        $user = $this->provider->retrieveByCredentials(
            ['email' => $credentials['email']]
        );

        if (is_null($user)) {
            return static::INVALID_USER;
        }

        if (!$this->validateToken($credentials['email'], $credentials['token'])) {
            return static::INVALID_TOKEN;
        }

        $callback($user, $credentials['password']);

        $this->deleteToken($credentials['email']);

        return static::PASSWORD_RESET;
    }

    /**
     * Create a new password reset token
     */
    protected function createToken(string $email): string
    {
        $this->deleteExistingTokens($email);

        $token = bin2hex(random_bytes(32));
        $hashedToken = hash('sha256', $token);

        $this->db->insert($this->table)->values([
            'email' => $email,
            'token' => $hashedToken,
            'created_at' => date('Y-m-d H:i:s'),
        ])->run();

        return $token;
    }

    /**
     * Validate a password reset token
     */
    protected function validateToken(string $email, string $token): bool
    {
        $record = $this->getTokenRecord($email);

        if (is_null($record)) {
            return false;
        }

        if ($this->tokenExpired($record['created_at'])) {
            $this->deleteToken($email);
            return false;
        }

        $hashedToken = hash('sha256', $token);

        return hash_equals($record['token'], $hashedToken);
    }

    /**
     * Get the token record
     */
    protected function getTokenRecord(string $email): ?array
    {
        $result = $this->db->select()
            ->from($this->table)
            ->where('email', '=', $email)
            ->run()
            ->fetch();

        return $result ?: null;
    }

    /**
     * Check if a token was recently created
     */
    protected function recentlyCreatedToken(string $email): bool
    {
        $record = $this->getTokenRecord($email);

        if (is_null($record)) {
            return false;
        }

        $createdAt = strtotime($record['created_at']);
        return (time() - $createdAt) < $this->throttle;
    }

    /**
     * Check if a token is expired
     */
    protected function tokenExpired(string $createdAt): bool
    {
        $expiresAt = strtotime($createdAt) + ($this->expires * 60);
        return time() >= $expiresAt;
    }

    /**
     * Delete existing tokens for a user
     */
    protected function deleteExistingTokens(string $email): void
    {
        $this->deleteToken($email);
    }

    /**
     * Delete a token record
     */
    protected function deleteToken(string $email): void
    {
        $this->db->delete($this->table)
            ->where('email', '=', $email)
            ->run();
    }

    /**
     * Delete expired tokens
     */
    public function deleteExpired(): void
    {
        $expiredAt = date('Y-m-d H:i:s', time() - ($this->expires * 60));

        $this->db->delete($this->table)
            ->where('created_at', '<', $expiredAt)
            ->run();
    }
}
