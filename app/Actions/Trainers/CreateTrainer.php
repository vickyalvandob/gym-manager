<?php

namespace App\Actions\Trainers;

use App\Enums\GymRole;
use App\Enums\GymUserStatus;
use App\Enums\TrainerStatus;
use App\Models\Gym;
use App\Models\Trainer;
use App\Models\User;
use App\Support\ActivityLogger;
use App\Support\GymContext;
use Illuminate\Support\Facades\DB;

class CreateTrainer
{
    public function __construct(
        private readonly GymContext $gymContext,
        private readonly ActivityLogger $activityLogger,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function handle(array $attributes): Trainer
    {
        return DB::transaction(function () use ($attributes): Trainer {
            $lockedGym = Gym::query()
                ->whereKey($this->gymContext->gymId())
                ->lockForUpdate()
                ->firstOrFail();
            $password = is_string($attributes['password'] ?? null)
                ? $attributes['password']
                : '';
            unset(
                $attributes['password'],
                $attributes['password_confirmation'],
            );
            $user = User::query()->create([
                'name' => $attributes['name'],
                'email' => $attributes['email'],
                'password' => $password,
            ]);
            $user->forceFill(['email_verified_at' => now()])->save();
            $lockedGym->users()->attach($user, [
                'role' => GymRole::Trainer->value,
                'status' => $attributes['status'] === TrainerStatus::Active->value
                    ? GymUserStatus::Active->value
                    : GymUserStatus::Inactive->value,
            ]);
            $attributes['user_id'] = $user->getKey();

            $this->activityLogger->record('user.created', $user, [
                'source' => 'trainer.created',
                'role' => GymRole::Trainer->value,
            ]);

            $attributes['trainer_code'] = sprintf(
                'TRN-%06d',
                $lockedGym->next_trainer_sequence,
            );
            $trainer = $lockedGym->trainers()->create($attributes);
            $lockedGym->forceFill([
                'next_trainer_sequence' => $lockedGym->next_trainer_sequence + 1,
            ])->save();

            $this->activityLogger->record('trainer.created', $trainer, [
                'trainer_code' => $trainer->trainer_code,
                'name' => $trainer->name,
                'status' => $trainer->status->value,
                'user_id' => $trainer->user_id,
            ]);

            return $trainer;
        }, 3);
    }
}
