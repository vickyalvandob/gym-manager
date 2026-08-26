<?php

namespace App\Actions\Trainers;

use App\Enums\GymUserStatus;
use App\Enums\TrainerStatus;
use App\Models\Trainer;
use App\Support\ActivityLogger;
use App\Support\GymContext;
use Illuminate\Support\Facades\DB;

class UpdateTrainer
{
    public function __construct(
        private readonly GymContext $gymContext,
        private readonly ActivityLogger $activityLogger,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function handle(Trainer $trainer, array $attributes): Trainer
    {
        return DB::transaction(function () use ($trainer, $attributes): Trainer {
            $wasActive = $trainer->status === TrainerStatus::Active;
            $trainer->fill($attributes);
            $changedFields = array_keys($trainer->getDirty());
            $trainer->save();

            if ($trainer->user !== null) {
                $trainer->user->forceFill([
                    'name' => $trainer->name,
                    'email' => $trainer->email,
                    'email_verified_at' => now(),
                ])->save();
                $this->gymContext->gym()->users()->updateExistingPivot(
                    $trainer->user->getKey(),
                    [
                        'status' => $trainer->status === TrainerStatus::Active
                            ? GymUserStatus::Active->value
                            : GymUserStatus::Inactive->value,
                    ],
                );
            }

            $this->activityLogger->record('trainer.updated', $trainer, [
                'name' => $trainer->name,
                'fields' => $changedFields,
            ]);

            if ($wasActive && $trainer->status === TrainerStatus::Inactive) {
                $this->activityLogger->record('trainer.deactivated', $trainer, [
                    'name' => $trainer->name,
                ]);
            }

            return $trainer;
        }, 3);
    }
}
