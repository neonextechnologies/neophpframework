<?php

declare(strict_types=1);

namespace App\Policies;

use App\Entities\User;
use App\Entities\Post;

/**
 * Post Policy
 * 
 * Example policy for Post authorization
 */
class PostPolicy
{
    /**
     * Determine if the given post can be viewed by the user
     */
    public function view(?User $user, Post $post): bool
    {
        // Anyone can view published posts
        if ($post->status === 'published') {
            return true;
        }

        // Only author can view draft posts
        return $user && $post->user_id === $user->id;
    }

    /**
     * Determine if the given user can create posts
     */
    public function create(User $user): bool
    {
        return $user->hasPermission('posts.create');
    }

    /**
     * Determine if the given post can be updated by the user
     */
    public function update(User $user, Post $post): bool
    {
        // Author can update their own posts
        if ($post->user_id === $user->id) {
            return true;
        }

        // Editors can update any post
        return $user->hasPermission('posts.update.any');
    }

    /**
     * Determine if the given post can be deleted by the user
     */
    public function delete(User $user, Post $post): bool
    {
        // Author can delete their own posts
        if ($post->user_id === $user->id) {
            return true;
        }

        // Admins can delete any post
        return $user->hasPermission('posts.delete.any');
    }

    /**
     * Determine actions before other methods are called
     */
    public function before(User $user, string $ability): ?bool
    {
        // Super admins can do anything
        if ($user->hasRole('super-admin')) {
            return true;
        }

        return null;
    }
}
