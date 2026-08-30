<?php

namespace App\Actions\Staff;

use App\Enums\GymRole;
use App\Enums\GymUserStatus;
use App\Models\User;
use App\Support\ActivityLogger;
use App\Support\GymContext;
use Illuminate\Support\Facades\DB;

class UpdateFrontDesk
{
    public function __construct(
        private readonly GymContext $gymContext,
        private readonly ActivityLogger $activityLogger,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function handle(User $frontDesk, array $attributes): User
    {
        return DB::transaction(function () use ($frontDesk, $attributes): User {
            $scopedUser = $this->gymContext->gym()->users()
                ->whereKey($frontDesk->getKey())
                ->wherePivot('role', GymRole::Admin->value)
                ->lockForUpdate()
                ->firstOrFail();
            $previousStatus = GymUserStatus::from((string) $scopedUser->getRelation('pivot')->getAttribute('status'));
            $status = GymUserStatus::from((string) $attributes['status']);
            $userAttributes = [
                'name' => $attributes['name'],
                'email' => $attributes['email'],
                'email_verified_at' => now(),
            ];

            if (is_string($attributes['password'] ?? null) && $attributes['password'] !== '') {
                $userAttributes['password'] = $attributes['password'];
            }

            $scopedUser->forceFill($userAttributes)->save();
            $this->gymContext->gym()->users()->updateExistingPivot($scopedUser->getKey(), [
                'status' => $status->value,
            ]);

            $this->activityLogger->record('staff.updated', $scopedUser, [
                'role' => GymRole::Admin->value,
                'status' => $status->value,
            ]);

            if ($previousStatus === GymUserStatus::Active && $status === GymUserStatus::Inactive) {
                $this->activityLogger->record('staff.deactivated', $scopedUser, [
                    'role' => GymRole::Admin->value,
                ]);
            }

            return $scopedUser;
        }, 3);
    }
}
