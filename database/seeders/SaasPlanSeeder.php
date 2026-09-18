<?php

namespace Database\Seeders;

use App\Enums\SaasPlanInterval;
use App\Models\SaasPlan;
use Illuminate\Database\Seeder;

class SaasPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Free',
                'slug' => 'free',
                'description' => 'Paket gratis untuk memulai satu gym dengan operasional dasar.',
                'price' => '0.00',
                'currency' => 'IDR',
                'billing_interval' => SaasPlanInterval::Monthly,
                'trial_days' => 0,
                'max_gyms' => 1,
                'max_members' => 20,
                'max_staff' => 5,
                'max_trainers' => 5,
                'max_membership_plans' => 5,
                'max_pt_packages' => 5,
                'is_active' => true,
                'sort_order' => 0,
            ],
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => 'Fondasi operasional untuk gym yang baru beralih dari pencatatan manual.',
                'price' => '299000.00',
                'currency' => 'IDR',
                'billing_interval' => SaasPlanInterval::Monthly,
                'trial_days' => 0,
                'max_gyms' => 1,
                'max_members' => 500,
                'max_staff' => 10,
                'max_trainers' => 10,
                'max_membership_plans' => 20,
                'max_pt_packages' => 20,
                'is_active' => true,
                'sort_order' => 10,
            ],
            [
                'name' => 'Growth',
                'slug' => 'growth',
                'description' => 'Kapasitas lebih besar untuk gym dengan tim dan member yang berkembang.',
                'price' => '599000.00',
                'currency' => 'IDR',
                'billing_interval' => SaasPlanInterval::Monthly,
                'trial_days' => 0,
                'max_gyms' => 3,
                'max_members' => 2000,
                'max_staff' => 30,
                'max_trainers' => 30,
                'max_membership_plans' => 50,
                'max_pt_packages' => 50,
                'is_active' => true,
                'sort_order' => 20,
            ],
            [
                'name' => 'Scale',
                'slug' => 'scale',
                'description' => 'Paket untuk operasional gym berskala besar dengan kapasitas fleksibel.',
                'price' => '999000.00',
                'currency' => 'IDR',
                'billing_interval' => SaasPlanInterval::Monthly,
                'trial_days' => 0,
                'max_gyms' => null,
                'max_members' => null,
                'max_staff' => null,
                'max_trainers' => null,
                'max_membership_plans' => null,
                'max_pt_packages' => null,
                'is_active' => true,
                'sort_order' => 30,
            ],
        ];

        foreach ($plans as $plan) {
            SaasPlan::query()->updateOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}
