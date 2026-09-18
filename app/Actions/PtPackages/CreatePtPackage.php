<?php

namespace App\Actions\PtPackages;

use App\Models\PtPackage;
use App\Models\Subscription;
use App\Support\ActivityLogger;
use App\Support\GymContext;
use App\Support\SubscriptionQuota;
use Illuminate\Support\Facades\DB;

class CreatePtPackage
{
    public function __construct(
        private readonly GymContext $gymContext,
        private readonly ActivityLogger $activityLogger,
        private readonly SubscriptionQuota $subscriptionQuota,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function handle(array $attributes): PtPackage
    {
        return DB::transaction(function () use ($attributes): PtPackage {
            $subscriptionId = $this->gymContext->gym()->subscription_id;
            if ($subscriptionId !== null) {
                $subscription = Subscription::query()->with('plan')->whereKey($subscriptionId)
                    ->lockForUpdate()->firstOrFail();
                $this->subscriptionQuota->ensureCanCreatePtPackage($subscription);
            }

            $ptPackage = $this->gymContext->gym()->ptPackages()->create($attributes);

            $this->activityLogger->record('pt_package.created', $ptPackage, [
                'name' => $ptPackage->name,
                'session_count' => $ptPackage->session_count,
                'price' => $ptPackage->price,
                'is_active' => $ptPackage->is_active,
            ]);

            return $ptPackage;
        }, 3);
    }
}
