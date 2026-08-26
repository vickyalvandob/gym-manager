<?php

namespace App\Http\Controllers;

use App\Actions\PersonalTraining\PurchasePtPackage;
use App\Enums\PaymentMethod;
use App\Http\Requests\PurchaseMemberPtPackageRequest;
use App\Models\User;
use App\Support\GymContext;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class PurchaseMemberPtPackageController extends Controller
{
    public function __construct(private readonly GymContext $gymContext) {}

    public function __invoke(
        PurchaseMemberPtPackageRequest $request,
        int $member,
        PurchasePtPackage $purchasePtPackage,
    ): RedirectResponse {
        $memberModel = $this->gymContext->gym()->members()->whereKey($member)->firstOrFail();
        Gate::authorize('view', $memberModel);
        $ptPackage = $this->gymContext->gym()->ptPackages()
            ->whereKey((int) $request->validated('pt_package_id'))
            ->firstOrFail();
        $trainer = $this->gymContext->gym()->trainers()
            ->whereKey((int) $request->validated('trainer_id'))
            ->firstOrFail();
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        $memberPtPackage = $purchasePtPackage->handle(
            $memberModel,
            $ptPackage,
            $trainer,
            CarbonImmutable::parse(
                (string) $request->validated('start_date'),
                $this->gymContext->gym()->timezone,
            )->startOfDay(),
            PaymentMethod::from((string) $request->validated('payment_method')),
            is_string($request->validated('notes')) ? $request->validated('notes') : null,
            $user,
        );

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Paket PT aktif. {$memberPtPackage->total_sessions} sesi tersedia.",
        ]);
        Inertia::flash('pt_purchase', [
            'member_pt_package_id' => $memberPtPackage->getKey(),
            'message' => "{$memberPtPackage->total_sessions} sesi tersedia.",
        ]);

        return to_route('members.show', $memberModel->getKey());
    }
}
