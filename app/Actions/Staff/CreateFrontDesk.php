<?php

namespace App\Actions\Staff;

use App\Enums\GymRole;
use App\Models\Gym;
use App\Models\Subscription;
use App\Models\User;
use App\Support\ActivityLogger;
use App\Support\GymContext;
use App\Support\SubscriptionQuota;
use Illuminate\Support\Facades\DB;

class CreateFrontDesk
{
    public function __construct(
        private readonly GymContext $gymContext,
        private readonly ActivityLogger $activityLogger,
        private readonly SubscriptionQuota $subscriptionQuota,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function handle(array $attributes): User
    {
        return DB::transaction(function () use ($attributes): User {
            $subscriptionId = $this->gymContext->gym()->subscription_id;

            if ($subscriptionId !== null) {
                $subscription = Subscription::query()
                    ->whereKey($subscriptionId)
                    ->with('plan')
                    ->lockForUpdate()
                    ->firstOrFail();
                $this->subscriptionQuota->ensureCanCreateStaff($subscription);
            }

            $gym = Gym::query()
                ->whereKey($this->gymContext->gymId())
                ->lockForUpdate()
                ->firstOrFail();
            $user = User::query()->create([
                'name' => $attributes['name'],
                'email' => $attributes['email'],
                'password' => $attributes['password'],
            ]);
            $user->forceFill(['email_verified_at' => now()])->save();
            $gym->users()->attach($user, [
                'role' => GymRole::Admin->value,
                'status' => $attributes['status'],
            ]);

            $this->activityLogger->record('user.created', $user, [
                'source' => 'staff.created',
                'role' => GymRole::Admin->value,
            ]);
            $this->activityLogger->record('staff.created', $user, [
                'role' => GymRole::Admin->value,
                'status' => $attributes['status'],
            ]);

            return $user;
        }, 3);
    }
}
