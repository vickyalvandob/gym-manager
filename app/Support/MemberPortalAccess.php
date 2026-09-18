<?php

namespace App\Support;

use App\Enums\GymStatus;
use App\Models\Member;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class MemberPortalAccess
{
    public function allows(User $user, Member $member): bool
    {
        if ($member->user_id !== $user->getKey()) {
            return false;
        }

        $member->loadMissing(['gym.subscription']);

        return $member->gym->status === GymStatus::Active
            && $member->gym->subscription?->grantsAccess() === true;
    }

    /** @return Collection<int, Member> */
    public function profiles(User $user): Collection
    {
        return $user->memberProfiles()
            ->select([
                'members.id',
                'members.gym_id',
                'members.user_id',
                'members.member_number',
                'members.name',
                'members.status',
            ])
            ->with([
                'gym:id,subscription_id,name,slug,status,timezone,currency',
                'gym.subscription:id,status,trial_ends_at,current_period_ends_at',
            ])
            ->oldest('members.id')
            ->get()
            ->filter(fn (Member $member): bool => $this->allows($user, $member))
            ->values();
    }

    public function firstProfile(User $user): ?Member
    {
        return $this->profiles($user)->first();
    }
}
