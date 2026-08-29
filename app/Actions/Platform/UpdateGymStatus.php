<?php

namespace App\Actions\Platform;

use App\Enums\GymStatus;
use App\Models\Gym;
use App\Support\PlatformActivityLogger;
use Illuminate\Support\Facades\DB;

class UpdateGymStatus
{
    public function __construct(private readonly PlatformActivityLogger $activityLogger) {}

    public function handle(Gym $gym, GymStatus $status): Gym
    {
        return DB::transaction(function () use ($gym, $status): Gym {
            $lockedGym = Gym::query()
                ->whereKey($gym->getKey())
                ->lockForUpdate()
                ->firstOrFail();
            $previousStatus = $lockedGym->status;

            if ($previousStatus === $status) {
                return $lockedGym;
            }

            $lockedGym->update(['status' => $status]);

            $this->activityLogger->record('gym.status_changed', $lockedGym, [
                'name' => $lockedGym->name,
                'from' => $previousStatus->value,
                'to' => $status->value,
            ]);

            return $lockedGym;
        }, 3);
    }
}
