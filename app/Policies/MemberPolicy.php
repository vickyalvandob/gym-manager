<?php

namespace App\Policies;

use App\Enums\GymRole;
use App\Models\Member;
use App\Models\User;
use App\Support\GymContext;

class MemberPolicy
{
    public function __construct(private readonly GymContext $gymContext) {}

    public function viewAny(User $user): bool
    {
        return $this->canManageMembers();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Member $member): bool
    {
        return $this->belongsToCurrentGym($member) && $this->canManageMembers();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->canManageMembers();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Member $member): bool
    {
        return $this->belongsToCurrentGym($member) && $this->canManageMembers();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Member $member): bool
    {
        return false;
    }

    public function updateStatus(User $user, Member $member): bool
    {
        return $this->belongsToCurrentGym($member) && $this->canManageMembers();
    }

    private function canManageMembers(): bool
    {
        return $this->gymContext->hasGym()
            && in_array($this->gymContext->role(), [GymRole::Owner, GymRole::Admin], true);
    }

    private function belongsToCurrentGym(Member $member): bool
    {
        return $this->gymContext->hasGym()
            && $member->gym_id === $this->gymContext->gymId();
    }
}
