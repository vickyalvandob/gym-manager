<?php

namespace App\Concerns;

use App\Models\PtPackage;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

trait PtPackageValidationRules
{
    /** @return array<string, array<mixed>> */
    protected function ptPackageRules(int $gymId, ?int $ignoreId = null): array
    {
        $uniqueName = Rule::unique(PtPackage::class, 'name')
            ->where(fn (Builder $query): Builder => $query->where('gym_id', $gymId));

        if ($ignoreId !== null) {
            $uniqueName->ignore($ignoreId);
        }

        return [
            'name' => ['required', 'string', 'max:120', $uniqueName],
            'session_count' => ['required', 'integer', 'min:1', 'max:1000'],
            'validity_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'price' => ['required', 'numeric', 'min:0', 'max:999999999999.99', 'decimal:0,2'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function preparePtPackageForValidation(): void
    {
        $this->merge([
            'name' => Str::squish((string) $this->input('name')),
            'validity_days' => $this->filled('validity_days')
                ? (int) $this->input('validity_days')
                : null,
            'description' => $this->filled('description')
                ? Str::of((string) $this->input('description'))->trim()->toString()
                : null,
        ]);
    }
}
