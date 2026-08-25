<?php

namespace App\Http\Controllers;

use App\Actions\MembershipPlans\CreateMembershipPlan;
use App\Actions\MembershipPlans\DeleteMembershipPlan;
use App\Actions\MembershipPlans\UpdateMembershipPlan;
use App\Enums\MembershipDurationUnit;
use App\Http\Requests\IndexMembershipPlanRequest;
use App\Http\Requests\StoreMembershipPlanRequest;
use App\Http\Requests\UpdateMembershipPlanRequest;
use App\Models\MembershipPlan;
use App\Support\GymContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class MembershipPlanController extends Controller
{
    public function __construct(private readonly GymContext $gymContext) {}

    public function index(IndexMembershipPlanRequest $request): Response
    {
        $search = Str::squish((string) $request->validated('search', ''));
        $status = $request->validated('status');

        $membershipPlans = $this->gymContext->gym()->membershipPlans()
            ->select([
                'membership_plans.id',
                'membership_plans.name',
                'membership_plans.duration',
                'membership_plans.duration_unit',
                'membership_plans.price',
                'membership_plans.description',
                'membership_plans.is_active',
                'membership_plans.created_at',
            ])
            ->withCount('memberMemberships')
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->when($status === 'active', fn ($query) => $query->where('is_active', true))
            ->when($status === 'inactive', fn ($query) => $query->where('is_active', false))
            ->latest('membership_plans.id')
            ->paginate((int) $request->validated('per_page', 15))
            ->withQueryString()
            ->through(fn (MembershipPlan $membershipPlan): array => $this->membershipPlanData($membershipPlan));

        return Inertia::render('membership-plans/index', [
            'membershipPlans' => $membershipPlans,
            'filters' => [
                'search' => $search,
                'status' => is_string($status) ? $status : '',
                'per_page' => (int) $request->validated('per_page', 15),
            ],
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', MembershipPlan::class);

        return Inertia::render('membership-plans/create', [
            'durationOptions' => $this->durationOptions(),
        ]);
    }

    public function store(
        StoreMembershipPlanRequest $request,
        CreateMembershipPlan $createMembershipPlan,
    ): RedirectResponse {
        $membershipPlan = $createMembershipPlan->handle($request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Paket {$membershipPlan->name} berhasil ditambahkan.",
        ]);

        return to_route('membership-plans.show', $membershipPlan->getKey());
    }

    public function show(int $membership_plan): Response
    {
        $membershipPlan = $this->findMembershipPlan($membership_plan);
        Gate::authorize('view', $membershipPlan);

        return Inertia::render('membership-plans/show', [
            'membershipPlan' => $this->membershipPlanData($membershipPlan),
        ]);
    }

    public function edit(int $membership_plan): Response
    {
        $membershipPlan = $this->findMembershipPlan($membership_plan);
        Gate::authorize('update', $membershipPlan);

        return Inertia::render('membership-plans/edit', [
            'membershipPlan' => $this->membershipPlanData($membershipPlan),
            'durationOptions' => $this->durationOptions(),
        ]);
    }

    public function update(
        UpdateMembershipPlanRequest $request,
        int $membership_plan,
        UpdateMembershipPlan $updateMembershipPlan,
    ): RedirectResponse {
        $membershipPlan = $this->findMembershipPlan($membership_plan);
        Gate::authorize('update', $membershipPlan);
        $membershipPlan = $updateMembershipPlan->handle($membershipPlan, $request->validated());

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Paket {$membershipPlan->name} berhasil diperbarui.",
        ]);

        return to_route('membership-plans.show', $membershipPlan->getKey());
    }

    public function destroy(
        int $membership_plan,
        DeleteMembershipPlan $deleteMembershipPlan,
    ): RedirectResponse {
        $membershipPlan = $this->findMembershipPlan($membership_plan);
        Gate::authorize('delete', $membershipPlan);
        $membershipPlanName = $membershipPlan->name;
        $deleteMembershipPlan->handle($membershipPlan);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Paket {$membershipPlanName} berhasil dihapus.",
        ]);

        return to_route('membership-plans.index');
    }

    private function findMembershipPlan(int $membershipPlan): MembershipPlan
    {
        return $this->gymContext->gym()->membershipPlans()
            ->withCount('memberMemberships')
            ->whereKey($membershipPlan)
            ->firstOrFail();
    }

    /** @return array<string, mixed> */
    private function membershipPlanData(MembershipPlan $membershipPlan): array
    {
        return [
            'id' => $membershipPlan->getKey(),
            'name' => $membershipPlan->name,
            'duration' => $membershipPlan->duration,
            'duration_unit' => $membershipPlan->duration_unit->value,
            'duration_unit_label' => $membershipPlan->duration_unit->label(),
            'duration_label' => "{$membershipPlan->duration} {$membershipPlan->duration_unit->label()}",
            'price' => $membershipPlan->price,
            'description' => $membershipPlan->description,
            'is_active' => $membershipPlan->is_active,
            'status_label' => $membershipPlan->is_active ? 'Aktif' : 'Nonaktif',
            'memberships_count' => $membershipPlan->member_memberships_count ?? 0,
            'can_delete' => ($membershipPlan->member_memberships_count ?? 0) === 0,
            'created_at' => $membershipPlan->created_at?->toIso8601String(),
            'updated_at' => $membershipPlan->updated_at?->toIso8601String(),
        ];
    }

    /** @return array<int, array{value: string, label: string}> */
    private function durationOptions(): array
    {
        return array_map(
            fn (MembershipDurationUnit $unit): array => [
                'value' => $unit->value,
                'label' => $unit->label(),
            ],
            MembershipDurationUnit::cases(),
        );
    }
}
