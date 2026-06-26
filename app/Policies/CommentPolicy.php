<?php

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;

class CommentPolicy
{
    public function update(User $user, Comment $comment): bool
    {
        return $user->id === $comment->user_id
            && $comment->status === Comment::STATUS_APPROVED;
    }

    public function delete(User $user, Comment $comment): bool
    {
        return $user->id === $comment->user_id
            || $user->hasPermission('manage_comments');
    }

    public function hide(User $user, Comment $comment): bool
    {
        return $user->can('manage_blog_posts');
    }

    public function restore(User $user, Comment $comment): bool
    {
        return $user->can('manage_blog_posts');
    }

    public function pin(User $user, Comment $comment): bool
    {
        return $user->can('manage_blog_posts');
    }

    public function moderate(User $user): bool
    {
        return $user->can('manage_blog_posts');
    }
}