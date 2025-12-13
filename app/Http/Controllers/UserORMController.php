<?php

/**
 * Example Controller using ORM and Views
 */

namespace App\Http\Controllers;

use NeoCore\System\Core\Controller;
use NeoCore\System\Core\Request;
use NeoCore\System\Core\Response;
use NeoCore\System\Core\ORMService;
use App\Entities\User;
use App\Repositories\UserRepository;

class UserORMController extends Controller
{
    private UserRepository $userRepository;

    public function __construct()
    {
        $this->userRepository = ORMService::getRepository(User::class);
    }

    /**
     * List all users (HTML view)
     */
    public function index(Request $request, Response $response): Response
    {
        $users = $this->userRepository->findActive(100);
        
        return $this->view($response, 'users/index', [
            'users' => $users,
            'total' => count($users)
        ]);
    }

    /**
     * List all users (JSON API)
     */
    public function apiIndex(Request $request, Response $response): Response
    {
        $users = $this->userRepository->findActive(100);
        
        $data = array_map(function($user) {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'status' => $user->status,
                'created_at' => $user->createdAt->format('Y-m-d H:i:s'),
            ];
        }, $users);

        return $this->respondSuccess($response, $data);
    }

    /**
     * Create new user
     */
    public function store(Request $request, Response $response): Response
    {
        $data = $request->all();

        // Validation
        $errors = $this->validate($data, [
            'name' => 'required|min:3',
            'email' => 'required|email',
            'password' => 'required|min:8',
        ]);

        if (!empty($errors)) {
            return $this->respondValidationError($response, $errors);
        }

        // Check if email exists
        if ($this->userRepository->findByEmail($data['email'])) {
            return $this->respondError($response, 'Email already exists', 422);
        }

        // Create user entity
        $user = new User();
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->setPassword($data['password']);

        // Save to database
        $entityManager = ORMService::getEntityManager();
        $entityManager->persist($user);
        $entityManager->run();

        return $this->respondSuccess($response, [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ], 'User created successfully');
    }

    /**
     * Show single user
     */
    public function show(Request $request, Response $response): Response
    {
        $id = $request->param('id');
        $user = $this->userRepository->findByPK($id);

        if (!$user) {
            return $this->respondNotFound($response, 'User not found');
        }

        return $this->respondSuccess($response, [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'status' => $user->status,
            'last_login' => $user->lastLogin?->format('Y-m-d H:i:s'),
            'created_at' => $user->createdAt->format('Y-m-d H:i:s'),
        ]);
    }

    /**
     * Update user
     */
    public function update(Request $request, Response $response): Response
    {
        $id = $request->param('id');
        $user = $this->userRepository->findByPK($id);

        if (!$user) {
            return $this->respondNotFound($response, 'User not found');
        }

        $data = $request->all();

        // Update fields
        if (isset($data['name'])) {
            $user->name = $data['name'];
        }
        
        if (isset($data['status'])) {
            $user->status = $data['status'];
        }

        $user->updatedAt = new \DateTimeImmutable();

        // Save
        $entityManager = ORMService::getEntityManager();
        $entityManager->persist($user);
        $entityManager->run();

        return $this->respondSuccess($response, [
            'id' => $user->id,
            'name' => $user->name,
        ], 'User updated successfully');
    }

    /**
     * Delete user
     */
    public function delete(Request $request, Response $response): Response
    {
        $id = $request->param('id');
        $user = $this->userRepository->findByPK($id);

        if (!$user) {
            return $this->respondNotFound($response, 'User not found');
        }

        $entityManager = ORMService::getEntityManager();
        $entityManager->delete($user);
        $entityManager->run();

        return $this->respondSuccess($response, null, 'User deleted successfully');
    }

    /**
     * Search users
     */
    public function search(Request $request, Response $response): Response
    {
        $keyword = $request->query('q', '');

        if (empty($keyword)) {
            return $this->respondError($response, 'Search keyword is required', 400);
        }

        $users = $this->userRepository->search($keyword);

        return $this->respondSuccess($response, $users);
    }
}
