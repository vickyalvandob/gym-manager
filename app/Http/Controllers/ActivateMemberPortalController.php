<?php

namespace App\Http\Controllers;

use App\Actions\Members\ManageMemberPortalAccess;
use App\Enums\MemberPortalAccessMethod;
use App\Http\Requests\ManageMemberPortalAccessRequest;
use App\Support\GymContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class ActivateMemberPortalController extends Controller
{
    public function __construct(private readonly GymContext $gymContext) {}

    /**
     * Handle the incoming request.
     */
    public function __invoke(
        ManageMemberPortalAccessRequest $request,
        int $member,
        ManageMemberPortalAccess $manageMemberPortalAccess,
    ): RedirectResponse {
        $memberModel = $this->gymContext->gym()->members()->whereKey($member)->firstOrFail();
        Gate::authorize('managePortal', $memberModel);
        $accessMethod = $request->accessMethod();
        $result = $manageMemberPortalAccess->handle(
            $memberModel,
            $accessMethod,
            $request->portalPassword(),
        );
        $portalUser = $result['user'];

        if ($accessMethod === MemberPortalAccessMethod::Invitation) {
            $status = Password::sendResetLink(['email' => $portalUser->email]);

            if ($status !== Password::ResetLinkSent) {
                throw ValidationException::withMessages([
                    'portal_access' => 'Akun portal sudah disiapkan, tetapi tautan akses belum dapat dikirim. Coba lagi.',
                ]);
            }
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => $accessMethod === MemberPortalAccessMethod::Invitation
                ? 'Undangan portal dikirim ke email member.'
                : ($result['was_enabled']
                    ? 'Password portal member berhasil direset.'
                    : 'Portal member aktif dan password awal berhasil ditetapkan.'),
        ]);

        return back();
    }
}
