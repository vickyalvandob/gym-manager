<?php

namespace App\Http\Controllers;

use App\Actions\GymSettings\DeleteGymLogo;
use App\Actions\GymSettings\UpdateGymSettings;
use App\Http\Requests\UpdateGymSettingsRequest;
use App\Models\Gym;
use App\Support\GymContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class GymSettingsController extends Controller
{
    public function __construct(private readonly GymContext $gymContext) {}

    public function edit(): Response
    {
        $gym = $this->gymContext->gym();
        Gate::authorize('update', $gym);

        return Inertia::render('gym-settings/edit', [
            'gym' => $this->gymData($gym),
        ]);
    }

    public function update(
        UpdateGymSettingsRequest $request,
        UpdateGymSettings $updateGymSettings,
    ): RedirectResponse {
        $updateGymSettings->handle(
            $request->safe()->except('logo'),
            $request->file('logo'),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Pengaturan gym berhasil disimpan.',
        ]);

        return to_route('gym-settings.edit');
    }

    public function destroyLogo(DeleteGymLogo $deleteGymLogo): RedirectResponse
    {
        Gate::authorize('update', $this->gymContext->gym());
        $deleteGymLogo->handle();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Logo gym berhasil dihapus.',
        ]);

        return to_route('gym-settings.edit');
    }

    /** @return array<string, mixed> */
    private function gymData(Gym $gym): array
    {
        return [
            'id' => $gym->getKey(),
            'name' => $gym->name,
            'logo_url' => $gym->logo === null
                ? null
                : route('gym-logo.show', ['v' => $gym->updated_at?->getTimestamp()]),
            'phone' => $gym->phone,
            'email' => $gym->email,
            'address' => $gym->address,
            'membership_expiry_warning_days' => $gym->membership_expiry_warning_days,
        ];
    }
}
