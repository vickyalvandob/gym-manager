<?php

namespace App\Http\Controllers;

use App\Actions\Memberships\AssignMembership;
use App\Http\Requests\AssignMemberMembershipRequest;
use App\Support\GymContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class AssignMemberMembershipController extends Controller
{
    public function __construct(private readonly GymContext $gymContext) {}

    public function __invoke(
        AssignMemberMembershipRequest $request,
        int $member,
        AssignMembership $assignMembership,
    ): RedirectResponse {
        $memberModel = $this->gymContext->gym()->members()
            ->whereKey($member)
            ->firstOrFail();
        Gate::authorize('assignMembership', $memberModel);

        $membershipPlan = $this->gymContext->gym()->membershipPlans()
            ->whereKey((int) $request->validated('membership_plan_id'))
            ->where('is_active', true)
            ->firstOrFail();
        $membership = $assignMembership->handle(
            $memberModel,
            $membershipPlan,
            (string) $request->validated('start_date'),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Paket {$membership->plan_name} berhasil ditetapkan.",
        ]);

        return back();
    }
}
