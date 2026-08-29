<?php

namespace App\Concerns;

use App\Enums\SaasPlanInterval;
use App\Models\SaasPlan;
use Illuminate\Validation\Rule;

trait SaasPlanValidationRules
{
    /** @return array<string, array<int, mixed>> */
    private function saasPlanRules(?SaasPlan $plan = null): array
    {
        return [
            'name' => ['required', 'string', 'max:120', Rule::unique(SaasPlan::class, 'name')->ignore($plan)],
            'description' => ['nullable', 'string', 'max:2000'],
            'price' => ['required', 'decimal:0,2', 'min:0', 'max:999999999999.99'],
            'currency' => ['required', 'string', 'size:3'],
            'billing_interval' => ['required', Rule::enum(SaasPlanInterval::class)],
            'trial_days' => ['required', 'integer', 'min:0', 'max:365'],
            'max_members' => ['nullable', 'integer', 'min:1', 'max:10000000'],
            'max_staff' => ['nullable', 'integer', 'min:1', 'max:65535'],
            'is_active' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:0', 'max:65535'],
        ];
    }

    private function prepareSaasPlanForValidation(): void
    {
        $this->merge([
            'name' => $this->string('name')->squish()->toString(),
            'description' => $this->filled('description')
                ? $this->string('description')->trim()->toString()
                : null,
            'currency' => $this->string('currency', 'IDR')->upper()->toString(),
        ]);
    }
}
