<?php

namespace App\Actions\GymSettings;

use App\Models\Gym;
use App\Support\ActivityLogger;
use App\Support\GymContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DeleteGymLogo
{
    public function __construct(
        private readonly GymContext $gymContext,
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function handle(): Gym
    {
        [$gym, $logoPath] = DB::transaction(function (): array {
            $gym = Gym::query()
                ->whereKey($this->gymContext->gymId())
                ->lockForUpdate()
                ->firstOrFail();
            $logoPath = $gym->logo;

            if (is_string($logoPath)) {
                $gym->logo = null;
                $gym->save();

                $this->activityLogger->record('gym.logo_removed', $gym);
            }

            return [$gym, $logoPath];
        }, 3);

        if (is_string($logoPath)) {
            Storage::disk('local')->delete($logoPath);
        }

        return $gym;
    }
}
