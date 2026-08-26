<?php

namespace App\Http\Controllers;

use App\Actions\Trainers\AssignMemberToTrainer;
use App\Http\Requests\AssignTrainerMemberRequest;
use App\Support\GymContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class AssignTrainerMemberController extends Controller
{
    public function __construct(private readonly GymContext $gymContext) {}

    public function __invoke(
        AssignTrainerMemberRequest $request,
        int $trainer,
        AssignMemberToTrainer $assignMemberToTrainer,
    ): RedirectResponse {
        $trainerModel = $this->gymContext->gym()->trainers()->whereKey($trainer)->firstOrFail();
        Gate::authorize('assignMembers', $trainerModel);
        $member = $this->gymContext->gym()->members()
            ->whereKey((int) $request->validated('member_id'))
            ->firstOrFail();

        $assignMemberToTrainer->handle($trainerModel, $member);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "{$member->name} berhasil ditugaskan kepada {$trainerModel->name}.",
        ]);

        return back();
    }
}
