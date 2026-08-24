<?php

namespace App\Concerns;

use App\Enums\MembershipDurationUnit;
use App\Models\MembershipPlan;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

trait MembershipPlanValidationRules
{
    /** @return array<string, array<mixed>> */
    protected function membershipPlanRules(int $gymId, ?int $ignoreId = null): array
    {
        $uniqueName = Rule::unique(MembershipPlan::class, 'name')
            ->where(fn (Builder $query): Builder => $query->where('gym_id', $gymId));

        if ($ignoreId !== null) {
            $uniqueName->ignore($ignoreId);
        }

        return [
            'name' => ['required', 'string', 'max:120', $uniqueName],
            'duration' => ['required', 'integer', 'min:1', 'max:3650'],
            'duration_unit' => ['required', Rule::enum(MembershipDurationUnit::class)],
            'price' => ['required', 'numeric', 'min:0', 'max:999999999999.99', 'decimal:0,2'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareMembershipPlanForValidation(): void
    {
        $this->merge([
            'name' => Str::squish((string) $this->input('name')),
            'description' => $this->filled('description')
                ? Str::of((string) $this->input('description'))->trim()->toString()
                : null,
        ]);
    }
}
