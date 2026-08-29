<?php

namespace App\Http\Controllers;

use App\Actions\Platform\UpdateGymStatus;
use App\Enums\GymStatus;
use App\Http\Requests\UpdatePlatformGymStatusRequest;
use App\Models\Gym;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class PlatformGymStatusController extends Controller
{
    public function __invoke(
        UpdatePlatformGymStatusRequest $request,
        Gym $gym,
        UpdateGymStatus $updateGymStatus,
    ): RedirectResponse {
        $gym = $updateGymStatus->handle($gym, GymStatus::from((string) $request->validated('status')));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Status {$gym->name} diubah menjadi {$gym->status->label()}.",
        ]);

        return back();
    }
}
