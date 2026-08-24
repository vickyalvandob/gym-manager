<?php

namespace App\Http\Controllers;

use App\Actions\Members\UpdateMemberStatus;
use App\Enums\MemberStatus;
use App\Http\Requests\UpdateMemberStatusRequest;
use App\Support\GymContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class UpdateMemberStatusController extends Controller
{
    public function __construct(private readonly GymContext $gymContext) {}

    public function __invoke(
        UpdateMemberStatusRequest $request,
        int $member,
        UpdateMemberStatus $updateMemberStatus,
    ): RedirectResponse {
        $memberModel = $this->gymContext->gym()->members()
            ->whereKey($member)
            ->firstOrFail();

        Gate::authorize('updateStatus', $memberModel);

        $status = MemberStatus::from((string) $request->validated('status'));
        $updateMemberStatus->handle($memberModel, $status);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Status {$memberModel->member_number} diubah menjadi {$status->label()}.",
        ]);

        return back();
    }
}
