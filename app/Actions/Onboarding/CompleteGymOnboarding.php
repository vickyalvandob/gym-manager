<?php

namespace App\Actions\Onboarding;

use App\Models\Gym;
use App\Support\ActivityLogger;
use App\Support\GymContext;
use Illuminate\Support\Facades\DB;

class CompleteGymOnboarding
{
    public function __construct(
        private readonly GymContext $gymContext,
        private readonly ActivityLogger $activityLogger,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function handle(array $attributes): Gym
    {
        return DB::transaction(function () use ($attributes): Gym {
            $gym = Gym::query()->lockForUpdate()->findOrFail($this->gymContext->gymId());

            if ($gym->onboarding_completed_at !== null) {
                return $gym;
            }

            $gym->fill($attributes);
            $gym->forceFill([
                'currency' => 'IDR',
                'onboarding_completed_at' => now(),
            ]);
            $gym->save();

            $this->activityLogger->record('gym.onboarding_completed', $gym, [
                'name' => $gym->name,
                'timezone' => $gym->timezone,
                'currency' => $gym->currency,
            ]);

            return $gym;
        }, 3);
    }
}
