<?php

namespace App\Http\Controllers;

use App\Actions\Memberships\AssignMembership;
use App\Http\Requests\RenewMemberMembershipRequest;
use App\Support\GymContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class RenewMemberMembershipController extends Controller
{
    public function __construct(private readonly GymContext $gymContext) {}

    public function __invoke(
        RenewMemberMembershipRequest $request,
        int $member,
        int $member_membership,
        AssignMembership $assignMembership,
    ): RedirectResponse {
        $memberModel = $this->gymContext->gym()->members()
            ->whereKey($member)
            ->firstOrFail();
        Gate::authorize('renewMembership', $memberModel);

        $previousMembership = $memberModel->memberships()
            ->where('gym_id', $this->gymContext->gymId())
            ->whereKey($member_membership)
            ->firstOrFail();
        $membershipPlan = $this->gymContext->gym()->membershipPlans()
            ->whereKey((int) $request->validated('membership_plan_id'))
            ->where('is_active', true)
            ->firstOrFail();
        $membership = $assignMembership->handle(
            $memberModel,
            $membershipPlan,
            (string) $request->validated('start_date'),
            $previousMembership,
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Membership berhasil diperpanjang dengan paket {$membership->plan_name}.",
        ]);

        return back();
    }
}
