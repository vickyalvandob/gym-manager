<?php

namespace App\Policies;

use App\Enums\GymRole;
use App\Models\CheckIn;
use App\Models\User;
use App\Support\GymContext;

class CheckInPolicy
{
    public function __construct(private readonly GymContext $gymContext) {}

    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->canOperateFrontDesk();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, CheckIn $checkIn): bool
    {
        return $this->belongsToCurrentGym($checkIn) && $this->canOperateFrontDesk();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->canOperateFrontDesk();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, CheckIn $checkIn): bool
    {
        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, CheckIn $checkIn): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, CheckIn $checkIn): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, CheckIn $checkIn): bool
    {
        return false;
    }

    private function canOperateFrontDesk(): bool
    {
        return $this->gymContext->hasGym()
            && in_array($this->gymContext->role(), [GymRole::Owner, GymRole::Admin], true);
    }

    private function belongsToCurrentGym(CheckIn $checkIn): bool
    {
        return $this->gymContext->hasGym()
            && $checkIn->gym_id === $this->gymContext->gymId();
    }
}
