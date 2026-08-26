<?php

namespace App\Actions\PtPackages;

use App\Models\PtPackage;
use App\Support\ActivityLogger;
use Illuminate\Support\Facades\DB;

class UpdatePtPackage
{
    public function __construct(private readonly ActivityLogger $activityLogger) {}

    /** @param array<string, mixed> $attributes */
    public function handle(PtPackage $ptPackage, array $attributes): PtPackage
    {
        return DB::transaction(function () use ($ptPackage, $attributes): PtPackage {
            $ptPackage->fill($attributes);
            $changedFields = array_keys($ptPackage->getDirty());
            $ptPackage->save();

            $this->activityLogger->record('pt_package.updated', $ptPackage, [
                'name' => $ptPackage->name,
                'fields' => $changedFields,
            ]);

            return $ptPackage;
        }, 3);
    }
}
