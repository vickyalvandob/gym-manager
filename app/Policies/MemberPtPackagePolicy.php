<?php

namespace App\Policies;

use App\Enums\GymRole;
use App\Models\MemberPtPackage;
use App\Models\User;
use App\Support\GymContext;

class MemberPtPackagePolicy
{
    public function __construct(private readonly GymContext $gymContext) {}

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->gymContext->hasGym();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, MemberPtPackage $memberPtPackage): bool
    {
        return $this->belongsToCurrentGym($memberPtPackage)
            && ($this->canOperatePt() || $this->isAssignedTrainer($user, $memberPtPackage));
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->canOperatePt();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, MemberPtPackage $memberPtPackage): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, MemberPtPackage $memberPtPackage): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, MemberPtPackage $memberPtPackage): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, MemberPtPackage $memberPtPackage): bool
    {
        return false;
    }

    private function canOperatePt(): bool
    {
        return $this->gymContext->hasGym()
            && in_array($this->gymContext->role(), [GymRole::Owner, GymRole::Admin], true);
    }

    private function belongsToCurrentGym(MemberPtPackage $memberPtPackage): bool
    {
        return $this->gymContext->hasGym()
            && $memberPtPackage->gym_id === $this->gymContext->gymId();
    }

    private function isAssignedTrainer(User $user, MemberPtPackage $memberPtPackage): bool
    {
        return $this->gymContext->role() === GymRole::Trainer
            && $memberPtPackage->trainer()->where('user_id', $user->getKey())->exists();
    }
}
