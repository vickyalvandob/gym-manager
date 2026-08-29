<?php

namespace App\Http\Controllers;

use App\Actions\Onboarding\CompleteGymOnboarding;
use App\Enums\GymRole;
use App\Http\Requests\CompleteOnboardingRequest;
use App\Support\GymContext;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class OnboardingController extends Controller
{
    public function __construct(private readonly GymContext $gymContext) {}

    public function edit(): Response|RedirectResponse
    {
        abort_unless($this->gymContext->role() === GymRole::Owner, HttpResponse::HTTP_FORBIDDEN);

        $gym = $this->gymContext->gym()->load('subscription.plan');

        if ($gym->onboarding_completed_at !== null) {
            return to_route('dashboard');
        }

        return Inertia::render('onboarding/edit', [
            'gym' => [
                'name' => $gym->name,
                'timezone' => $gym->timezone,
                'currency' => $gym->currency,
                'phone' => $gym->phone,
                'email' => $gym->email,
                'address' => $gym->address,
            ],
            'subscription' => $gym->subscription === null ? null : [
                'plan_name' => $gym->subscription->plan->name,
                'status_label' => $gym->subscription->status->label(),
                'trial_ends_at' => $gym->subscription->trial_ends_at?->toIso8601String(),
            ],
            'timezoneOptions' => [
                ['value' => 'Asia/Jakarta', 'label' => 'WIB - Jakarta'],
                ['value' => 'Asia/Makassar', 'label' => 'WITA - Makassar'],
                ['value' => 'Asia/Jayapura', 'label' => 'WIT - Jayapura'],
            ],
            'currencyOptions' => [
                ['value' => 'IDR', 'label' => 'IDR - Rupiah'],
            ],
        ]);
    }

    public function update(
        CompleteOnboardingRequest $request,
        CompleteGymOnboarding $completeGymOnboarding,
    ): RedirectResponse {
        $gym = $completeGymOnboarding->handle($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Workspace {$gym->name} siap digunakan.",
        ]);

        return to_route('dashboard');
    }
}
