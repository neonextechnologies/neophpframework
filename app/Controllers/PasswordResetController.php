<?php

declare(strict_types=1);

namespace App\Controllers;

use NeoCore\Http\Request;
use NeoCore\Http\Response;
use NeoCore\Auth\Passwords\PasswordResetManager;
use NeoCore\Validation\Validator;

/**
 * Password Reset Controller
 * 
 * Handles password reset requests
 */
class PasswordResetController
{
    protected PasswordResetManager $passwords;

    public function __construct(PasswordResetManager $passwords)
    {
        $this->passwords = $passwords;
    }

    /**
     * Show the password reset request form
     */
    public function showLinkRequestForm(Request $request): Response
    {
        return view('auth/passwords/email');
    }

    /**
     * Send password reset link
     */
    public function sendResetLinkEmail(Request $request): Response
    {
        $validator = new Validator($request->all(), [
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator->errors())
                ->withInput();
        }

        $response = $this->passwords->sendResetLink(
            $request->only('email')
        );

        return $response === PasswordResetManager::RESET_LINK_SENT
            ? redirect()->back()->with('status', 'We have emailed your password reset link!')
            : redirect()->back()->withErrors(['email' => 'We could not find a user with that email address.']);
    }

    /**
     * Show the password reset form
     */
    public function showResetForm(Request $request, string $token): Response
    {
        return view('auth/passwords/reset', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    /**
     * Reset the user's password
     */
    public function reset(Request $request): Response
    {
        $validator = new Validator($request->all(), [
            'token' => 'required',
            'email' => 'required|email',
            'password' => 'required|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator->errors())
                ->withInput();
        }

        $response = $this->passwords->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) {
                $user->password = hash_make($password);
                $user->updated_at = new \DateTimeImmutable();
                // Save user
            }
        );

        return $response === PasswordResetManager::PASSWORD_RESET
            ? redirect('/login')->with('status', 'Your password has been reset!')
            : redirect()->back()->withErrors(['email' => 'Invalid token or email address.']);
    }
}
