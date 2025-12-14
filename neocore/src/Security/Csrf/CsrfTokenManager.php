<?php

declare(strict_types=1);

namespace NeoCore\Security\Csrf;

use NeoCore\Session\SessionInterface;

/**
 * CSRF Token Manager
 * 
 * Manages CSRF token generation and validation
 */
class CsrfTokenManager
{
    protected SessionInterface $session;
    protected string $tokenKey = '_csrf_token';
    protected int $tokenLength = 32;

    public function __construct(SessionInterface $session)
    {
        $this->session = $session;
    }

    /**
     * Get or generate a CSRF token
     */
    public function getToken(): string
    {
        $token = $this->session->get($this->tokenKey);

        if (empty($token)) {
            $token = $this->generateToken();
            $this->session->put($this->tokenKey, $token);
        }

        return $token;
    }

    /**
     * Validate a CSRF token
     */
    public function validateToken(string $token): bool
    {
        $sessionToken = $this->session->get($this->tokenKey);

        if (empty($sessionToken) || empty($token)) {
            return false;
        }

        return hash_equals($sessionToken, $token);
    }

    /**
     * Regenerate the CSRF token
     */
    public function regenerateToken(): string
    {
        $token = $this->generateToken();
        $this->session->put($this->tokenKey, $token);
        return $token;
    }

    /**
     * Generate a new CSRF token
     */
    protected function generateToken(): string
    {
        return bin2hex(random_bytes($this->tokenLength));
    }

    /**
     * Get the token key used in session
     */
    public function getTokenKey(): string
    {
        return $this->tokenKey;
    }
}
