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

        $gym = $this->gymContext->gym();

        if ($gym->onboarding_completed_at !== null) {
            return to_route('dashboard');
        }

        return Inertia::render('onboarding/edit', [
            'gym' => [
                'id' => $gym->getKey(),
                'name' => $gym->name,
                'timezone' => $gym->timezone,
                'phone' => $gym->phone,
                'email' => $gym->email,
                'address' => $gym->address,
            ],
            'timezoneOptions' => [
                ['value' => 'Asia/Jakarta', 'label' => 'WIB (UTC+7)'],
                ['value' => 'Asia/Makassar', 'label' => 'WITA (UTC+8)'],
                ['value' => 'Asia/Jayapura', 'label' => 'WIT (UTC+9)'],
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
