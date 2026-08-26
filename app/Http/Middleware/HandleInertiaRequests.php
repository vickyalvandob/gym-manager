<?php

namespace App\Http\Middleware;

use App\Support\GymContext;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    public function __construct(private readonly GymContext $gymContext) {}

    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => fn (): array => $this->authProps($request),
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function authProps(Request $request): array
    {
        $currentGym = $this->gymContext->hasGym()
            ? $this->gymContext->gym()
            : null;
        $role = $this->gymContext->hasGym()
            ? $this->gymContext->role()
            : null;

        return [
            'user' => $request->user(),
            'currentGym' => $currentGym === null ? null : [
                'id' => $currentGym->getKey(),
                'name' => $currentGym->name,
                'slug' => $currentGym->slug,
                'status' => $currentGym->status->value,
                'timezone' => $currentGym->timezone,
                'currency' => $currentGym->currency,
                'membership_expiry_warning_days' => $currentGym->membership_expiry_warning_days,
                'count_pt_no_show_as_used_session' => $currentGym->count_pt_no_show_as_used_session,
            ],
            'role' => $role?->value,
            'roleLabel' => $role?->label(),
            'permissions' => $role?->permissions() ?? [],
        ];
    }
}
