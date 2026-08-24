<?php

namespace App\Policies;

use App\Enums\GymRole;
use App\Models\MembershipPlan;
use App\Models\User;
use App\Support\GymContext;

class MembershipPlanPolicy
{
    public function __construct(private readonly GymContext $gymContext) {}

    public function viewAny(User $user): bool
    {
        return $this->canManageMembershipPlans();
    }

    public function view(User $user, MembershipPlan $membershipPlan): bool
    {
        return $this->belongsToCurrentGym($membershipPlan) && $this->canManageMembershipPlans();
    }

    public function create(User $user): bool
    {
        return $this->canManageMembershipPlans();
    }

    public function update(User $user, MembershipPlan $membershipPlan): bool
    {
        return $this->belongsToCurrentGym($membershipPlan) && $this->canManageMembershipPlans();
    }

    public function delete(User $user, MembershipPlan $membershipPlan): bool
    {
        return $this->belongsToCurrentGym($membershipPlan) && $this->canManageMembershipPlans();
    }

    public function updateStatus(User $user, MembershipPlan $membershipPlan): bool
    {
        return $this->belongsToCurrentGym($membershipPlan) && $this->canManageMembershipPlans();
    }

    private function canManageMembershipPlans(): bool
    {
        return $this->gymContext->hasGym()
            && in_array($this->gymContext->role(), [GymRole::Owner, GymRole::Admin], true);
    }

    private function belongsToCurrentGym(MembershipPlan $membershipPlan): bool
    {
        return $this->gymContext->hasGym()
            && $membershipPlan->gym_id === $this->gymContext->gymId();
    }
}
