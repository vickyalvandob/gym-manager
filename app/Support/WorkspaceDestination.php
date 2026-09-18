<?php

namespace App\Support;

use App\Enums\GymRole;
use App\Enums\GymStatus;
use App\Enums\GymUserStatus;
use App\Models\User;

class WorkspaceDestination
{
    public function routeName(User $user, ?int $currentGymId = null): string
    {
        if ($user->is_platform_admin) {
            return 'platform.dashboard';
        }

        $gym = $user->gyms()
            ->select(['gyms.id'])
            ->where('gyms.status', GymStatus::Active)
            ->wherePivot('status', GymUserStatus::Active->value)
            ->orderByRaw('case when gyms.id = ? then 0 else 1 end', [$currentGymId ?? 0])
            ->oldest('gym_user.created_at')
            ->first();

        if ($gym !== null) {
            return $gym->getRelation('pivot')->getAttribute('role') === GymRole::Trainer->value
                ? 'trainer.dashboard'
                : 'app.home';
        }

        if ($user->memberProfiles()->exists()) {
            return 'my.show';
        }

        return $user->gyms()->wherePivot('role', GymRole::Owner->value)
            ->wherePivot('status', GymUserStatus::Active->value)->exists()
                ? 'subscription.show'
                : 'app.home';
    }
}
