<?php

namespace App\Policies;

use App\Enums\GymRole;
use App\Models\Payment;
use App\Models\User;
use App\Support\GymContext;

class PaymentPolicy
{
    public function __construct(private readonly GymContext $gymContext) {}

    public function viewAny(User $user): bool
    {
        return $this->canOperateFrontDesk();
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Payment $payment): bool
    {
        return $this->belongsToCurrentGym($payment) && $this->canOperateFrontDesk();
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
    public function update(User $user, Payment $payment): bool
    {
        return $this->belongsToCurrentGym($payment) && $this->canOperateFrontDesk();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Payment $payment): bool
    {
        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Payment $payment): bool
    {
        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Payment $payment): bool
    {
        return false;
    }

    private function canOperateFrontDesk(): bool
    {
        return $this->gymContext->hasGym()
            && in_array($this->gymContext->role(), [GymRole::Owner, GymRole::Admin], true);
    }

    private function belongsToCurrentGym(Payment $payment): bool
    {
        return $this->gymContext->hasGym()
            && $payment->gym_id === $this->gymContext->gymId();
    }
}
