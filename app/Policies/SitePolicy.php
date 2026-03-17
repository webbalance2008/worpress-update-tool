<?php

namespace App\Policies;

use App\Models\Site;
use App\Models\User;

class SitePolicy
{
    /**
     * Only the site owner can view it.
     */
    public function view(User $user, Site $site): bool
    {
        return $user->id === $site->user_id;
    }

    /**
     * Only the site owner can update it.
     */
    public function update(User $user, Site $site): bool
    {
        return $user->id === $site->user_id;
    }

    /**
     * Only the site owner can delete it.
     */
    public function delete(User $user, Site $site): bool
    {
        return $user->id === $site->user_id;
    }
}
