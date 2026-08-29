<?php

namespace App\Http\Responses;

use App\Enums\GymRole;
use App\Enums\GymStatus;
use App\Enums\GymUserStatus;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): JsonResponse|RedirectResponse
    {
        if ($request->wantsJson()) {
            return response()->json(['two_factor' => false]);
        }

        $user = $request->user();

        if ($user?->is_platform_admin === true) {
            return redirect()->intended(route('platform.dashboard'));
        }

        $hasActiveGym = $user?->gyms()
            ->where('gyms.status', GymStatus::Active->value)
            ->wherePivot('status', GymUserStatus::Active->value)
            ->exists() ?? false;
        $ownsSuspendedGym = $user?->gyms()
            ->where('gyms.status', GymStatus::Suspended->value)
            ->wherePivot('status', GymUserStatus::Active->value)
            ->wherePivot('role', GymRole::Owner->value)
            ->exists() ?? false;

        $destination = ! $hasActiveGym && $ownsSuspendedGym
            ? route('subscription.show')
            : route('dashboard');

        return redirect()->intended($destination);
    }
}
