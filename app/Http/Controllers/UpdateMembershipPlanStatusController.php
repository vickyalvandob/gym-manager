<?php

namespace App\Http\Controllers;

use App\Actions\MembershipPlans\UpdateMembershipPlanStatus;
use App\Http\Requests\UpdateMembershipPlanStatusRequest;
use App\Support\GymContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class UpdateMembershipPlanStatusController extends Controller
{
    public function __construct(private readonly GymContext $gymContext) {}

    public function __invoke(
        UpdateMembershipPlanStatusRequest $request,
        int $membership_plan,
        UpdateMembershipPlanStatus $updateMembershipPlanStatus,
    ): RedirectResponse {
        $membershipPlan = $this->gymContext->gym()->membershipPlans()
            ->whereKey($membership_plan)
            ->firstOrFail();

        Gate::authorize('updateStatus', $membershipPlan);
        $isActive = (bool) $request->validated('is_active');
        $updateMembershipPlanStatus->handle($membershipPlan, $isActive);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Status {$membershipPlan->name} diubah menjadi ".($isActive ? 'Aktif.' : 'Nonaktif.'),
        ]);

        return back();
    }
}
