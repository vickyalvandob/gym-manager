<?php

namespace App\Actions\Platform;

use App\Models\PlatformBillingSetting;
use App\Models\User;
use App\Support\PlatformActivityLogger;
use Illuminate\Support\Facades\DB;

class UpdatePlatformBillingSetting
{
    public function __construct(private readonly PlatformActivityLogger $activityLogger) {}

    /** @param array{bank_name: string, account_name: string, account_number: string, instructions?: string|null} $attributes */
    public function handle(array $attributes, User $actor): PlatformBillingSetting
    {
        return DB::transaction(function () use ($attributes, $actor): PlatformBillingSetting {
            DB::table('platform_billing_settings')->insertOrIgnore([
                'id' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $settings = PlatformBillingSetting::query()->lockForUpdate()->findOrFail(1);

            $settings->forceFill([
                ...$attributes,
                'instructions' => filled($attributes['instructions'] ?? null)
                    ? $attributes['instructions']
                    : null,
                'updated_by' => $actor->getKey(),
            ])->save();

            $this->activityLogger->record('platform_billing.updated', $settings, [
                'bank_name' => $settings->bank_name,
                'account_name' => $settings->account_name,
            ]);

            return $settings;
        }, 3);
    }
}
