<?php

declare(strict_types=1);

namespace App\Controllers;

use NeoCore\Http\Request;
use NeoCore\Http\Response;
use NeoCore\Auth\AuthManager;
use Cycle\ORM\EntityManagerInterface;

/**
 * Email Verification Controller
 * 
 * Handles email verification
 */
class VerificationController
{
    protected AuthManager $auth;
    protected EntityManagerInterface $entityManager;

    public function __construct(AuthManager $auth, EntityManagerInterface $entityManager)
    {
        $this->auth = $auth;
        $this->entityManager = $entityManager;
    }

    /**
     * Show the email verification notice
     */
    public function show(Request $request): Response
    {
        $user = $request->user();

        return $user->hasVerifiedEmail()
            ? redirect('/dashboard')
            : view('auth/verify-email');
    }

    /**
     * Verify the user's email address
     */
    public function verify(Request $request, string $id, string $hash): Response
    {
        $user = $request->user();

        if (!hash_equals((string) $id, (string) $user->getAuthIdentifier())) {
            return redirect('/login')->withErrors(['email' => 'Invalid verification link.']);
        }

        if (!hash_equals($hash, sha1($user->getEmailForVerification()))) {
            return redirect('/login')->withErrors(['email' => 'Invalid verification link.']);
        }

        if ($user->hasVerifiedEmail()) {
            return redirect('/dashboard')->with('success', 'Your email is already verified.');
        }

        if ($user->markEmailAsVerified()) {
            $this->entityManager->persist($user);
            $this->entityManager->run();

            return redirect('/dashboard')->with('success', 'Your email has been verified!');
        }

        return redirect('/email/verify')->withErrors(['email' => 'Could not verify email.']);
    }

    /**
     * Resend the email verification notification
     */
    public function resend(Request $request): Response
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect('/dashboard');
        }

        $user->sendEmailVerificationNotification();

        return redirect()->back()->with('success', 'Verification link sent!');
    }
}
