<?php

namespace App\Http\Responses;

use App\Enums\GymRole;
use App\Enums\GymStatus;
use App\Enums\GymUserStatus;
use App\Models\Member;
use App\Models\User;
use App\Support\MemberPortalAccess;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function __construct(private readonly MemberPortalAccess $memberPortalAccess) {}

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

        $hasMemberPortal = $user instanceof User
            && $this->memberPortalAccess->firstProfile($user) instanceof Member;
        $destination = match (true) {
            ! $hasActiveGym && $hasMemberPortal => route('member-portal.index'),
            ! $hasActiveGym && $ownsSuspendedGym => route('subscription.show'),
            default => route('dashboard'),
        };

        return redirect()->intended($destination);
    }
}
