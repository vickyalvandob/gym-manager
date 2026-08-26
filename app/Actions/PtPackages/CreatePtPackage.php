<?php

namespace App\Actions\PtPackages;

use App\Models\PtPackage;
use App\Support\ActivityLogger;
use App\Support\GymContext;
use Illuminate\Support\Facades\DB;

class CreatePtPackage
{
    public function __construct(
        private readonly GymContext $gymContext,
        private readonly ActivityLogger $activityLogger,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function handle(array $attributes): PtPackage
    {
        return DB::transaction(function () use ($attributes): PtPackage {
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
