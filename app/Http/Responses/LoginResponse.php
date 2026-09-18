<?php

namespace App\Http\Responses;

use App\Support\WorkspaceDestination;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;

class LoginResponse implements LoginResponseContract
{
    public function __construct(private readonly WorkspaceDestination $workspaceDestination) {}

    public function toResponse($request): JsonResponse|RedirectResponse
    {
        if ($request->wantsJson()) {
            return response()->json(['two_factor' => false]);
        }

        $user = $request->user();

        if ($user?->is_platform_admin === true) {
            return redirect()->intended(route('platform.dashboard'));
        }

        abort_unless($user !== null, 403);
        $destination = $this->workspaceDestination->routeName(
            $user, (int) $request->session()->get('current_gym_id', 0),
        );

        $request->session()->forget('url.intended');

        return to_route($destination);
    }
}
