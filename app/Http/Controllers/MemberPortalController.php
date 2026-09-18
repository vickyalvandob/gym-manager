<?php

namespace App\Http\Controllers;

use App\Actions\Members\UpdateMemberPortalContact;
use App\Http\Requests\UpdateMemberPortalContactRequest;
use App\Models\Member;
use App\Models\User;
use App\Support\MemberPortalAccess;
use App\Support\MemberPortalData;
use App\Support\MemberQrCode;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;

class MemberPortalController extends Controller
{
    public function __construct(
        private readonly MemberPortalAccess $memberPortalAccess,
        private readonly MemberPortalData $memberPortalData,
    ) {}

    public function index(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $member = $this->memberPortalAccess->firstProfile($user);
        abort_unless($member instanceof Member, 403, 'Akun Anda belum memiliki akses portal member aktif.');

        $request->session()->put('member_profile_id', $member->getKey());

        return to_route('my.show');
    }

    public function show(Request $request, Member $member): Response
    {
        Gate::authorize('viewPortal', $member);

        return Inertia::render('member-portal/overview', [
            ...$this->commonProps($request, $member),
            ...$this->memberPortalData->overview($member),
        ]);
    }

    public function checkIns(Request $request, Member $member): Response
    {
        Gate::authorize('viewPortal', $member);

        return Inertia::render('member-portal/check-ins', [
            ...$this->commonProps($request, $member),
            'checkIns' => Inertia::defer(
                fn () => $this->memberPortalData->checkIns($member),
                'memberPortalCheckIns',
                rescue: true,
            ),
        ]);
    }

    public function qr(Request $request, Member $member, MemberQrCode $qrCode): Response
    {
        Gate::authorize('viewPortal', $member);

        return Inertia::render('member-portal/qr', [
            ...$this->commonProps($request, $member),
            'qr_code_data_url' => $qrCode->dataUrl($member),
        ]);
    }

    public function membership(Request $request, Member $member): Response
    {
        Gate::authorize('viewPortal', $member);

        return Inertia::render('member-portal/membership', [
            ...$this->commonProps($request, $member),
            'memberships' => $this->memberPortalData->memberships($member),
        ]);
    }

    public function payments(Request $request, Member $member): Response
    {
        Gate::authorize('viewPortal', $member);

        return Inertia::render('member-portal/payments', [
            ...$this->commonProps($request, $member),
            'payments' => Inertia::defer(
                fn () => $this->memberPortalData->payments($member),
                'memberPortalPayments',
                rescue: true,
            ),
        ]);
    }

    public function personalTraining(Request $request, Member $member): Response
    {
        Gate::authorize('viewPortal', $member);

        return Inertia::render('member-portal/personal-training', [
            ...$this->commonProps($request, $member),
            'ptPackages' => Inertia::defer(
                fn () => $this->memberPortalData->ptPackages($member),
                'memberPortalTraining',
                rescue: true,
            ),
            'ptSessions' => Inertia::defer(
                fn () => $this->memberPortalData->ptSessions($member),
                'memberPortalTraining',
                rescue: true,
            ),
        ]);
    }

    public function settings(Request $request, Member $member): Response
    {
        Gate::authorize('viewPortal', $member);

        return Inertia::render('member-portal/settings', [
            ...$this->commonProps($request, $member),
            'passwordRules' => Password::defaults()->toPasswordRulesString(),
            'section' => $request->routeIs('my.security') ? 'security' : ($request->routeIs('my.settings') ? 'profile' : 'all'),
        ]);
    }

    public function update(
        UpdateMemberPortalContactRequest $request,
        Member $member,
        UpdateMemberPortalContact $updateMemberPortalContact,
    ): RedirectResponse {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $updateMemberPortalContact->handle(
            $member,
            $user,
            $request->contactAttributes(),
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'Kontak member berhasil diperbarui.',
        ]);

        return back();
    }

    /** @return array<string, mixed> */
    private function commonProps(Request $request, Member $member): array
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        return [
            'member' => $this->memberPortalData->member($member),
            'profiles' => $this->memberPortalAccess->profiles($user)
                ->map(fn (Member $profile): array => [
                    'id' => $profile->getKey(),
                    'member_number' => $profile->member_number,
                    'name' => $profile->name,
                    'gym_name' => $profile->gym->name,
                ])
                ->all(),
        ];
    }
}
