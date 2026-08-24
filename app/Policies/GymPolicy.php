<?php

namespace App\Policies;

use App\Enums\GymRole;
use App\Enums\GymUserStatus;
use App\Models\Gym;
use App\Models\User;

class GymPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->gyms()
            ->wherePivot('status', GymUserStatus::Active->value)
            ->exists();
    }

    public function view(User $user, Gym $gym): bool
    {
        return $this->roleFor($user, $gym) !== null;
    }

    public function update(User $user, Gym $gym): bool
    {
        return $this->roleFor($user, $gym) === GymRole::Owner;
    }

    public function manageUsers(User $user, Gym $gym): bool
    {
        return $this->roleFor($user, $gym) === GymRole::Owner;
    }

    public function operateFrontDesk(User $user, Gym $gym): bool
    {
        return in_array($this->roleFor($user, $gym), [GymRole::Owner, GymRole::Admin], true);
    }

    private function roleFor(User $user, Gym $gym): ?GymRole
    {
        $role = $user->gyms()
            ->whereKey($gym->getKey())
            ->wherePivot('status', GymUserStatus::Active->value)
            ->value('gym_user.role');

        return is_string($role) ? GymRole::tryFrom($role) : null;
    }
}
