<?php

namespace App\Policies;

use App\Enums\GymRole;
use App\Enums\TrainerStatus;
use App\Models\MemberPtPackage;
use App\Models\PtSession;
use App\Models\User;
use App\Support\GymContext;

class PtSessionPolicy
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
    public function view(User $user, PtSession $ptSession): bool
    {
        return $this->belongsToCurrentGym($ptSession)
            && ($this->canOperatePt() || $this->ownsTrainer($user, $ptSession->trainer_id));
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->gymContext->hasGym();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, PtSession $ptSession): bool
    {
        return $this->view($user, $ptSession);
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, PtSession $ptSession): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, PtSession $ptSession): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, PtSession $ptSession): bool
    {
        return false;
    }

    public function schedule(User $user, MemberPtPackage $memberPtPackage): bool
    {
        return $memberPtPackage->gym_id === $this->gymContext->gymId()
            && ($this->canOperatePt() || $this->ownsTrainer($user, $memberPtPackage->trainer_id));
    }

    public function complete(User $user, PtSession $ptSession): bool
    {
        return $this->belongsToCurrentGym($ptSession)
            && $this->ownsTrainer($user, $ptSession->trainer_id);
    }

    public function markNoShow(User $user, PtSession $ptSession): bool
    {
        return $this->complete($user, $ptSession);
    }

    private function canOperatePt(): bool
    {
        return $this->gymContext->hasGym()
            && in_array($this->gymContext->role(), [GymRole::Owner, GymRole::Admin], true);
    }

    private function belongsToCurrentGym(PtSession $ptSession): bool
    {
        return $this->gymContext->hasGym()
            && $ptSession->gym_id === $this->gymContext->gymId();
    }

    private function ownsTrainer(User $user, int $trainerId): bool
    {
        return $this->gymContext->hasGym()
            && $this->gymContext->role() === GymRole::Trainer
            && $this->gymContext->gym()->trainers()
                ->whereKey($trainerId)
                ->where('user_id', $user->getKey())
                ->where('status', TrainerStatus::Active->value)
                ->exists();
    }
}
