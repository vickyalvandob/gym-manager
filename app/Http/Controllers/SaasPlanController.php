<?php

namespace App\Http\Controllers;

use App\Actions\SaasPlans\CreateSaasPlan;
use App\Actions\SaasPlans\UpdateSaasPlan;
use App\Actions\SaasPlans\UpdateSaasPlanStatus;
use App\Enums\SaasPlanInterval;
use App\Http\Requests\StoreSaasPlanRequest;
use App\Http\Requests\UpdateSaasPlanRequest;
use App\Http\Requests\UpdateSaasPlanStatusRequest;
use App\Models\SaasPlan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SaasPlanController extends Controller
{
    public function index(): Response
    {
        Gate::authorize('viewAny', SaasPlan::class);

        return Inertia::render('platform/saas-plans/index', [
            'plans' => SaasPlan::query()
                ->select(['id', 'name', 'slug', 'description', 'price', 'currency', 'billing_interval', 'trial_days', 'max_members', 'max_staff', 'is_active', 'sort_order'])
                ->withCount('subscriptions')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->get()
                ->map(fn (SaasPlan $plan): array => $this->planData($plan)),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', SaasPlan::class);

        return Inertia::render('platform/saas-plans/create', [
            'intervalOptions' => $this->intervalOptions(),
        ]);
    }

    public function store(StoreSaasPlanRequest $request, CreateSaasPlan $createSaasPlan): RedirectResponse
    {
        $plan = $createSaasPlan->handle($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => "Paket {$plan->name} berhasil dibuat."]);

        return to_route('platform.saas-plans.edit', $plan);
    }

    public function edit(SaasPlan $saas_plan): Response
    {
        Gate::authorize('update', $saas_plan);

        return Inertia::render('platform/saas-plans/edit', [
            'plan' => $this->planData($saas_plan->loadCount('subscriptions')),
            'intervalOptions' => $this->intervalOptions(),
        ]);
    }

    public function update(
        UpdateSaasPlanRequest $request,
        SaasPlan $saas_plan,
        UpdateSaasPlan $updateSaasPlan,
    ): RedirectResponse {
        Gate::authorize('update', $saas_plan);
        $plan = $updateSaasPlan->handle($saas_plan, $request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => "Paket {$plan->name} berhasil diperbarui."]);

        return to_route('platform.saas-plans.edit', $plan);
    }

    public function updateStatus(
        UpdateSaasPlanStatusRequest $request,
        SaasPlan $saas_plan,
        UpdateSaasPlanStatus $updateSaasPlanStatus,
    ): RedirectResponse {
        Gate::authorize('update', $saas_plan);
        $plan = $updateSaasPlanStatus->handle($saas_plan, (bool) $request->validated('is_active'));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Status paket {$plan->name} berhasil diperbarui.",
        ]);

        return back();
    }

    /** @return array<string, mixed> */
    private function planData(SaasPlan $plan): array
    {
        return [
            'id' => $plan->getKey(),
            'name' => $plan->name,
            'slug' => $plan->slug,
            'description' => $plan->description,
            'price' => $plan->price,
            'currency' => $plan->currency,
            'billing_interval' => $plan->billing_interval->value,
            'billing_interval_label' => $plan->billing_interval->label(),
            'trial_days' => $plan->trial_days,
            'max_members' => $plan->max_members,
            'max_staff' => $plan->max_staff,
            'is_active' => $plan->is_active,
            'sort_order' => $plan->sort_order,
            'subscriptions_count' => (int) ($plan->subscriptions_count ?? 0),
        ];
    }

    /** @return array<int, array{value: string, label: string}> */
    private function intervalOptions(): array
    {
        return array_map(fn (SaasPlanInterval $interval): array => [
            'value' => $interval->value,
            'label' => $interval->label(),
        ], SaasPlanInterval::cases());
    }
}
