<?php

namespace App\Policies;

use App\Enums\GymRole;
use App\Enums\TrainerStatus;
use App\Models\Trainer;
use App\Models\User;
use App\Support\GymContext;

class TrainerPolicy
{
    public function __construct(private readonly GymContext $gymContext) {}

    public function viewAny(User $user): bool
    {
        return $this->canOperateTrainers();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Trainer $trainer): bool
    {
        return $this->belongsToCurrentGym($trainer)
            && ($this->canOperateTrainers() || $this->isOwnTrainerProfile($user, $trainer));
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
    public function update(User $user, Trainer $trainer): bool
    {
        return $this->belongsToCurrentGym($trainer) && $this->isOwner();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Trainer $trainer): bool
    {
        return false;
    }

    public function assignMembers(User $user, Trainer $trainer): bool
    {
        return $this->belongsToCurrentGym($trainer) && $this->canOperateTrainers();
    }

    public function viewOwnMembers(User $user): bool
    {
        return $this->gymContext->hasGym()
            && $this->gymContext->role() === GymRole::Trainer
            && $this->gymContext->gym()->trainers()
                ->where('user_id', $user->getKey())
                ->where('status', TrainerStatus::Active->value)
                ->exists();
    }

    private function canOperateTrainers(): bool
    {
        return $this->gymContext->hasGym()
            && in_array($this->gymContext->role(), [GymRole::Owner, GymRole::Admin], true);
    }

    private function isOwner(): bool
    {
        return $this->gymContext->hasGym()
            && $this->gymContext->role() === GymRole::Owner;
    }

    private function belongsToCurrentGym(Trainer $trainer): bool
    {
        return $this->gymContext->hasGym()
            && $trainer->gym_id === $this->gymContext->gymId();
    }

    private function isOwnTrainerProfile(User $user, Trainer $trainer): bool
    {
        return $this->gymContext->role() === GymRole::Trainer
            && $trainer->status === TrainerStatus::Active
            && $trainer->user_id === $user->getKey();
    }
}
