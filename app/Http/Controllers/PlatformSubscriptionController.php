<?php

namespace App\Http\Controllers;

use App\Actions\Platform\UpdateGymSubscription;
use App\Http\Requests\UpdatePlatformSubscriptionRequest;
use App\Models\Gym;
use App\Models\SaasPlan;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class PlatformSubscriptionController extends Controller
{
    public function update(
        UpdatePlatformSubscriptionRequest $request,
        Gym $gym,
        UpdateGymSubscription $updateGymSubscription,
    ): RedirectResponse {
        $attributes = $request->validated();
        $plan = SaasPlan::query()->where('is_active', true)->findOrFail((int) $attributes['saas_plan_id']);
        $subscription = $updateGymSubscription->handle($gym, $plan, $attributes);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Subscription {$gym->name} diperbarui menjadi {$subscription->status->label()}.",
        ]);

        return back();
    }
}
