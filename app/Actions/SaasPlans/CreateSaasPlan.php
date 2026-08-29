<?php

namespace App\Actions\SaasPlans;

use App\Models\SaasPlan;
use App\Support\PlatformActivityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CreateSaasPlan
{
    public function __construct(private readonly PlatformActivityLogger $activityLogger) {}

    /** @param array<string, mixed> $attributes */
    public function handle(array $attributes): SaasPlan
    {
        return DB::transaction(function () use ($attributes): SaasPlan {
            $attributes['slug'] = $this->uniqueSlug((string) $attributes['name']);
            $plan = SaasPlan::query()->create($attributes);

            $this->activityLogger->record('saas_plan.created', $plan, [
                'name' => $plan->name,
                'price' => $plan->price,
                'billing_interval' => $plan->billing_interval->value,
            ]);

            return $plan;
        }, 3);
    }

    private function uniqueSlug(string $name): string
    {
        $baseSlug = Str::slug($name) ?: 'plan';
        $slug = Str::limit($baseSlug, 120, '');

        if (! SaasPlan::query()->where('slug', $slug)->exists()) {
            return $slug;
        }

        return $slug.'-'.Str::lower(Str::random(6));
    }
}
