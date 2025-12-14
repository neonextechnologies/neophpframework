<?php

declare(strict_types=1);

namespace NeoCore\Auth;

use NeoCore\Session\SessionInterface;

/**
 * Session-based Authentication Guard
 * 
 * Handles authentication via session storage
 */
class SessionGuard implements Guard
{
    protected UserProviderInterface $provider;
    protected SessionInterface $session;
    protected ?Authenticatable $user = null;
    protected bool $loggedOut = false;

    public function __construct(UserProviderInterface $provider, SessionInterface $session)
    {
        $this->provider = $provider;
        $this->session = $session;
    }

    /**
     * Determine if the current user is authenticated
     */
    public function check(): bool
    {
        return !is_null($this->user());
    }

    /**
     * Determine if the current user is a guest
     */
    public function guest(): bool
    {
        return !$this->check();
    }

    /**
     * Get the currently authenticated user
     */
    public function user(): ?Authenticatable
    {
        if ($this->loggedOut) {
            return null;
        }

        if (!is_null($this->user)) {
            return $this->user;
        }

        $id = $this->session->get('auth_id');

        if (!is_null($id)) {
            $this->user = $this->provider->retrieveById($id);
        }

        // Check remember me token
        if (is_null($this->user) && !is_null($recaller = $this->getRememberCookie())) {
            $this->user = $this->getUserByRecaller($recaller);
        }

        return $this->user;
    }

    /**
     * Get the ID for the currently authenticated user
     */
    public function id(): mixed
    {
        return $this->user()?->getAuthIdentifier();
    }

    /**
     * Validate a user's credentials
     */
    public function validate(array $credentials = []): bool
    {
        $user = $this->provider->retrieveByCredentials($credentials);

        if (is_null($user)) {
            return false;
        }

        return $this->provider->validateCredentials($user, $credentials);
    }

    /**
     * Attempt to authenticate a user using the given credentials
     */
    public function attempt(array $credentials = [], bool $remember = false): bool
    {
        $user = $this->provider->retrieveByCredentials($credentials);

        if ($this->hasValidCredentials($user, $credentials)) {
            $this->login($user, $remember);
            return true;
        }

        return false;
    }

    /**
     * Determine if the user has valid credentials
     */
    protected function hasValidCredentials(?Authenticatable $user, array $credentials): bool
    {
        return !is_null($user) && $this->provider->validateCredentials($user, $credentials);
    }

    /**
     * Log a user into the application
     */
    public function login(Authenticatable $user, bool $remember = false): void
    {
        $this->session->put('auth_id', $user->getAuthIdentifier());
        $this->session->regenerate();

        if ($remember) {
            $this->createRememberToken($user);
        }

        $this->user = $user;
        $this->loggedOut = false;
    }

    /**
     * Log the user out of the application
     */
    public function logout(): void
    {
        $user = $this->user();

        $this->session->forget('auth_id');
        $this->session->regenerate();

        if (!is_null($user)) {
            $this->cycleRememberToken($user);
        }

        $this->user = null;
        $this->loggedOut = true;
    }

    /**
     * Create a remember me token
     */
    protected function createRememberToken(Authenticatable $user): void
    {
        $token = bin2hex(random_bytes(32));
        $this->provider->updateRememberToken($user, $token);
        $this->setRememberCookie($user->getAuthIdentifier() . '|' . $token);
    }

    /**
     * Cycle the remember token
     */
    protected function cycleRememberToken(Authenticatable $user): void
    {
        $this->provider->updateRememberToken($user, bin2hex(random_bytes(32)));
        $this->clearRememberCookie();
    }

    /**
     * Get user by remember token
     */
    protected function getUserByRecaller(string $recaller): ?Authenticatable
    {
        if (!str_contains($recaller, '|')) {
            return null;
        }

        [$id, $token] = explode('|', $recaller, 2);

        return $this->provider->retrieveByToken($id, $token);
    }

    /**
     * Get the remember cookie value
     */
    protected function getRememberCookie(): ?string
    {
        return $_COOKIE['remember_token'] ?? null;
    }

    /**
     * Set the remember cookie
     */
    protected function setRememberCookie(string $value): void
    {
        setcookie('remember_token', $value, [
            'expires' => time() + (60 * 60 * 24 * 365), // 1 year
            'path' => '/',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
    }

    /**
     * Clear the remember cookie
     */
    protected function clearRememberCookie(): void
    {
        setcookie('remember_token', '', [
            'expires' => time() - 3600,
            'path' => '/'
        ]);
    }
}
