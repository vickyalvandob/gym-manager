<?php

namespace App\Http\Controllers;

use App\Actions\Platform\UpdateSubscriberSubscription;
use App\Http\Requests\UpdatePlatformSubscriptionRequest;
use App\Models\SaasPlan;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class PlatformUserSubscriptionController extends Controller
{
    public function update(
        UpdatePlatformSubscriptionRequest $request,
        User $user,
        UpdateSubscriberSubscription $updateSubscriberSubscription,
    ): RedirectResponse {
        abort_unless($user->subscription()->exists(), 404);

        $attributes = $request->validated();
        $plan = SaasPlan::query()->where('is_active', true)->findOrFail((int) $attributes['saas_plan_id']);
        $subscription = $updateSubscriberSubscription->handle($user, $plan, $attributes);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Subscription {$user->name} diperbarui menjadi {$subscription->status->label()}.",
        ]);

        return back();
    }
}
