<?php

declare(strict_types=1);

namespace NeoCore\Auth\Middleware;

use NeoCore\Http\Request;
use NeoCore\Http\Response;

/**
 * Require Password Confirmation Middleware
 * 
 * Ensures the user has confirmed their password within a timeframe
 */
class RequirePasswordConfirmation
{
    /**
     * Handle an incoming request
     */
    public function handle(Request $request, callable $next, ?string $redirectTo = null): Response
    {
        $confirmedAt = $request->session()->get('auth.password_confirmed_at');

        if ($this->shouldConfirmPassword($confirmedAt)) {
            $intended = $request->url();
            
            return redirect()->guest($redirectTo ?? route('password.confirm'))
                ->with('intended', $intended);
        }

        return $next($request);
    }

    /**
     * Determine if the user should confirm their password
     */
    protected function shouldConfirmPassword(?int $confirmedAt): bool
    {
        $timeout = config('auth.password_timeout', 10800); // 3 hours default

        return is_null($confirmedAt) || 
               (time() - $confirmedAt) > $timeout;
    }
}
