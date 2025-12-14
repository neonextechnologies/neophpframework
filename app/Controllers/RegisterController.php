<?php

declare(strict_types=1);

namespace App\Controllers;

use NeoCore\Http\Request;
use NeoCore\Http\Response;
use NeoCore\Auth\AuthManager;
use NeoCore\Auth\Hash\HashManager;
use NeoCore\Validation\Validator;
use Cycle\ORM\EntityManagerInterface;
use App\Entities\User;

/**
 * Registration Controller
 * 
 * Handles user registration
 */
class RegisterController
{
    protected AuthManager $auth;
    protected HashManager $hash;
    protected EntityManagerInterface $entityManager;

    public function __construct(
        AuthManager $auth,
        HashManager $hash,
        EntityManagerInterface $entityManager
    ) {
        $this->auth = $auth;
        $this->hash = $hash;
        $this->entityManager = $entityManager;
    }

    /**
     * Show the registration form
     */
    public function showRegistrationForm(Request $request): Response
    {
        return view('auth/register');
    }

    /**
     * Handle registration request
     */
    public function register(Request $request): Response
    {
        // Validate input
        $validator = new Validator($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator->errors())
                ->withInput();
        }

        // Create user
        $user = $this->createUser($request->only(['name', 'email', 'password']));

        // Log the user in
        $this->auth->login($user);

        return redirect('/dashboard')
            ->with('success', 'Registration successful! Welcome to NeoCore.');
    }

    /**
     * Create a new user
     */
    protected function createUser(array $data): User
    {
        $user = new User();
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->password = $this->hash->make($data['password']);
        $user->email_verified_at = null;
        $user->created_at = new \DateTimeImmutable();
        $user->updated_at = new \DateTimeImmutable();

        $this->entityManager->persist($user);
        $this->entityManager->run();

        return $user;
    }
}
