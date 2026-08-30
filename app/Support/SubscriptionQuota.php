<?php

namespace App\Support;

use App\Enums\GymRole;
use App\Models\Member;
use App\Models\SaasPlan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class SubscriptionQuota
{
    /** @return array{gyms: int, members: int, staff: int} */
    public function usage(Subscription $subscription): array
    {
        $subscriptionId = $subscription->getKey();

        return [
            'gyms' => $subscription->gyms()->count(),
            'members' => $this->memberUsage($subscriptionId),
            'staff' => $this->staffUsage($subscriptionId),
        ];
    }

    public function ensureCanCreateGym(Subscription $subscription): void
    {
        $this->ensureBelowLimit('name', $subscription->gyms()->count(), $subscription->plan->max_gyms, 'Batas jumlah gym pada paket subscription sudah tercapai.');
    }

    public function ensureCanCreateMember(Subscription $subscription): void
    {
        $this->ensureBelowLimit('name', $this->memberUsage($subscription->getKey()), $subscription->plan->max_members, 'Batas member pada paket subscription sudah tercapai.');
    }

    public function ensureCanCreateStaff(Subscription $subscription): void
    {
        $this->ensureBelowLimit('email', $this->staffUsage($subscription->getKey()), $subscription->plan->max_staff, 'Batas staf pada paket subscription sudah tercapai.');
    }

    public function ensurePlanCoversUsage(
        Subscription $subscription,
        SaasPlan $plan,
        int $additionalGyms = 0,
        ?string $errorField = 'saas_plan_id',
    ): void {
        $usage = $this->usage($subscription);
        $usage['gyms'] += $additionalGyms;
        $errors = [];

        foreach ([
            ['field' => 'max_gyms', 'usage' => $usage['gyms'], 'limit' => $plan->max_gyms, 'label' => 'gym'],
            ['field' => 'max_members', 'usage' => $usage['members'], 'limit' => $plan->max_members, 'label' => 'member'],
            ['field' => 'max_staff', 'usage' => $usage['staff'], 'limit' => $plan->max_staff, 'label' => 'staf'],
        ] as $capacity) {
            if ($capacity['limit'] !== null && $capacity['usage'] > $capacity['limit']) {
                $field = $errorField ?? $capacity['field'];
                $errors[$field][] = "Pemakaian {$capacity['label']} saat ini ({$capacity['usage']}) melebihi batas paket ({$capacity['limit']}).";
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    private function ensureBelowLimit(string $field, int $usage, ?int $limit, string $message): void
    {
        if ($limit !== null && $usage >= $limit) {
            throw ValidationException::withMessages([$field => $message]);
        }
    }

    private function memberUsage(int $subscriptionId): int
    {
        return Member::query()
            ->whereHas('gym', fn ($query) => $query->where('subscription_id', $subscriptionId))
            ->count();
    }

    private function staffUsage(int $subscriptionId): int
    {
        return User::query()
            ->whereHas('gyms', fn ($query) => $query
                ->where('gyms.subscription_id', $subscriptionId)
                ->where('gym_user.role', '!=', GymRole::Owner->value))
            ->count();
    }
}
