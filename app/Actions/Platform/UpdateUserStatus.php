<?php

namespace App\Actions\Platform;

use App\Models\User;
use App\Support\PlatformActivityLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateUserStatus
{
    public function __construct(private readonly PlatformActivityLogger $activityLogger) {}

    public function handle(User $actor, User $user, bool $isActive): User
    {
        return DB::transaction(function () use ($actor, $user, $isActive): User {
            $lockedUser = User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();

            if ($lockedUser->is_platform_admin || $lockedUser->is($actor)) {
                throw ValidationException::withMessages([
                    'is_active' => 'Akses akun Platform Super Admin tidak dapat diubah dari halaman ini.',
                ]);
            }

            $previousStatus = $lockedUser->is_active;
            $lockedUser->forceFill(['is_active' => $isActive])->save();

            if ($previousStatus !== $isActive) {
                $this->activityLogger->record('user.access_updated', $lockedUser, [
                    'from' => $previousStatus ? 'active' : 'inactive',
                    'to' => $isActive ? 'active' : 'inactive',
                ]);
            }

            return $lockedUser;
        }, 3);
    }
}
