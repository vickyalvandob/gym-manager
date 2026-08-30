<?php

namespace App\Actions\Gyms;

use App\Enums\GymRole;
use App\Enums\GymUserStatus;
use App\Models\Gym;
use App\Models\Subscription;
use App\Models\User;
use App\Support\PlatformActivityLogger;
use App\Support\SubscriptionQuota;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateSubscribedGym
{
    public function __construct(
        private readonly SubscriptionQuota $subscriptionQuota,
        private readonly PlatformActivityLogger $activityLogger,
    ) {}

    /** @param array{name: string} $attributes */
    public function handle(User $subscriber, Subscription $subscription, array $attributes): Gym
    {
        return DB::transaction(function () use ($subscriber, $subscription, $attributes): Gym {
            $lockedSubscription = Subscription::query()
                ->whereKey($subscription->getKey())
                ->whereBelongsTo($subscriber, 'subscriber')
                ->with('plan')
                ->lockForUpdate()
                ->firstOrFail();

            $this->subscriptionQuota->ensureCanCreateGym($lockedSubscription);

            $gym = Gym::query()->create([
                'name' => $attributes['name'],
                'slug' => $this->uniqueSlug($attributes['name']),
            ]);
            $gym->forceFill(['subscription_id' => $lockedSubscription->getKey()])->save();
            $gym->users()->attach($subscriber, [
                'role' => GymRole::Owner->value,
                'status' => GymUserStatus::Active->value,
            ]);

            $this->activityLogger->record('gym.created', $gym, [
                'subscriber_id' => $subscriber->getKey(),
                'subscription_id' => $lockedSubscription->getKey(),
                'source' => 'subscriber_multi_gym',
            ]);

            return $gym;
        }, 3);
    }

    private function uniqueSlug(string $name): string
    {
        $baseSlug = Str::slug($name) ?: 'gym';

        return Str::limit($baseSlug, 120, '').'-'.Str::lower(Str::random(6));
    }
}
