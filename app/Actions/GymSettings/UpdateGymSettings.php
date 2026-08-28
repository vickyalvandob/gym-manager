<?php

namespace App\Actions\GymSettings;

use App\Models\Gym;
use App\Support\ActivityLogger;
use App\Support\GymContext;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class UpdateGymSettings
{
    public function __construct(
        private readonly GymContext $gymContext,
        private readonly ActivityLogger $activityLogger,
    ) {}

    /** @param array<string, mixed> $attributes */
    public function handle(array $attributes, ?UploadedFile $logo): Gym
    {
        $newLogoPath = $logo?->store('gym-logos/'.$this->gymContext->gymId(), 'local');

        if ($logo !== null && ! is_string($newLogoPath)) {
            throw new RuntimeException('Logo gym gagal disimpan.');
        }

        try {
            [$gym, $oldLogoPath] = DB::transaction(function () use ($attributes, $newLogoPath): array {
                $gym = Gym::query()
                    ->whereKey($this->gymContext->gymId())
                    ->lockForUpdate()
                    ->firstOrFail();
                $oldLogoPath = $gym->logo;

                $gym->fill($attributes);

                if (is_string($newLogoPath)) {
                    $gym->logo = $newLogoPath;
                }

                $changedFields = array_keys($gym->getDirty());
                $gym->save();

                if ($changedFields !== []) {
                    $this->activityLogger->record('gym.settings_updated', $gym, [
                        'fields' => $changedFields,
                    ]);
                }

                return [$gym, $oldLogoPath];
            }, 3);
        } catch (Throwable $exception) {
            if (is_string($newLogoPath)) {
                Storage::disk('local')->delete($newLogoPath);
            }

            throw $exception;
        }

        if (is_string($newLogoPath) && is_string($oldLogoPath)) {
            Storage::disk('local')->delete($oldLogoPath);
        }

        return $gym;
    }
}
