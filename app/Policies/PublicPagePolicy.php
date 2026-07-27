<?php

namespace App\Policies;

use App\Models\PublicPage;
use App\Models\User;

class PublicPagePolicy
{
    public function viewAny(User $user): bool { return $user->hasPermission('manage_public_pages'); }
    public function view(User $user, PublicPage $page): bool { return $user->hasPermission('manage_public_pages'); }
    public function create(User $user): bool { return $user->hasPermission('manage_public_pages'); }
    public function update(User $user, PublicPage $page): bool { return $user->hasPermission('manage_public_pages'); }
    public function delete(User $user, PublicPage $page): bool { return $user->hasPermission('manage_public_pages'); }
    public function publish(User $user, PublicPage $page): bool { return $user->hasPermission('publish_public_pages'); }
}
