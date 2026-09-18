<?php

namespace App\Http\Controllers;

use App\Enums\GymRole;
use App\Enums\GymStatus;
use App\Enums\GymUserStatus;
use App\Models\Gym;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SwitchCurrentGymController extends Controller
{
    public function __invoke(Request $request, Gym $gym): RedirectResponse
    {
        $accessibleGym = $request->user()?->gyms()
            ->whereKey($gym->getKey())
            ->wherePivot('status', GymUserStatus::Active->value)
            ->first();

        abort_unless($accessibleGym instanceof Gym, Response::HTTP_NOT_FOUND);

        $role = GymRole::from((string) $accessibleGym->getRelation('pivot')->getAttribute('role'));

        if ($gym->status === GymStatus::Suspended) {
            abort_unless($role === GymRole::Owner, Response::HTTP_FORBIDDEN);

            $request->session()->put('current_gym_id', $gym->getKey());

            return to_route('subscription.show');
        }

        $request->session()->put('current_gym_id', $gym->getKey());

        if ($role === GymRole::Trainer) {
            return to_route('trainer.dashboard');
        }

        return $gym->onboarding_completed_at === null
            ? to_route('onboarding.edit')
            : to_route('dashboard');
    }
}
