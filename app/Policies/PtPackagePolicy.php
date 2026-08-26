<?php

namespace App\Policies;

use App\Enums\GymRole;
use App\Models\PtPackage;
use App\Models\User;
use App\Support\GymContext;

class PtPackagePolicy
{
    public function __construct(private readonly GymContext $gymContext) {}

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->canOperatePt();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, PtPackage $ptPackage): bool
    {
        return $this->belongsToCurrentGym($ptPackage) && $this->canOperatePt();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->isOwner();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PtPackage $ptPackage): bool
    {
        return $this->belongsToCurrentGym($ptPackage) && $this->isOwner();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PtPackage $ptPackage): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PtPackage $ptPackage): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PtPackage $ptPackage): bool
    {
        return false;
    }

    private function canOperatePt(): bool
    {
        return $this->gymContext->hasGym()
            && in_array($this->gymContext->role(), [GymRole::Owner, GymRole::Admin], true);
    }

    private function isOwner(): bool
    {
        return $this->gymContext->hasGym()
            && $this->gymContext->role() === GymRole::Owner;
    }

    private function belongsToCurrentGym(PtPackage $ptPackage): bool
    {
        return $this->gymContext->hasGym()
            && $ptPackage->gym_id === $this->gymContext->gymId();
    }
}
