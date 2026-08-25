<?php

namespace Database\Seeders;

use App\Enums\GymRole;
use App\Enums\GymStatus;
use App\Enums\GymUserStatus;
use App\Enums\MemberGender;
use App\Enums\MembershipDurationUnit;
use App\Enums\MemberStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\CheckIn;
use App\Models\Gym;
use App\Models\Member;
use App\Models\MemberMembership;
use App\Models\MembershipPlan;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoGymSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        $gym = Gym::query()->updateOrCreate(
            ['slug' => 'gymflow-demo'],
            [
                'name' => 'GymFlow Demo',
                'status' => GymStatus::Active,
                'timezone' => 'Asia/Jakarta',
                'currency' => 'IDR',
                'membership_expiry_warning_days' => 7,
            ],
        );

        $accounts = [
            ['name' => 'Owner GymFlow', 'email' => 'owner@gym.test', 'role' => GymRole::Owner],
            ['name' => 'Front Desk GymFlow', 'email' => 'frontdesk@gym.test', 'role' => GymRole::Admin],
            ['name' => 'Trainer GymFlow', 'email' => 'trainer@gym.test', 'role' => GymRole::Trainer],
        ];

        foreach ($accounts as $account) {
            $user = User::query()->updateOrCreate(
                ['email' => $account['email']],
                [
                    'name' => $account['name'],
                    'email_verified_at' => now(),
                    'password' => 'password',
                ],
            );

            $gym->users()->syncWithoutDetaching([
                $user->getKey() => [
                    'role' => $account['role']->value,
                    'status' => GymUserStatus::Active->value,
                ],
            ]);
        }

        $members = [
            ['name' => 'Aditya Pratama', 'phone' => '081234560001', 'email' => 'aditya@example.test', 'gender' => MemberGender::Male, 'status' => MemberStatus::Active],
            ['name' => 'Nadia Permata', 'phone' => '081234560002', 'email' => 'nadia@example.test', 'gender' => MemberGender::Female, 'status' => MemberStatus::Active],
            ['name' => 'Rizky Mahendra', 'phone' => '081234560003', 'email' => null, 'gender' => MemberGender::Male, 'status' => MemberStatus::Active],
            ['name' => 'Salsabila Putri', 'phone' => '081234560004', 'email' => 'salsabila@example.test', 'gender' => MemberGender::Female, 'status' => MemberStatus::Active],
            ['name' => 'Fajar Nugroho', 'phone' => '081234560005', 'email' => null, 'gender' => MemberGender::Male, 'status' => MemberStatus::Inactive],
            ['name' => 'Maya Lestari', 'phone' => '081234560006', 'email' => 'maya@example.test', 'gender' => MemberGender::Female, 'status' => MemberStatus::Active],
            ['name' => 'Dimas Saputra', 'phone' => '081234560007', 'email' => 'dimas@example.test', 'gender' => MemberGender::Male, 'status' => MemberStatus::Inactive],
            ['name' => 'Ayu Wulandari', 'phone' => '081234560008', 'email' => null, 'gender' => MemberGender::Female, 'status' => MemberStatus::Active],
        ];

        foreach ($members as $index => $memberData) {
            Member::query()->updateOrCreate(
                [
                    'gym_id' => $gym->getKey(),
                    'member_number' => sprintf('MBR-%06d', $index + 1),
                ],
                [
                    ...$memberData,
                    'birth_date' => now()->subYears(20 + $index)->subMonths($index)->toDateString(),
                    'address' => 'Jakarta',
                    'emergency_contact' => '08129999000'.($index + 1),
                    'notes' => null,
                ],
            );
        }

        $gym->forceFill([
            'next_member_sequence' => max($gym->next_member_sequence, count($members) + 1),
        ])->save();

        $membershipPlans = [
            ['name' => 'Daily Pass', 'duration' => 1, 'duration_unit' => MembershipDurationUnit::Day, 'price' => '50000.00', 'description' => 'Akses gym selama satu hari.', 'is_active' => true],
            ['name' => 'Monthly', 'duration' => 1, 'duration_unit' => MembershipDurationUnit::Month, 'price' => '250000.00', 'description' => 'Paket membership bulanan.', 'is_active' => true],
            ['name' => '3 Months', 'duration' => 3, 'duration_unit' => MembershipDurationUnit::Month, 'price' => '650000.00', 'description' => 'Paket tiga bulan dengan harga lebih hemat.', 'is_active' => true],
            ['name' => '6 Months', 'duration' => 6, 'duration_unit' => MembershipDurationUnit::Month, 'price' => '1200000.00', 'description' => 'Paket membership enam bulan.', 'is_active' => true],
            ['name' => '12 Months', 'duration' => 12, 'duration_unit' => MembershipDurationUnit::Month, 'price' => '2200000.00', 'description' => 'Paket membership tahunan.', 'is_active' => false],
        ];

        foreach ($membershipPlans as $membershipPlanData) {
            MembershipPlan::query()->updateOrCreate(
                [
                    'gym_id' => $gym->getKey(),
                    'name' => $membershipPlanData['name'],
                ],
                $membershipPlanData,
            );
        }

        $monthlyPlan = $gym->membershipPlans()->where('name', 'Monthly')->firstOrFail();
        $threeMonthPlan = $gym->membershipPlans()->where('name', '3 Months')->firstOrFail();
        $aditya = $gym->members()->where('member_number', 'MBR-000001')->firstOrFail();
        $nadia = $gym->members()->where('member_number', 'MBR-000002')->firstOrFail();
        $rizky = $gym->members()->where('member_number', 'MBR-000003')->firstOrFail();
        $salsabila = $gym->members()->where('member_number', 'MBR-000004')->firstOrFail();
        $today = today($gym->timezone);

        $adityaPreviousStart = $today->copy()->subMonthNoOverflow();
        $adityaPrevious = MemberMembership::query()->updateOrCreate(
            [
                'gym_id' => $gym->getKey(),
                'member_id' => $aditya->getKey(),
                'renewed_from_id' => null,
            ],
            [
                'membership_plan_id' => $monthlyPlan->getKey(),
                'plan_name' => $monthlyPlan->name,
                'duration' => $monthlyPlan->duration,
                'duration_unit' => $monthlyPlan->duration_unit,
                'price' => $monthlyPlan->price,
                'start_date' => $adityaPreviousStart,
                'end_date' => $adityaPreviousStart->copy()->addMonthNoOverflow()->subDay(),
            ],
        );
        $adityaRenewalStart = $adityaPrevious->end_date->copy()->addDay();

        $adityaRenewal = MemberMembership::query()->updateOrCreate(
            [
                'gym_id' => $gym->getKey(),
                'member_id' => $aditya->getKey(),
                'renewed_from_id' => $adityaPrevious->getKey(),
            ],
            [
                'membership_plan_id' => $monthlyPlan->getKey(),
                'plan_name' => $monthlyPlan->name,
                'duration' => $monthlyPlan->duration,
                'duration_unit' => $monthlyPlan->duration_unit,
                'price' => $monthlyPlan->price,
                'start_date' => $adityaRenewalStart,
                'end_date' => $adityaRenewalStart->copy()->addMonthNoOverflow()->subDay(),
            ],
        );

        $nadiaStart = $today->copy()->subMonthNoOverflow()->addDays(3);
        $nadiaMembership = MemberMembership::query()->updateOrCreate(
            [
                'gym_id' => $gym->getKey(),
                'member_id' => $nadia->getKey(),
                'renewed_from_id' => null,
            ],
            [
                'membership_plan_id' => $monthlyPlan->getKey(),
                'plan_name' => $monthlyPlan->name,
                'duration' => $monthlyPlan->duration,
                'duration_unit' => $monthlyPlan->duration_unit,
                'price' => $monthlyPlan->price,
                'start_date' => $nadiaStart,
                'end_date' => $nadiaStart->copy()->addMonthNoOverflow()->subDay(),
            ],
        );

        $rizkyStart = $today->copy()->subMonthsNoOverflow(4);
        $rizkyMembership = MemberMembership::query()->updateOrCreate(
            [
                'gym_id' => $gym->getKey(),
                'member_id' => $rizky->getKey(),
                'renewed_from_id' => null,
            ],
            [
                'membership_plan_id' => $threeMonthPlan->getKey(),
                'plan_name' => $threeMonthPlan->name,
                'duration' => $threeMonthPlan->duration,
                'duration_unit' => $threeMonthPlan->duration_unit,
                'price' => $threeMonthPlan->price,
                'start_date' => $rizkyStart,
                'end_date' => $rizkyStart->copy()->addMonthsNoOverflow(3)->subDay(),
            ],
        );

        $salsabilaStart = $today->copy()->addWeek();
        $salsabilaMembership = MemberMembership::query()->updateOrCreate(
            [
                'gym_id' => $gym->getKey(),
                'member_id' => $salsabila->getKey(),
                'renewed_from_id' => null,
            ],
            [
                'membership_plan_id' => $monthlyPlan->getKey(),
                'plan_name' => $monthlyPlan->name,
                'duration' => $monthlyPlan->duration,
                'duration_unit' => $monthlyPlan->duration_unit,
                'price' => $monthlyPlan->price,
                'start_date' => $salsabilaStart,
                'end_date' => $salsabilaStart->copy()->addMonthNoOverflow()->subDay(),
            ],
        );

        $owner = User::query()->where('email', 'owner@gym.test')->firstOrFail();
        $invoiceMonth = $today->format('Ym');
        $paymentExamples = [
            [$adityaPrevious, PaymentStatus::Paid, PaymentMethod::Cash, $today->copy()->subWeeks(3), 'Pembayaran periode sebelumnya.'],
            [$adityaRenewal, PaymentStatus::Pending, null, null, null],
            [$nadiaMembership, PaymentStatus::Paid, PaymentMethod::BankTransfer, $today->copy()->subWeeks(2), 'Transfer terverifikasi.'],
            [$rizkyMembership, PaymentStatus::Paid, PaymentMethod::DebitCard, $today->copy()->subMonths(3), null],
            [$salsabilaMembership, PaymentStatus::Pending, null, null, null],
        ];

        foreach ($paymentExamples as $index => [$membership, $status, $method, $paidAt, $notes]) {
            Payment::query()->updateOrCreate(
                [
                    'gym_id' => $gym->getKey(),
                    'member_membership_id' => $membership->getKey(),
                ],
                [
                    'member_id' => $membership->member_id,
                    'invoice_number' => sprintf('INV-%s-%06d', $invoiceMonth, $index + 1),
                    'amount' => $membership->price,
                    'status' => $status,
                    'method' => $method,
                    'paid_at' => $paidAt,
                    'notes' => $notes,
                    'received_by_id' => $status === PaymentStatus::Paid
                        ? $owner->getKey()
                        : null,
                ],
            );
        }

        $gym->forceFill([
            'next_invoice_sequence' => max($gym->next_invoice_sequence, count($paymentExamples) + 1),
        ])->save();

        $checkInExamples = [
            [$aditya, $adityaRenewal, now($gym->timezone)->subHour()->startOfHour()],
            [$nadia, $nadiaMembership, now($gym->timezone)->subHours(2)->startOfHour()],
            [$aditya, $adityaPrevious, now($gym->timezone)->subDay()->startOfDay()->addHours(17)],
        ];

        foreach ($checkInExamples as [$member, $membership, $checkedInAt]) {
            CheckIn::query()->firstOrCreate(
                [
                    'gym_id' => $gym->getKey(),
                    'member_id' => $member->getKey(),
                    'checked_in_at' => $checkedInAt->copy()->utc(),
                ],
                [
                    'member_membership_id' => $membership->getKey(),
                    'created_by' => $owner->getKey(),
                ],
            );
        }
    }
}
