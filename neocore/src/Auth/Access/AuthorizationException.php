<?php

declare(strict_types=1);

namespace NeoCore\Auth\Access;

/**
 * Authorization Exception
 * 
 * Thrown when authorization fails
 */
class AuthorizationException extends \Exception
{
    protected $message = 'This action is unauthorized.';
    protected $code = 403;

    public function __construct(string $message = null, int $code = 403)
    {
        parent::__construct($message ?? $this->message, $code);
    }
}
