<?php

namespace App\Http\Controllers;

use App\Actions\Trainers\RemoveMemberFromTrainer;
use App\Support\GymContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class RemoveTrainerMemberController extends Controller
{
    public function __construct(private readonly GymContext $gymContext) {}

    public function __invoke(
        int $trainer,
        int $member,
        RemoveMemberFromTrainer $removeMemberFromTrainer,
    ): RedirectResponse {
        $trainerModel = $this->gymContext->gym()->trainers()->whereKey($trainer)->firstOrFail();
        Gate::authorize('assignMembers', $trainerModel);
        $memberModel = $this->gymContext->gym()->members()->whereKey($member)->firstOrFail();

        $removeMemberFromTrainer->handle($trainerModel, $memberModel);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Assignment {$memberModel->name} berhasil dilepas.",
        ]);

        return back();
    }
}
