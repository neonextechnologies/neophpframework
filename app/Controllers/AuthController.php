<?php

declare(strict_types=1);

namespace App\Controllers;

use NeoCore\Http\Request;
use NeoCore\Http\Response;
use NeoCore\Auth\AuthManager;
use NeoCore\Validation\Validator;

/**
 * Authentication Controller
 * 
 * Handles user authentication (login, logout)
 */
class AuthController
{
    protected AuthManager $auth;

    public function __construct(AuthManager $auth)
    {
        $this->auth = $auth;
    }

    /**
     * Show the login form
     */
    public function showLoginForm(Request $request): Response
    {
        return view('auth/login');
    }

    /**
     * Handle login request
     */
    public function login(Request $request): Response
    {
        // Validate credentials
        $validator = new Validator($request->all(), [
            'email' => 'required|email',
            'password' => 'required|min:6'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator->errors())
                ->withInput();
        }

        $credentials = $request->only(['email', 'password']);
        $remember = $request->input('remember', false);

        if ($this->auth->attempt($credentials, $remember)) {
            $request->session()->regenerate();
            
            return redirect()->intended('/dashboard')
                ->with('success', 'You have been logged in successfully.');
        }

        return redirect()->back()
            ->withErrors(['email' => 'The provided credentials do not match our records.'])
            ->withInput($request->only('email'));
    }

    /**
     * Handle logout request
     */
    public function logout(Request $request): Response
    {
        $this->auth->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/login')
            ->with('success', 'You have been logged out successfully.');
    }
}
