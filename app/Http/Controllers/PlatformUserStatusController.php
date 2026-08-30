<?php

namespace App\Http\Controllers;

use App\Actions\Platform\UpdateUserStatus;
use App\Http\Requests\UpdatePlatformUserStatusRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class PlatformUserStatusController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(
        UpdatePlatformUserStatusRequest $request,
        User $user,
        UpdateUserStatus $updateUserStatus,
    ): RedirectResponse {
        $isActive = $request->boolean('is_active');
        $updatedUser = $updateUserStatus->handle($request->user(), $user, $isActive);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Akses akun {$updatedUser->name} berhasil diperbarui.",
        ]);

        return back();
    }
}
