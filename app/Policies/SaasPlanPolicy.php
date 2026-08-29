<?php

namespace App\Policies;

use App\Models\SaasPlan;
use App\Models\User;

class SaasPlanPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->is_platform_admin;
    }

    public function view(User $user, SaasPlan $saasPlan): bool
    {
        return $user->is_platform_admin;
    }

    public function create(User $user): bool
    {
        return $user->is_platform_admin;
    }

    public function update(User $user, SaasPlan $saasPlan): bool
    {
        return $user->is_platform_admin;
    }
}
